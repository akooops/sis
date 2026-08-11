import { mount } from 'svelte';

/**
 * Svelte islands. A page opts in with markup only:
 *
 *   <div data-island="visit-booking" data-props="{…json…}"></div>
 *
 * Each entry is a dynamic import, so the wizard bundles are code-split and
 * only downloaded on the pages that actually mount them.
 */
const ISLANDS = {
    'visit-booking': () => import('./islands/VisitBooking.svelte'),
    'job-application': () => import('./islands/JobApplication.svelte'),
};

function readProps(el) {
    if (!el.dataset.props) {
        return {};
    }

    try {
        return JSON.parse(el.dataset.props);
    } catch (error) {
        console.warn('[island] invalid data-props JSON on', el, error);
        return {};
    }
}

export default function initIslands(root = document) {
    root.querySelectorAll('[data-island]').forEach(async (el) => {
        const name = el.dataset.island;
        const load = ISLANDS[name];

        if (!load) {
            console.warn(`[island] no component registered for "${name}"`);
            return;
        }

        const { default: Component } = await load();

        mount(Component, { target: el, props: readProps(el) });
    });
}
