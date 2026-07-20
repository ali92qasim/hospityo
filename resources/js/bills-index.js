import { initDataTable } from './datatable';

function formatCurrency(amount) {
    const formatted = Number(amount || 0).toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });

    return (window.appConfig?.currency || '') + ' ' + formatted;
}

function statusColor(status) {
    const colors = {
        paid: 'green',
        partial: 'yellow',
        pending: 'red',
        draft: 'blue',
        cancelled: 'gray',
    };

    return colors[status] || 'gray';
}

$(document).ready(function () {
    initDataTable('.bills-table', {
        ajax: '/bills/data',
        initComplete: function () {
            $('.bills-table').removeClass('invisible').addClass('visible');
        },

        columns: [
            {
                data: 'bill_number',
                render: function (data) {
                    return `<div class="font-medium text-gray-900">${data}</div>`;
                },
            },
            {
                data: 'patient',
                render: function (data) {
                    if (!data) {
                        return '-';
                    }

                    return `
                        <div class="text-sm text-gray-900">${data.name ?? '-'}</div>
                        <div class="text-sm text-gray-500">${data.phone ?? ''}</div>
                    `;
                },
            },
            {
                data: 'bill_type',
                render: function (data) {
                    return `<span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded uppercase">${data ?? ''}</span>`;
                },
            },
            {
                data: 'total_amount',
                render: function (data, type, row) {
                    const due = Number(row.due_amount || 0);
                    const dueHtml = due > 0
                        ? `<div class="text-sm text-red-500">Due: ${formatCurrency(due)}</div>`
                        : '';

                    return `
                        <div class="text-sm text-gray-900">${formatCurrency(data)}</div>
                        ${dueHtml}
                    `;
                },
            },
            {
                data: 'status',
                render: function (data) {
                    const color = statusColor(data);

                    return `<span class="bg-${color}-100 text-${color}-800 text-xs px-2 py-1 rounded capitalize">${data ?? ''}</span>`;
                },
            },
            {
                data: 'bill_date',
                render: function (data) {
                    if (!data) {
                        return '-';
                    }

                    const date = new Date(data);

                    return date.toLocaleDateString(undefined, {
                        month: 'short',
                        day: 'numeric',
                        year: 'numeric',
                    });
                },
            },
            {
                data: 'id',
                orderable: false,
                searchable: false,
                render: function (id) {
                    return `
                        <div class="flex items-center space-x-3">
                            <a href="/bills/${id}" class="text-medical-blue hover:text-blue-700" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="/bills/${id}/print" target="_blank" class="text-green-600 hover:text-green-700" title="Print">
                                <i class="fas fa-print"></i>
                            </a>
                            <a href="/bills/${id}/edit" class="text-medical-blue hover:text-blue-700" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" action="/bills/${id}" onsubmit="return confirm('Are you sure?')">
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
