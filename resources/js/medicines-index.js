import { initDataTable } from './datatable';
import confirmDialog from './confirm-dialog';

function capitalize(value) {
    if (!value) {
        return '';
    }

    return value.charAt(0).toUpperCase() + value.slice(1);
}

function showMedicineImportResult(type, html) {
    const resultDiv = document.getElementById('medicine-import-result');
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

function renderMedicineImportDoneResult(data) {
    if (data.status === 'failed') {
        showMedicineImportResult('error', data.message || 'Import failed. Please check your file and try again.');
        return;
    }

    const msg = '<strong>' + (data.created ?? 0).toLocaleString() + '</strong> medicine(s) created, '
        + '<strong>' + (data.updated ?? 0).toLocaleString() + '</strong> updated.';

    if (data.errors && data.errors.length > 0) {
        const list = data.errors
            .map(function (error) { return '<li>' + error + '</li>'; })
            .join('');

        showMedicineImportResult(
            'warning',
            msg
            + '<br><span class="font-medium mt-1 block">Warnings (' + data.errors.length + '):</span>'
            + '<ul class="list-disc list-inside text-xs mt-1 space-y-0.5 max-h-40 overflow-y-auto">'
            + list
            + '</ul>'
        );
        return;
    }

    showMedicineImportResult('success', msg);
}

const filters = {
    category_id: '',
    status: '',
    low_stock: false,
};

let medicinesTable = null;

function reloadMedicinesTable() {
    if (medicinesTable) {
        medicinesTable.ajax.reload(null, false);
    }
}

function bindFilters() {
    const categorySelect = document.getElementById('medicine-category-filter');
    const statusSelect = document.getElementById('medicine-status-filter');
    const lowStockCheckbox = document.getElementById('medicine-low-stock-filter');
    const applyButton = document.getElementById('apply-medicine-filters');
    const clearButton = document.getElementById('clear-medicine-filters');

    applyButton?.addEventListener('click', () => {
        filters.category_id = categorySelect?.value ?? '';
        filters.status = statusSelect?.value ?? '';
        filters.low_stock = lowStockCheckbox?.checked ?? false;
        reloadMedicinesTable();
    });

    clearButton?.addEventListener('click', () => {
        filters.category_id = '';
        filters.status = '';
        filters.low_stock = false;

        if (categorySelect) {
            categorySelect.value = '';
        }

        if (statusSelect) {
            statusSelect.value = '';
        }

        if (lowStockCheckbox) {
            lowStockCheckbox.checked = false;
        }

        reloadMedicinesTable();
    });
}

function bindMedicineImportForm(root) {
    const importButton = root.querySelector('[data-medicine-import-trigger]');
    const fileInput = root.querySelector('[data-medicine-import-file]');
    const form = root.querySelector('[data-medicine-import-form]');

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
            title: 'Import medicines',
            message: 'Import "' + fileName + '"?',
            detail: 'The import runs in the background — you can continue using the application. Import categories, brands, and units first. Existing medicines with the same SKU will be updated.',
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

$(document).ready(function () {
    const root = document.getElementById('medicines-index');
    if (root) {
        bindMedicineImportForm(root);
    }

    window.addEventListener('medicine-import-done', function (event) {
        renderMedicineImportDoneResult(event.detail);
        reloadMedicinesTable();
    });

    bindFilters();

    medicinesTable = initDataTable('.medicines-table', {
        ajax: {
            url: '/medicines/data',
            data: function (params) {
                params.category_id = filters.category_id;
                params.status = filters.status;
                params.low_stock = filters.low_stock ? 1 : 0;
            },
        },
        initComplete: function () {
            $('.medicines-table').removeClass('invisible').addClass('visible');
        },
        columns: [
            {
                data: 'name',
                render: function (data, type, row) {
                    const details = row.strength ?? '';
                    const brand = row.brand?.name
                        ? `<div class="text-xs text-gray-400">${row.brand.name}</div>`
                        : '';

                    return `
                        <div class="font-medium text-gray-900">${data ?? '-'}</div>
                        ${details ? `<div class="text-sm text-gray-500">${details}</div>` : ''}
                        ${brand}
                    `;
                },
            },
            {
                data: 'sku',
                render: function (data) {
                    return `
                        <div class="text-sm font-mono text-gray-700 bg-gray-100 px-2 py-1 rounded inline-block">
                            ${data ?? '-'}
                        </div>
                    `;
                },
            },
            {
                data: 'category',
                render: function (data) {
                    return `<span class="text-sm text-gray-500">${data?.name ?? '-'}</span>`;
                },
            },
            {
                data: 'brand',
                visible: false,
                searchable: true,
            },
            {
                data: 'stock_quantity',
                orderable: false,
                searchable: false,
                render: function (_data, _type, row) {
                    if (!row.manage_stock) {
                        return `
                            <div class="text-sm text-gray-500">
                                <i class="fas fa-ban mr-1"></i>Not Managed
                            </div>
                        `;
                    }

                    const stockClass = row.is_low_stock ? 'text-red-600' : 'text-gray-900';
                    const lowStockHtml = row.is_low_stock
                        ? `<div class="text-xs text-red-500">Low Stock (Reorder: ${row.reorder_level ?? 0})</div>`
                        : '';

                    return `
                        <div class="text-sm font-medium ${stockClass}">
                            ${row.stock_quantity ?? 0} ${row.stock_unit ?? ''}
                        </div>
                        ${lowStockHtml}
                    `;
                },
            },
            {
                data: 'status',
                render: function (data) {
                    const isActive = data === 'active';
                    const badgeClass = isActive
                        ? 'bg-green-100 text-green-800'
                        : 'bg-red-100 text-red-800';

                    return `<span class="px-2 py-1 text-xs rounded-full ${badgeClass}">${capitalize(data ?? '')}</span>`;
                },
            },
            {
                data: 'id',
                orderable: false,
                searchable: false,
                render: function (id) {
                    return `
                        <div class="flex items-center space-x-3">
                            <a href="/medicines/${id}/edit" class="text-medical-blue hover:text-blue-700" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" action="/medicines/${id}" onsubmit="return confirm('Are you sure?')">
                                <input type="hidden" name="_token" value="${window.csrf}">
                                <input type="hidden" name="_method" value="DELETE">
                                <button type="submit" class="text-red-600 hover:text-red-700" title="Delete">
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
