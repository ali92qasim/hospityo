import { initDataTable } from './datatable';

$(document).ready(function () {

    initDataTable('.investigations-table', {
        ajax: '/investigations/data',
        initComplete: function () {
            $('.investigations-table').removeClass('invisible').addClass('visible');
        },

        columns: [
            { data: 'code' },
            { data: 'name' },

            {
                data: 'category',
                render: function (data) {
                    const badges = {
                        hematology:              'bg-red-100 text-red-800',
                        biochemistry:            'bg-yellow-100 text-yellow-800',
                        microbiology:            'bg-green-100 text-green-800',
                        immunology:              'bg-indigo-100 text-indigo-800',
                        histopathology:          'bg-pink-100 text-pink-800',
                        molecular:               'bg-cyan-100 text-cyan-800',
                        'x-ray':                 'bg-purple-100 text-purple-800',
                        ultrasound:              'bg-blue-100 text-blue-800',
                        'ct-scan':               'bg-orange-100 text-orange-800',
                        mri:                     'bg-teal-100 text-teal-800',
                        'cardiac-diagnostics':   'bg-rose-100 text-rose-800',
                    };

                    const cls = badges[data] || 'bg-gray-100 text-gray-800';

                    return `<span class="px-2 py-1 text-xs font-medium rounded-full ${cls}">
                    ${data.replace(/-/g, ' ')}
                </span>`;
                }
            },

            {
                data: 'price',
                render: function (data) {
                    const price = Number(data || 0).toLocaleString();
                    return (window.appConfig?.currency || '') + ' ' + price;
                }
            },

            {
                data: 'turnaround_time',
                render: d => d ?? '-'
            },

            {
                data: 'is_active',
                render: function (data) {
                    return data
                        ? `<span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Active</span>`
                        : `<span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Inactive</span>`;
                }
            },

            {
                data: 'id',
                orderable: false,
                searchable: false,
                render: function (id) {
                    return `
                <div class="flex items-center space-x-3">

                    <a href="/investigations/${id}" class="text-blue-600">
                        <i class="fas fa-eye"></i>
                    </a>

                    <a href="/investigations/${id}/edit" class="text-yellow-600">
                        <i class="fas fa-edit"></i>
                    </a>

                    <form method="POST" action="/investigations/${id}" onsubmit="return confirm('Delete?')">
                        <input type="hidden" name="_token" value="${window.csrf}">
                        <input type="hidden" name="_method" value="DELETE">
                        <button class="text-red-600">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>

                </div>
            `;
                }
            }
        ]
    });

});
