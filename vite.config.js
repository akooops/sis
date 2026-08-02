import { defineConfig } from 'vite';
import { resolve } from 'node:path';
import laravel from 'laravel-vite-plugin';
import { svelte } from '@sveltejs/vite-plugin-svelte';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        tailwindcss(),
        laravel({
            // app.* is the admin (Inertia + the whole Metronic theme); public.*
            // is the standalone public form page, which must not ship either.
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/public.css',
                'resources/js/public.js',
            ],
            refresh: true,
        }),
        svelte(),
    ],
    resolve: {
        alias: {
            '@': resolve(__dirname, 'resources/js'),
        },
    },
});
