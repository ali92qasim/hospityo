import flatpickr from 'flatpickr';
import { initDataTable } from './datatable';

const typeColors = {
    opd: 'bg-blue-500',
    ipd: 'bg-green-500',
    emergency: 'bg-red-500',
};

const statusColors = {
    registered: 'bg-blue-100 text-blue-800',
    vitals_recorded: 'bg-green-100 text-green-800',
    with_doctor: 'bg-purple-100 text-purple-800',
    tests_ordered: 'bg-yellow-100 text-yellow-800',
    tests_completed: 'bg-orange-100 text-orange-800',
    completed: 'bg-gray-100 text-gray-800',
    admitted: 'bg-purple-100 text-purple-800',
    triaged: 'bg-red-100 text-red-800',
    discharged: 'bg-orange-100 text-orange-800',
};

const dateFilterClasses = {
    '': 'bg-gray-800 text-white',
    today: 'bg-blue-500 text-white',
    yesterday: 'bg-blue-500 text-white',
    this_week: 'bg-green-500 text-white',
    last_week: 'bg-green-500 text-white',
    this_month: 'bg-purple-500 text-white',
    last_month: 'bg-purple-500 text-white',
    custom: 'bg-orange-500 text-white',
};

const dateFilterInactiveClasses = {
    '': 'bg-gray-100 text-gray-700 hover:bg-gray-200',
    today: 'bg-blue-100 text-blue-700 hover:bg-blue-200',
    yesterday: 'bg-blue-100 text-blue-700 hover:bg-blue-200',
    this_week: 'bg-green-100 text-green-700 hover:bg-green-200',
    last_week: 'bg-green-100 text-green-700 hover:bg-green-200',
    this_month: 'bg-purple-100 text-purple-700 hover:bg-purple-200',
    last_month: 'bg-purple-100 text-purple-700 hover:bg-purple-200',
    custom: 'bg-orange-100 text-orange-700 hover:bg-orange-200',
};

const statusFilterClasses = {
    '': 'bg-gray-800 text-white',
    registered: 'bg-blue-500 text-white',
    vitals_recorded: 'bg-green-500 text-white',
    with_doctor: 'bg-purple-500 text-white',
    completed: 'bg-gray-500 text-white',
};

const statusFilterInactiveClasses = {
    '': 'bg-gray-100 text-gray-700 hover:bg-gray-200',
    registered: 'bg-blue-100 text-blue-700 hover:bg-blue-200',
    vitals_recorded: 'bg-green-100 text-green-700 hover:bg-green-200',
    with_doctor: 'bg-purple-100 text-purple-700 hover:bg-purple-200',
    completed: 'bg-gray-100 text-gray-700 hover:bg-gray-200',
};

const filters = {
    date_filter: '',
    start_date: '',
    end_date: '',
    status: '',
    visit_type: '',
};

let visitsTable = null;
let searchTimeout = null;

function formatStatus(status) {
    if (!status) {
        return '';
    }

    return status.replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase());
}

function formatVisitDate(value) {
    if (!value) {
        return '-';
    }

    const date = new Date(value);

    return date.toLocaleString(undefined, {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    });
}

function setActiveFilterButton(groupSelector, activeValue, activeClasses, inactiveClasses) {
    document.querySelectorAll(groupSelector).forEach((button) => {
        const value = button.dataset.filterValue ?? '';
        const isActive = value === activeValue;
        const classMap = isActive ? activeClasses : inactiveClasses;

        button.className = `visit-filter-btn px-3 py-1 text-sm rounded-full ${classMap[value] ?? classMap['']}`;
    });
}

function reloadVisitsTable() {
    if (visitsTable) {
        visitsTable.ajax.reload();
    }
}

function toggleCustomDateRange() {
    document.getElementById('custom-date-range')?.classList.toggle('hidden');
}

function initFlatpickr() {
    const startDateInput = document.querySelector('input[name="start_date"]');
    const endDateInput = document.querySelector('input[name="end_date"]');

    if (startDateInput) {
        flatpickr(startDateInput, {
            dateFormat: 'Y-m-d',
            maxDate: 'today',
            allowInput: true,
            onChange: function (_selectedDates, dateStr) {
                if (endDateInput?._flatpickr) {
                    endDateInput._flatpickr.set('minDate', dateStr);
                }
            },
        });
    }

    if (endDateInput) {
        flatpickr(endDateInput, {
            dateFormat: 'Y-m-d',
            maxDate: 'today',
            allowInput: true,
            minDate: startDateInput?.value || null,
        });
    }
}

function bindFilters() {
    document.querySelectorAll('[data-date-filter]').forEach((button) => {
        button.addEventListener('click', () => {
            const value = button.dataset.filterValue ?? '';

            if (value === 'custom') {
                toggleCustomDateRange();
                return;
            }

            filters.date_filter = value;
            filters.start_date = '';
            filters.end_date = '';

            document.getElementById('custom-date-range')?.classList.add('hidden');
            document.querySelector('input[name="start_date"]').value = '';
            document.querySelector('input[name="end_date"]').value = '';

            setActiveFilterButton('[data-date-filter]', value, dateFilterClasses, dateFilterInactiveClasses);
            reloadVisitsTable();
        });
    });

    document.querySelectorAll('[data-status-filter]').forEach((button) => {
        button.addEventListener('click', () => {
            const value = button.dataset.filterValue ?? '';
            filters.status = value;

            setActiveFilterButton('[data-status-filter]', value, statusFilterClasses, statusFilterInactiveClasses);
            reloadVisitsTable();
        });
    });

    document.getElementById('apply-custom-date-range')?.addEventListener('click', () => {
        const startDate = document.querySelector('input[name="start_date"]')?.value;
        const endDate = document.querySelector('input[name="end_date"]')?.value;

        if (!startDate || !endDate) {
            return;
        }

        filters.date_filter = '';
        filters.start_date = startDate;
        filters.end_date = endDate;

        setActiveFilterButton('[data-date-filter]', 'custom', dateFilterClasses, dateFilterInactiveClasses);
        reloadVisitsTable();
    });

    document.getElementById('clear-custom-date-range')?.addEventListener('click', () => {
        filters.start_date = '';
        filters.end_date = '';

        document.querySelector('input[name="start_date"]').value = '';
        document.querySelector('input[name="end_date"]').value = '';
        document.getElementById('custom-date-range')?.classList.add('hidden');

        setActiveFilterButton('[data-date-filter]', filters.date_filter, dateFilterClasses, dateFilterInactiveClasses);
        reloadVisitsTable();
    });

    const searchInput = document.getElementById('search-visits');
    const searchClear = document.getElementById('search-clear');

    searchInput?.addEventListener('input', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            const value = searchInput.value.trim();
            searchClear?.classList.toggle('hidden', value === '');

            if (visitsTable) {
                visitsTable.search(value).draw();
            }
        }, 300);
    });

    searchClear?.addEventListener('click', () => {
        searchInput.value = '';
        searchClear.classList.add('hidden');

        if (visitsTable) {
            visitsTable.search('').draw();
        }
    });
}

$(document).ready(function () {
    initFlatpickr();
    bindFilters();

    visitsTable = initDataTable('.visits-table', {
        ajax: {
            url: '/visits/data',
            data: function (params) {
                params.date_filter = filters.date_filter;
                params.start_date = filters.start_date;
                params.end_date = filters.end_date;
                params.status = filters.status;
                params.visit_type = filters.visit_type;
            },
        },
        layout: {
            topStart: 'pageLength',
            topEnd: null,
            bottomStart: 'info',
            bottomEnd: 'paging',
        },
        initComplete: function () {
            $('.visits-table').removeClass('invisible').addClass('visible');
        },
        columns: [
            {
                data: 'visit_no',
                render: function (data, type, row) {
                    const visitType = (row.visit_type ?? '').toUpperCase();
                    const colorClass = typeColors[row.visit_type] ?? 'bg-gray-500';

                    return `
                        <div class="flex items-center">
                            <div class="w-10 h-10 ${colorClass} rounded-full flex items-center justify-center mr-3">
                                <i class="fas fa-clipboard-list text-white"></i>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-gray-900">${data ?? '-'}</div>
                                <div class="text-sm text-gray-500">${visitType}</div>
                                <div class="text-xs text-gray-400">${formatVisitDate(row.visit_datetime)}</div>
                            </div>
                        </div>
                    `;
                },
            },
            {
                data: 'patient',
                render: function (data) {
                    return `
                        <div class="text-sm text-gray-900">${data?.name ?? 'Deleted Patient'}</div>
                        <div class="text-xs text-gray-500">${data?.patient_no ?? '-'}</div>
                    `;
                },
            },
            {
                data: 'doctor',
                render: function (data) {
                    if (!data) {
                        return '<span class="text-sm text-gray-400">Not assigned</span>';
                    }

                    return `
                        <div class="text-sm text-gray-900">Dr. ${data.name ?? '-'}</div>
                        <div class="text-xs text-gray-500">${data.department?.name ?? 'No Department'}</div>
                    `;
                },
            },
            {
                data: 'status',
                render: function (data) {
                    const badgeClass = statusColors[data] ?? 'bg-gray-100 text-gray-800';

                    return `<span class="px-2 py-1 text-xs rounded-full ${badgeClass}">${formatStatus(data)}</span>`;
                },
            },
            {
                data: 'id',
                orderable: false,
                searchable: false,
                render: function (id) {
                    return `
                        <a href="/visits/${id}/workflow" class="text-medical-blue hover:text-blue-700">
                            <i class="fas fa-tasks mr-1"></i>Workflow
                        </a>
                    `;
                },
            },
        ],
    });
});
