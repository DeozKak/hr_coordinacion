import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                // Bundle antiguo (AdminLTE/Bootstrap) — se mantiene mientras dure la migración.
                'resources/sass/app.scss',
                'resources/js/app.js',
                // Bundle nuevo (Tailwind + Alpine).
                'resources/css/app.css',
                'resources/js/tw.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
