import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import inject from '@rollup/plugin-inject';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/appointments-calendar.css',
                'resources/css/doctors-form.css',
                'resources/css/visits-form.css',
                'resources/css/ot-calendar.css',
                'resources/css/ot-surgeries.css',
                'resources/js/app.js',
                'resources/js/appointments-calendar.js',
                'resources/js/doctors-form.js',
                'resources/js/visits-form.js',
                'resources/js/visits-index.js',
                'resources/js/prescription-form.js',
                'resources/js/wards-form.js',
                'resources/js/inventory-form.js',
                'resources/js/purchases-form.js',
                'resources/js/investigations-form.js',
                'resources/js/bills-form.js',
                'resources/js/date-picker.js',
                'resources/js/datatable.js',
                'resources/js/pagination.js',
                'resources/js/ot-scheduling.js',
                'resources/js/ot-calendar.js',
                'resources/js/ot-surgeries.js',
                'resources/js/ot-surgery-show.js',
                'resources/js/pac-form.js',
                'resources/js/surgical-checklist.js',
                'resources/js/ot-consumable-usage.js',
                'resources/js/sterilization-form.js',
                'resources/js/operative-vitals.js',
            ],
            refresh: true,
        }),
        inject({
            $: 'jquery',
            jQuery: 'jquery',
            exclude: [
                '**/select2/**',
                '**/node_modules/select2/**',
            ]
        }),
    ],
});
