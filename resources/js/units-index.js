/**
 * Units index — DataTables and import UI.
 */

import { initDataTable } from './datatable';
import confirmDialog from './confirm-dialog';

const STORAGE_KEYS = {
    importKey: 'unitImportKey',
    statusUrl: 'unitImportStatusUrl',
    indexUrl: 'unitImportIndexUrl',
    expiry: 'unitImportExpiry',
    doneFlag: 'unitImportDone',
};

const POLL_MS = 1500;
const RETRY_MS = 3000;

let unitsTable = null;

function capitalize(value) {
    if (!value) {
        return '';
    }

    return value.charAt(0).toUpperCase() + value.slice(1);
}

function reloadUnitsTable() {
    if (unitsTable) {
        unitsTable.ajax.reload(null, false);
    }
}

function clearImportStorage() {
    Object.values(STORAGE_KEYS).forEach((key) => localStorage.removeItem(key));
}

function isImportExpired() {
    const expiry = parseInt(localStorage.getItem(STORAGE_KEYS.expiry) || '0', 10);
    return !expiry || Date.now() > expiry;
}

function showImportResult(type, html) {
    const resultDiv = document.getElementById('unit-import-result');
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
    const banner = document.getElementById('unit-import-progress');
    if (banner) {
        banner.classList.add('hidden');
    }
}

function showProgressBanner() {
    const banner = document.getElementById('unit-import-progress');
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
        showImportResult('success', 'Units imported successfully.');
    }
}

function renderDoneResult(data) {
    if (data.status === 'failed') {
        let html = data.message || 'Import failed. Please check your file and try again.';

        if (data.errors && data.errors.length > 1) {
            const list = data.errors
                .map(function (error) { return '<li>' + error + '</li>'; })
                .join('');

            html += '<ul class="list-disc list-inside text-xs mt-2 space-y-0.5 max-h-48 overflow-y-auto">' + list + '</ul>';
        }

        showImportResult('error', html);
        return;
    }

    const msg = '<strong>' + (data.created ?? 0) + '</strong> unit(s) created, '
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
        reloadUnitsTable();
        return;
    }

    showImportResult('success', msg);
    reloadUnitsTable();
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
                if (data.status === 'pending' || data.status === 'processing') {
                    setTimeout(poll, POLL_MS);
                    return;
                }

                hideProgressBanner();
                clearImportStorage();

                if (data.status === 'done') {
                    renderDoneResult(data);
                    return;
                }

                if (data.status === 'failed') {
                    renderDoneResult(data);
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
    const importButton = root.querySelector('[data-unit-import-trigger]');
    const fileInput = root.querySelector('[data-unit-import-file]');
    const form = root.querySelector('[data-unit-import-form]');

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
            title: 'Import units',
            message: 'Import "' + fileName + '"?',
            detail: 'New units will be created. Existing units with the same abbreviation will be updated. Base units in parentheses are resolved automatically. CSV, .xls, and .xlsx files are accepted.',
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

function bindDeleteConfirm(root) {
    root.addEventListener('submit', function (event) {
        const form = event.target.closest('form[data-confirm-delete="unit"]');
        if (!form) {
            return;
        }

        event.preventDefault();

        confirmDialog({
            title: 'Delete unit',
            message: 'Delete this unit?',
            detail: 'This action cannot be undone if the unit is not linked elsewhere.',
            confirmText: 'Delete',
            cancelText: 'Cancel',
            variant: 'danger',
        }).then(function (confirmed) {
            if (confirmed) {
                form.submit();
            }
        });
    });
}

$(document).ready(function () {
    const root = document.getElementById('units-index');
    if (!root) {
        return;
    }

    handlePostReloadResult();
    bindImportForm(root);
    bootstrapImportPolling(root);
    bindDeleteConfirm(root);

    unitsTable = initDataTable('.units-table', {
        ajax: '/units/data',
        initComplete: function () {
            $('.units-table').removeClass('invisible').addClass('visible');
        },
        columns: [
            {
                data: 'name',
                render: function (data) {
                    return `<div class="text-sm font-medium text-gray-900">${data ?? '-'}</div>`;
                },
            },
            {
                data: 'abbreviation',
                render: function (data) {
                    return `<span class="text-sm text-gray-900">${data ?? '-'}</span>`;
                },
            },
            {
                data: 'base_unit',
                render: function (data) {
                    return `<span class="text-sm text-gray-900">${data?.name ?? 'Base Unit'}</span>`;
                },
            },
            {
                data: 'conversion_factor',
                orderable: false,
                searchable: false,
                render: function (_data, _type, row) {
                    const baseUnit = row.base_unit;

                    if (!baseUnit) {
                        return '<span class="text-sm text-gray-900">Base Unit</span>';
                    }

                    return `<span class="text-sm text-gray-900">1 ${row.abbreviation ?? ''} = ${row.conversion_factor ?? 0} ${baseUnit.abbreviation ?? ''}</span>`;
                },
            },
            {
                data: 'type',
                render: function (data) {
                    return `<span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">${capitalize(data ?? '')}</span>`;
                },
            },
            {
                data: 'is_active',
                render: function (data) {
                    const badgeClass = data
                        ? 'bg-green-100 text-green-800'
                        : 'bg-red-100 text-red-800';

                    return `<span class="px-2 py-1 text-xs rounded-full ${badgeClass}">${data ? 'Active' : 'Inactive'}</span>`;
                },
            },
            {
                data: 'id',
                orderable: false,
                searchable: false,
                render: function (id) {
                    return `
                        <div class="flex items-center space-x-3">
                            <a href="/units/${id}/edit" class="text-yellow-600 hover:text-yellow-800" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" action="/units/${id}" data-confirm-delete="unit">
                                <input type="hidden" name="_token" value="${window.csrf}">
                                <input type="hidden" name="_method" value="DELETE">
                                <button type="submit" class="text-red-600 hover:text-red-800" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    `;
                },
            },
        ],
    });
});
