import './bootstrap';

import { createInertiaApp } from '@inertiajs/svelte'
import { mount } from 'svelte'

const route = window.route;

createInertiaApp({
  resolve: name => {
    const pages = import.meta.glob('./Pages/**/*.svelte', { eager: true })
    return pages[`./Pages/${name}.svelte`]
  },
  setup({ el, App, props }) {
    mount(App, { target: el, props })
  },
})