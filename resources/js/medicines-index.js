import { initDataTable } from './datatable';

function capitalize(value) {
    if (!value) {
        return '';
    }

    return value.charAt(0).toUpperCase() + value.slice(1);
}

const filters = {
    category_id: '',
    status: '',
    low_stock: false,
};

let medicinesTable = null;

function reloadMedicinesTable() {
    if (medicinesTable) {
        medicinesTable.ajax.reload();
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

$(document).ready(function () {
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
                    const strength = row.strength ?? '';
                    const dosageForm = row.dosage_form ?? '';
                    const details = [strength, dosageForm].filter(Boolean).join(' - ');
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
