/**
 * Import Poller — persists investigation import progress across page navigations.
 *
 * localStorage keys written by the investigations index view:
 *   investigationImportKey       — server cache key to poll
 *   investigationImportStatusUrl — URL of the importStatus endpoint
 *   investigationImportIndexUrl  — investigations index URL (for post-done reload)
 *   investigationImportExpiry    — ms timestamp; keys are stale after this
 *
 * On every page load:
 *   1. If investigationImportDone flag exists → show result toast, clear flag.
 *   2. If import keys exist and not expired → start polling.
 *   3. If import keys exist but expired → clear silently (no toast).
 *
 * While polling:
 *   pending    → keep polling every 2 s
 *   done       → dismiss spinner, clear keys, reload index (or show toast elsewhere)
 *   failed     → dismiss spinner, clear keys, show error toast
 *   not_found  → dismiss spinner, clear keys silently (cache expired / key gone)
 */

const KEYS = {
    importKey:  'investigationImportKey',
    statusUrl:  'investigationImportStatusUrl',
    indexUrl:   'investigationImportIndexUrl',
    expiry:     'investigationImportExpiry',
    doneFlag:   'investigationImportDone',
};

const POLL_MS  = 2000;
const RETRY_MS = 3000;

// ---------------------------------------------------------------------------

function clearAll() {
    Object.values(KEYS).forEach(k => localStorage.removeItem(k));
}

function isExpired() {
    const exp = parseInt(localStorage.getItem(KEYS.expiry) || '0', 10);
    return !exp || Date.now() > exp;
}

function showResultToast(data) {
    const created = data.created ?? 0;
    const updated = data.updated ?? 0;
    const msg     = created + ' investigation(s) created, ' + updated + ' updated.';

    if (data.status === 'failed') {
        window.Toast.error(data.message || 'Import failed. Please try again.');
        return;
    }

    if (data.errors && data.errors.length) {
        window.Toast.warning(msg + ' ' + data.errors.length + ' row(s) had errors.', 0);
        console.group('[Investigation Import] Row errors');
        data.errors.forEach(e => console.warn(e));
        console.groupEnd();
    } else {
        window.Toast.success(msg || 'Investigations imported successfully.');
    }
}

// ---------------------------------------------------------------------------
// Show toast after a page reload triggered by a completed import
// ---------------------------------------------------------------------------

function handlePostReloadToast() {
    const raw = localStorage.getItem(KEYS.doneFlag);
    if (!raw) return;
    localStorage.removeItem(KEYS.doneFlag);
    try {
        showResultToast(JSON.parse(raw));
    } catch (_) {
        window.Toast.success('Investigations imported successfully.');
    }
}

// ---------------------------------------------------------------------------
// Polling
// ---------------------------------------------------------------------------

function startPolling(cacheKey, statusUrl, indexUrl) {
    const spinner = window.Toast.loading(
        'Import is processing in the background\u2026 investigations are being added.'
    );

    function poll() {
        // Stop if keys were cleared externally (another tab) or expired
        if (!localStorage.getItem(KEYS.importKey) || isExpired()) {
            spinner.dismiss();
            clearAll();
            return;
        }

        fetch(statusUrl + '?key=' + encodeURIComponent(cacheKey), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'pending') {
                // Job still running — poll again
                setTimeout(poll, POLL_MS);
                return;
            }

            // Any terminal status: done, failed, not_found
            spinner.dismiss();
            clearAll();

            if (data.status === 'not_found') {
                // Cache expired before we could read it — stop silently
                return;
            }

            // done or failed
            let indexPath = '';
            try { indexPath = new URL(indexUrl).pathname; } catch (_) { indexPath = indexUrl; }

            if (data.status === 'done' && window.location.pathname === indexPath) {
                // Reload the index so the new rows appear, then show toast
                localStorage.setItem(KEYS.doneFlag, JSON.stringify(data));
                location.reload();
            } else {
                showResultToast(data);
            }
        })
        .catch(() => setTimeout(poll, RETRY_MS));
    }

    setTimeout(poll, POLL_MS);
}

// ---------------------------------------------------------------------------
// Entry point
// ---------------------------------------------------------------------------

document.addEventListener('DOMContentLoaded', function () {
    handlePostReloadToast();

    const cacheKey = localStorage.getItem(KEYS.importKey);
    if (!cacheKey) return;

    if (isExpired()) {
        clearAll();   // stale keys from a previous session — discard silently
        return;
    }

    const statusUrl = localStorage.getItem(KEYS.statusUrl);
    if (!statusUrl) { clearAll(); return; }

    startPolling(cacheKey, statusUrl, localStorage.getItem(KEYS.indexUrl) || '');
});
