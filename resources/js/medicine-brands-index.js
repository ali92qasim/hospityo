/**
 * Medicine brands index — import UI and background import polling.
 */

import confirmDialog from './confirm-dialog';

const STORAGE_KEYS = {
    importKey: 'medicineBrandImportKey',
    statusUrl: 'medicineBrandImportStatusUrl',
    indexUrl: 'medicineBrandImportIndexUrl',
    expiry: 'medicineBrandImportExpiry',
    doneFlag: 'medicineBrandImportDone',
};

const POLL_MS = 1500;
const RETRY_MS = 3000;

function clearImportStorage() {
    Object.values(STORAGE_KEYS).forEach((key) => localStorage.removeItem(key));
}

function isImportExpired() {
    const expiry = parseInt(localStorage.getItem(STORAGE_KEYS.expiry) || '0', 10);
    return !expiry || Date.now() > expiry;
}

function showImportResult(type, html) {
    const resultDiv = document.getElementById('medicine-brand-import-result');
    if (!resultDiv) {
        return;
    }

    const styles = {
        success: 'bg-green-50 border-green-200 text-green-800',
        warning: 'bg-yellow-50 border-yellow-200 text-yellow-800',
        error: 'bg-red-50 border-red-200 text-red-800',
    };

    const icons = {
        success: 'fa-check-circle',
        warning: 'fa-exclamation-triangle',
        error: 'fa-times-circle',
    };

    resultDiv.className = 'mb-4 border rounded-lg p-4 text-sm ' + (styles[type] || styles.warning);
    resultDiv.innerHTML = '<i class="fas ' + (icons[type] || icons.warning) + ' mr-1"></i>' + html;
    resultDiv.classList.remove('hidden');
}

function hideProgressBanner() {
    const banner = document.getElementById('medicine-brand-import-progress');
    if (banner) {
        banner.classList.add('hidden');
    }
}

function showProgressBanner() {
    const banner = document.getElementById('medicine-brand-import-progress');
    if (banner) {
        banner.classList.remove('hidden');
    }
}

function handlePostReloadResult() {
    const raw = localStorage.getItem(STORAGE_KEYS.doneFlag);
    if (!raw) {
        return;
    }

    localStorage.removeItem(STORAGE_KEYS.doneFlag);

    try {
        const data = JSON.parse(raw);
        renderDoneResult(data);
    } catch (_) {
        showImportResult('success', 'Medicine brands imported successfully.');
    }
}

function renderDoneResult(data) {
    if (data.status === 'failed') {
        showImportResult('error', data.message || 'Import failed. Please check your file and try again.');
        return;
    }

    const msg = '<strong>' + (data.created ?? 0) + '</strong> brand(s) created, '
        + '<strong>' + (data.updated ?? 0) + '</strong> updated.';

    if (data.errors && data.errors.length > 0) {
        const list = data.errors
            .map(function (error) { return '<li>' + error + '</li>'; })
            .join('');

        showImportResult(
            'warning',
            msg
            + '<br><span class="font-medium mt-1 block">Warnings (' + data.errors.length + '):</span>'
            + '<ul class="list-disc list-inside text-xs mt-1 space-y-0.5 max-h-32 overflow-y-auto">'
            + list
            + '</ul>'
        );
        return;
    }

    showImportResult('success', msg);
}

function startImportPolling(cacheKey, statusUrl, indexUrl, expiryMs) {
    showProgressBanner();

    function poll() {
        if (Date.now() > expiryMs) {
            hideProgressBanner();
            showImportResult('warning', 'Import is taking longer than expected. Please refresh the page to check the result.');
            clearImportStorage();
            return;
        }

        fetch(statusUrl + '?key=' + encodeURIComponent(cacheKey), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (data.status === 'pending') {
                    setTimeout(poll, POLL_MS);
                    return;
                }

                hideProgressBanner();
                clearImportStorage();

                if (data.status === 'done') {
                    renderDoneResult(data);
                    setTimeout(function () {
                        window.location.href = indexUrl;
                    }, 3000);
                    return;
                }

                if (data.status === 'failed') {
                    showImportResult('error', data.message || 'Import failed. Please check your file and try again.');
                    return;
                }

                showImportResult('warning', 'Import status could not be determined. Please refresh the page.');
            })
            .catch(function () {
                setTimeout(poll, RETRY_MS);
            });
    }

    poll();
}

function bindImportForm(root) {
    const importButton = root.querySelector('[data-medicine-brand-import-trigger]');
    const fileInput = root.querySelector('[data-medicine-brand-import-file]');
    const form = root.querySelector('[data-medicine-brand-import-form]');

    if (!importButton || !fileInput || !form) {
        return;
    }

    importButton.addEventListener('click', function () {
        fileInput.click();
    });

    fileInput.addEventListener('change', function () {
        if (!fileInput.files || !fileInput.files.length) {
            return;
        }

        const fileName = fileInput.files[0].name;

        confirmDialog({
            title: 'Import brands',
            message: 'Import "' + fileName + '"?',
            detail: 'New brands will be created. Existing brands with the same name will be updated. CSV, .xls, and .xlsx files are accepted.',
            confirmText: 'Import',
            cancelText: 'Cancel',
            variant: 'success',
        }).then(function (confirmed) {
            if (confirmed) {
                form.submit();
                return;
            }

            fileInput.value = '';
        });
    });
}

function bootstrapImportPolling(root) {
    const pending = root.dataset.importPending === '1';
    const cacheKey = root.dataset.importCacheKey || '';
    const statusUrl = root.dataset.importStatusUrl || '';
    const indexUrl = root.dataset.importIndexUrl || window.location.href;
    const expiryMs = parseInt(root.dataset.importExpiry || '0', 10);

    if (pending && cacheKey && statusUrl) {
        localStorage.setItem(STORAGE_KEYS.importKey, cacheKey);
        localStorage.setItem(STORAGE_KEYS.statusUrl, statusUrl);
        localStorage.setItem(STORAGE_KEYS.indexUrl, indexUrl);
        localStorage.setItem(STORAGE_KEYS.expiry, String(expiryMs || Date.now() + 25 * 60 * 1000));
        startImportPolling(cacheKey, statusUrl, indexUrl, expiryMs || Date.now() + 25 * 60 * 1000);
        return;
    }

    const storedKey = localStorage.getItem(STORAGE_KEYS.importKey);
    if (!storedKey || isImportExpired()) {
        clearImportStorage();
        return;
    }

    const storedStatusUrl = localStorage.getItem(STORAGE_KEYS.statusUrl);
    if (!storedStatusUrl) {
        clearImportStorage();
        return;
    }

    startImportPolling(
        storedKey,
        storedStatusUrl,
        localStorage.getItem(STORAGE_KEYS.indexUrl) || window.location.href,
        parseInt(localStorage.getItem(STORAGE_KEYS.expiry) || '0', 10)
    );
}

document.addEventListener('DOMContentLoaded', function () {
    const root = document.getElementById('medicine-brands-index');
    if (!root) {
        return;
    }

    handlePostReloadResult();
    bindImportForm(root);
    bootstrapImportPolling(root);
});
