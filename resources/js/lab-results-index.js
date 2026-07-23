import { initDataTable } from './datatable';

const statusConfig = {
    preliminary: {
        badgeClass: 'bg-yellow-100 text-yellow-800',
        label: 'Preliminary',
    },
    final: {
        badgeClass: 'bg-green-100 text-green-800',
        label: 'Final',
    },
};

let labResultsTable = null;

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function formatReportedAt(reportedAt, testedAt) {
    const value = reportedAt || testedAt;

    if (!value) {
        return 'N/A';
    }

    return new Date(value).toLocaleString(undefined, {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    });
}

function formatTestsList(tests) {
    const text = tests ?? 'Unknown Test';
    const items = String(text).split(',').map((item) => item.trim()).filter(Boolean);

    if (items.length <= 2) {
        return escapeHtml(text);
    }

    const preview = items.slice(0, 2).join(', ');

    return `${escapeHtml(preview)} <span class="text-gray-500">+${items.length - 2} more</span>`;
}

function reloadLabResultsTable() {
    if (labResultsTable) {
        labResultsTable.ajax.reload(null, false);
    }
}

function verifyResult(resultId) {
    if (!confirm('Verify and finalize this result?')) {
        return;
    }

    fetch(`/lab-results/${resultId}/verify`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': window.csrf,
            'X-Requested-With': 'XMLHttpRequest',
            Accept: 'application/json',
        },
    })
        .then(function (response) {
            if (!response.ok) {
                throw new Error('Verify request failed');
            }

            reloadLabResultsTable();
        })
        .catch(function () {
            alert('Failed to verify result. Please try again.');
        });
}

function bindVerifyButtons(root) {
    root.addEventListener('click', function (event) {
        const button = event.target.closest('[data-verify-result]');
        if (!button) {
            return;
        }

        event.preventDefault();
        verifyResult(button.dataset.verifyResult);
    });
}

$(document).ready(function () {
    const root = document.getElementById('lab-results-index');
    if (root) {
        bindVerifyButtons(root);
    }

    labResultsTable = initDataTable('.lab-results-table', {
        ajax: '/lab-results/data',
        responsive: false,
        scrollX: true,
        autoWidth: false,
        initComplete: function () {
            $('.lab-results-table').removeClass('invisible').addClass('visible');
        },
        columnDefs: [
            { targets: 0, className: 'whitespace-nowrap min-w-[120px]' },
            { targets: 1, className: 'whitespace-nowrap min-w-[160px]' },
            { targets: 2, className: 'whitespace-normal min-w-[240px] max-w-md' },
            { targets: 3, className: 'whitespace-nowrap min-w-[110px]' },
            { targets: 4, className: 'whitespace-nowrap min-w-[150px]' },
            { targets: 5, className: 'whitespace-nowrap min-w-[120px]' },
        ],
        columns: [
            {
                data: 'order_number',
                render: function (data) {
                    return `<span class="font-medium">${escapeHtml(data ?? 'N/A')}</span>`;
                },
            },
            {
                data: 'patient_name',
                render: function (data) {
                    return `<span class="text-sm text-gray-900">${escapeHtml(data ?? 'Unknown Patient')}</span>`;
                },
            },
            {
                data: 'tests_list',
                render: function (data) {
                    const text = data ?? 'Unknown Test';

                    return `<div class="text-sm text-gray-700 whitespace-normal break-words" title="${escapeHtml(text)}">${formatTestsList(text)}</div>`;
                },
            },
            {
                data: 'status',
                render: function (data) {
                    const config = statusConfig[data] ?? statusConfig.preliminary;

                    return `<span class="px-2 py-1 text-xs rounded-full font-medium ${config.badgeClass}">${config.label}</span>`;
                },
            },
            {
                data: 'reported_at',
                render: function (data, type, row) {
                    return `<span class="text-sm text-gray-500">${formatReportedAt(data, row.tested_at)}</span>`;
                },
            },
            {
                data: 'id',
                orderable: false,
                searchable: false,
                render: function (id, type, row) {
                    const verifyButton = row.status === 'preliminary'
                        ? `<button type="button" data-verify-result="${id}" class="text-green-600 hover:text-green-800" title="Verify">
                                <i class="fas fa-check-circle"></i>
                           </button>`
                        : '';

                    return `
                        <div class="flex items-center gap-3 whitespace-nowrap">
                            <a href="/lab-results/${id}" class="text-blue-600 hover:text-blue-800" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            ${verifyButton}
                            <a href="/lab-results/${id}/report" class="text-purple-600 hover:text-purple-800" target="_blank" title="Print Report">
                                <i class="fas fa-print"></i>
                            </a>
                        </div>
                    `;
                },
            },
        ],
    });
});
