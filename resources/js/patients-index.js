import { initDataTable } from './datatable';

function capitalize(value) {
    if (!value) {
        return '';
    }

    return value.charAt(0).toUpperCase() + value.slice(1);
}

$(document).ready(function () {
    initDataTable('.patients-table', {
        ajax: '/patients/data',
        initComplete: function () {
            $('.patients-table').removeClass('invisible').addClass('visible');
        },

        columns: [
            {
                data: 'name',
                render: function (data, type, row) {
                    const gender = capitalize(row.gender);
                    const age = row.age ?? '-';

                    return `
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-medical-blue rounded-full flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-user text-white"></i>
                            </div>
                            <div class="min-w-0">
                                <div class="text-sm font-medium text-gray-900 truncate">${data ?? '-'}</div>
                                <div class="text-sm text-gray-500">${row.patient_no ?? ''}</div>
                                <div class="text-xs text-gray-400">${gender}, ${age} years</div>
                            </div>
                        </div>
                    `;
                },
            },
            {
                data: 'phone',
                render: function (data, type, row) {
                    const maritalStatus = row.marital_status
                        ? `<div class="text-xs text-gray-500">${capitalize(row.marital_status)}</div>`
                        : '';

                    return `
                        <div class="text-sm text-gray-900">${data ?? '-'}</div>
                        ${maritalStatus}
                    `;
                },
            },
            {
                data: 'emergency_name',
                render: function (data, type, row) {
                    const emergencyPhone = row.emergency_phone
                        ? `<div class="text-xs text-gray-500">${row.emergency_phone}${row.emergency_relation ? ` (${row.emergency_relation})` : ''}</div>`
                        : '';

                    return `
                        <div class="text-sm text-gray-900">${data ?? '—'}</div>
                        ${emergencyPhone}
                    `;
                },
            },
            {
                data: 'id',
                orderable: false,
                searchable: false,
                render: function (id) {
                    return `
                        <div class="flex items-center space-x-3">
                            <a href="/patients/${id}" class="text-medical-blue hover:text-blue-700" title="View Details">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="/patients/${id}/history" class="text-purple-600 hover:text-purple-700" title="Patient History">
                                <i class="fas fa-history"></i>
                            </a>
                            <a href="/patients/${id}/edit" class="text-medical-green hover:text-green-700" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                        </div>
                    `;
                },
            },
        ],
    });
});
