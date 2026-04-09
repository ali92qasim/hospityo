import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/appointments-calendar.css',
                'resources/css/doctors-form.css',
                'resources/css/visits-form.css',
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
                'resources/js/bills-form.js'
            ],
            refresh: true,
        }),
    ],
});
