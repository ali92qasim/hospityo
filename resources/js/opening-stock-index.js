import confirmDialog from './confirm-dialog';

function showOpeningStockResult(type, html) {
    const resultDiv = document.getElementById('opening-stock-import-result');
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

function renderOpeningStockDoneResult(data) {
    if (data.status === 'failed') {
        let html = data.message || 'Import failed. Please check your file and try again.';

        if (data.errors && data.errors.length > 1) {
            const list = data.errors
                .map(function (error) { return '<li>' + error + '</li>'; })
                .join('');

            html += '<ul class="list-disc list-inside text-xs mt-2 space-y-0.5 max-h-48 overflow-y-auto">' + list + '</ul>';
        }

        showOpeningStockResult('error', html);
        return;
    }

    const msg = '<strong>' + (data.created ?? 0).toLocaleString() + '</strong> opening stock batch(es) recorded. '
        + 'This import is now locked.';

    showOpeningStockResult('success', msg);

    setTimeout(function () {
        window.location.reload();
    }, 2500);
}

function bindOpeningStockImportForm(root) {
    if (root.dataset.importLocked === '1') {
        return;
    }

    const importButton = root.querySelector('[data-opening-stock-import-trigger]');
    const fileInput = root.querySelector('[data-opening-stock-import-file]');
    const form = root.querySelector('[data-opening-stock-import-form]');

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
            title: 'Import opening stock',
            message: 'Import "' + fileName + '"?',
            detail: 'This is a one-time operation. After a successful import, opening stock upload will be locked for this clinic. All rows must be valid or nothing will be saved.',
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

function bootstrapOpeningStockImportPending(root) {
    if (root.dataset.importPending !== '1') {
        return;
    }

    const cacheKey = root.dataset.importCacheKey;
    const statusUrl = root.dataset.importStatusUrl;

    if (!cacheKey || !statusUrl) {
        return;
    }

    localStorage.setItem('openingStockImportKey', cacheKey);
    localStorage.setItem('openingStockImportStatusUrl', statusUrl);
    localStorage.setItem('openingStockImportIndexUrl', window.location.href);
    localStorage.setItem('openingStockImportExpiry', String(Date.now() + 25 * 60 * 1000));
}

document.addEventListener('DOMContentLoaded', function () {
    const root = document.getElementById('opening-stock-index');
    if (!root) {
        return;
    }

    bootstrapOpeningStockImportPending(root);
    bindOpeningStockImportForm(root);

    window.addEventListener('opening-stock-import-done', function (event) {
        renderOpeningStockDoneResult(event.detail);
    });
});

export { renderOpeningStockDoneResult };
