import $ from 'jquery';
import DataTable from 'datatables.net';
import 'datatables.net-responsive';
import '../js/dataTables.tailwindcss.js'; // ← the vendored Tailwind renderer

window.$ = window.jQuery = $;

export function initDataTable(selector, options = {}) {
    return new DataTable(selector, {
        processing: true,
        serverSide: true,
        responsive: true,
        ordering: false,
        language: {
            processing: `
                <div class="flex items-center justify-center gap-2 text-sm text-gray-500">
                    <svg class="animate-spin h-5 w-5 text-blue-500"
                         xmlns="http://www.w3.org/2000/svg"
                         fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor"
                              d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span>Loading records...</span>
                </div>
            `
        },
        ...options
    });
}
