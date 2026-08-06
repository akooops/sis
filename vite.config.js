import { defineConfig } from 'vite';
import { resolve } from 'node:path';
import laravel from 'laravel-vite-plugin';
import { svelte } from '@sveltejs/vite-plugin-svelte';
import tailwindcss from '@tailwindcss/vite';

/**
 * Two apps in one repo.
 *
 *   resources/admin — the Inertia + Svelte admin, and the whole Metronic theme.
 *   resources/site  — the public website end users visit.
 *
 * They are built SEPARATELY, into separate directories, because a Vite build
 * emits exactly one manifest.json: two targets writing to public/build would
 * leave whichever ran last as the only one Laravel could resolve. Hence
 * `@vite([...], 'build/admin')` and `@vite([...], 'build/site')` in the two
 * Blade shells — the second argument names the build directory.
 *
 * Pick a target with vite's own --mode flag (cross-platform, unlike an env
 * prefix), which is what the npm scripts do:
 *
 *   npm run build         both, one after the other
 *   npm run build:admin   admin only
 *   npm run build:site    site only
 *   npm run dev           one dev server serving both
 */
const TARGETS = {
    admin: {
        input: ['resources/admin/css/app.css', 'resources/admin/js/app.js'],
        buildDirectory: 'build/admin',
    },
    site: {
        input: ['resources/site/css/site.css', 'resources/site/js/site.js'],
        buildDirectory: 'build/site',
    },
};

export default defineConfig(({ command, mode }) => {
    const target = TARGETS[mode] ?? null;

    if (command === 'build' && !target) {
        throw new Error(
            `vite build needs a target: --mode admin or --mode site (got "${mode}"). Use \`npm run build\` to build both.`,
        );
    }

    const { input, buildDirectory } = target ?? {
        input: [...TARGETS.admin.input, ...TARGETS.site.input],
        buildDirectory: 'build',
    };

    return {
        plugins: [
            tailwindcss(),
            laravel({ input, buildDirectory, refresh: true }),
            svelte(),
        ],
        resolve: {
            alias: {
                '@': resolve(__dirname, 'resources/admin/js'),
                '@site': resolve(__dirname, 'resources/site/js'),
            },
        },
    };
});
