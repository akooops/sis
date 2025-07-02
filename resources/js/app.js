import './bootstrap';

import { createInertiaApp } from '@inertiajs/svelte'
import { mount } from 'svelte'

const route = window.route;

createInertiaApp({
  resolve: name => {
    const pages = import.meta.glob('./Pages/**/*.svelte', { eager: true })
    const page = pages[`./Pages/${name}.svelte`]
    
    if (!page) {
      console.error(`Page not found: ./Pages/${name}.svelte`)
      throw new Error(`Page not found: ${name}`)
    }
    
    return page
  },
  setup({ el, App, props }) {
    mount(App, { target: el, props })
  },
})