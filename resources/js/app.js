import './bootstrap';
// Keenicons as a standalone sheet (unlayered) so its icon-glyph `content` wins
// over Tailwind's ::before reset. Fonts are bundled by Vite from the url()s.
import '../../metronic/dist/assets/vendors/keenicons/styles.bundle.css';

import { createInertiaApp, router } from '@inertiajs/svelte';
import { mount } from 'svelte';
import { initKt } from './lib/kt';

createInertiaApp({
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.svelte', { eager: false });
        const page = pages[`./Pages/${name}.svelte`];
        if (!page) throw new Error(`Page not found: ./Pages/${name}.svelte`);
        return page();
    },
    setup({ el, App, props }) {
        mount(App, { target: el, props });
        initKt();
    },
    progress: {
        delay: 250,
        color: '#001965',
        includeCSS: true,
        showSpinner: false,
    },
});

router.on('navigate', initKt);
