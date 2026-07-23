/**
 * Background import poller — tracks CSV/Excel imports across page navigations.
 *
 * Each import type stores keys in localStorage as `{type}ImportKey`, etc.
 * On every admin page load the poller resumes active imports and shows a
 * persistent toast so the rest of the application stays usable.
 */

const PROFILES = [
    {
        type: 'investigation',
        label: 'investigations',
        reloadOnDone: true,
    },
    {
        type: 'medicine',
        label: 'medicines',
        reloadOnDone: false,
        doneEvent: 'medicine-import-done',
    },
    {
        type: 'openingStock',
        label: 'opening stock batches',
        reloadOnDone: false,
        doneEvent: 'opening-stock-import-done',
    },
];

const POLL_MS = 2000;
const RETRY_MS = 3000;

function keysFor(type) {
    return {
        importKey: `${type}ImportKey`,
        statusUrl: `${type}ImportStatusUrl`,
        indexUrl: `${type}ImportIndexUrl`,
        expiry: `${type}ImportExpiry`,
        doneFlag: `${type}ImportDone`,
    };
}

function clearProfile(keys) {
    Object.values(keys).forEach((key) => localStorage.removeItem(key));
}

function isExpired(keys) {
    const expiry = parseInt(localStorage.getItem(keys.expiry) || '0', 10);
    return !expiry || Date.now() > expiry;
}

function progressMessage(label, data) {
    const processed = data.processed ?? 0;
    const total = data.total ?? 0;

    if (total > 0) {
        const pct = Math.min(100, Math.round((processed / total) * 100));
        return `Importing ${label}: ${processed.toLocaleString()} / ${total.toLocaleString()} (${pct}%)…`;
    }

    return `Importing ${label} in the background…`;
}

function showResultToast(data, label) {
    const created = data.created ?? 0;
    const updated = data.updated ?? 0;
    const msg = `${created.toLocaleString()} ${label} created, ${updated.toLocaleString()} updated.`;

    if (data.status === 'failed') {
        window.Toast.error(data.message || 'Import failed. Please try again.');
        return;
    }

    if (data.errors && data.errors.length) {
        window.Toast.warning(`${msg} ${data.errors.length} row(s) had warnings.`, 8000);
        console.group(`[${label} Import] Row warnings`);
        data.errors.forEach((error) => console.warn(error));
        console.groupEnd();
        return;
    }

    window.Toast.success(msg || `${label} imported successfully.`);
}

function handlePostReloadToast(profile) {
    const keys = keysFor(profile.type);
    const raw = localStorage.getItem(keys.doneFlag);
    if (!raw) {
        return;
    }

    localStorage.removeItem(keys.doneFlag);

    try {
        showResultToast(JSON.parse(raw), profile.label);
    } catch (_) {
        window.Toast.success(`${profile.label} imported successfully.`);
    }
}

function startPolling(profile, cacheKey, statusUrl, indexUrl) {
    const keys = keysFor(profile.type);
    const spinner = window.Toast.loading(progressMessage(profile.label, { status: 'pending' }), 0);

    function poll() {
        if (!localStorage.getItem(keys.importKey) || isExpired(keys)) {
            spinner.dismiss();
            clearProfile(keys);
            return;
        }

        fetch(`${statusUrl}?key=${encodeURIComponent(cacheKey)}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.status === 'pending' || data.status === 'processing') {
                    spinner.update(progressMessage(profile.label, data), 'info', true);
                    setTimeout(poll, POLL_MS);
                    return;
                }

                spinner.dismiss();
                clearProfile(keys);

                if (data.status === 'not_found') {
                    return;
                }

                let indexPath = '';
                try {
                    indexPath = new URL(indexUrl, window.location.origin).pathname;
                } catch (_) {
                    indexPath = indexUrl;
                }

                const onIndexPage = window.location.pathname === indexPath;

                if (data.status === 'done' && onIndexPage && profile.reloadOnDone) {
                    localStorage.setItem(keys.doneFlag, JSON.stringify(data));
                    window.location.reload();
                    return;
                }

                if (profile.doneEvent && onIndexPage && (data.status === 'done' || data.status === 'failed')) {
                    window.dispatchEvent(new CustomEvent(profile.doneEvent, { detail: data }));

                    if (data.status === 'failed') {
                        return;
                    }
                } else if (data.status === 'done' && profile.doneEvent) {
                    window.dispatchEvent(new CustomEvent(profile.doneEvent, { detail: data }));
                }

                showResultToast(data, profile.label);
            })
            .catch(() => setTimeout(poll, RETRY_MS));
    }

    setTimeout(poll, POLL_MS);
}

function bootstrapProfile(profile) {
    handlePostReloadToast(profile);

    const keys = keysFor(profile.type);
    const cacheKey = localStorage.getItem(keys.importKey);
    if (!cacheKey) {
        return;
    }

    if (isExpired(keys)) {
        clearProfile(keys);
        return;
    }

    const statusUrl = localStorage.getItem(keys.statusUrl);
    if (!statusUrl) {
        clearProfile(keys);
        return;
    }

    startPolling(
        profile,
        cacheKey,
        statusUrl,
        localStorage.getItem(keys.indexUrl) || window.location.href,
    );
}

document.addEventListener('DOMContentLoaded', () => {
    PROFILES.forEach(bootstrapProfile);
});

export { keysFor, clearProfile, progressMessage };
