import { initDataTable } from './datatable';

function capitalize(value) {
    if (!value) {
        return '';
    }

    return value.charAt(0).toUpperCase() + value.slice(1);
}

function formatCurrency(amount) {
    const formatted = Number(amount || 0).toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });

    return (window.appConfig?.currency || '') + ' ' + formatted;
}

$(document).ready(function () {
    initDataTable('.doctors-table', {
        ajax: '/doctors/data',
        initComplete: function () {
            $('.doctors-table').removeClass('invisible').addClass('visible');
        },

        columns: [
            {
                data: 'name',
                render: function (data, type, row) {
                    return `
                        <div class="flex items-center">
                            <div class="w-9 h-9 bg-medical-green rounded-full flex items-center justify-center mr-3 shrink-0">
                                <i class="fas fa-user-md text-white text-sm"></i>
                            </div>
                            <div class="min-w-0">
                                <div class="text-sm font-medium text-gray-900 truncate max-w-[160px]" title="${data ?? ''}">${data ?? '-'}</div>
                                <div class="text-sm text-gray-500">${row.doctor_no ?? ''}</div>
                                <div class="text-xs text-gray-400">${row.experience_years ?? 0} yrs exp.</div>
                            </div>
                        </div>
                    `;
                },
            },
            {
                data: 'specialization',
                render: function (data, type, row) {
                    return `
                        <div class="text-sm text-gray-900 truncate max-w-[130px]" title="${data ?? ''}">${data ?? '-'}</div>
                        <div class="text-xs text-gray-500 truncate max-w-[130px]">${row.qualification ?? ''}</div>
                    `;
                },
            },
            {
                data: 'phone',
                render: function (data, type, row) {
                    return `
                        <div class="text-sm text-gray-900">${data ?? '-'}</div>
                        <div class="text-xs text-gray-500 truncate max-w-[140px]" title="${row.email ?? ''}">${row.email ?? ''}</div>
                    `;
                },
            },
            {
                data: 'shift_start',
                render: function (data, type, row) {
                    const shiftEnd = row.shift_end ?? '';
                    const shift = data && shiftEnd ? `${data} - ${shiftEnd}` : '-';

                    return `
                        <div class="text-sm text-gray-900 whitespace-nowrap">${shift}</div>
                        <div class="text-xs text-gray-500">${formatCurrency(row.consultation_fee)}</div>
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
                        <div class="flex items-center space-x-1">
                            <a href="/doctors/${id}" class="inline-flex items-center justify-center w-7 h-7 rounded text-medical-blue hover:bg-blue-50" title="View">
                                <i class="fas fa-eye text-xs"></i>
                            </a>
                            <a href="/doctors/${id}/edit" class="inline-flex items-center justify-center w-7 h-7 rounded text-medical-green hover:bg-green-50" title="Edit">
                                <i class="fas fa-edit text-xs"></i>
                            </a>
                            <form method="POST" action="/doctors/${id}" onsubmit="return confirm('Are you sure?')">
                                <input type="hidden" name="_token" value="${window.csrf}">
                                <input type="hidden" name="_method" value="DELETE">
                                <button type="submit" class="inline-flex items-center justify-center w-7 h-7 rounded text-red-600 hover:bg-red-50" title="Delete">
                                    <i class="fas fa-trash text-xs"></i>
                                </button>
                            </form>
                        </div>
                    `;
                },
            },
        ],
    });
});
