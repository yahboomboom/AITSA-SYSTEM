import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/enrollment-app.jsx',
                'resources/js/curriculum-app.jsx',
                'resources/js/dashboard-app.jsx',
                'resources/js/schedule-app.jsx',
                'resources/js/clearance-app.jsx',
                'resources/js/payment-app.jsx',
                'resources/js/faculty-schedule-app.jsx',
                'resources/js/registrar-dashboard-app.jsx',
            ],
            refresh: true,
        }),
        tailwindcss(),
        react(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
