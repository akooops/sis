import './bootstrap';

import { createInertiaApp, router } from '@inertiajs/svelte'
import { mount } from 'svelte'

const route = window.route;
window.route = route;

createInertiaApp({
  resolve: name => {
    const pages = import.meta.glob('./Pages/**/*.svelte', { eager: false })
    const page = pages[`./Pages/${name}.svelte`]
    
    if (!page) {
      console.error(`Page not found: ./Pages/${name}.svelte`)
      throw new Error(`Page not found: ${name}`)
    }
    
    return page()
  },
  setup({ el, App, props }) {    
    mount(App, { target: el, props })
    
    // Initialize KT components on initial load
    setTimeout(() => {
      if (window.KTToggle) {
        window.KTToggle.init();
      }
      if (window.KTReparent) {
        window.KTReparent.init();
      }
      if (window.KTComponents) {
        window.KTComponents.init();
      }
    }, 0);
  },
  progress: {
    // The delay after which the progress bar will appear, in milliseconds...
    delay: 250,

    // The color of the progress bar...
    color: '#001965',

    // Whether to include the default NProgress styles...
    includeCSS: true,

    // Whether the NProgress spinner will be shown...
    showSpinner: false,
  },
})

// Clean up drawers before navigation
router.on('before', (event) => {
  // Find all drawer instances and dispose them
  const drawers = document.querySelectorAll('[data-kt-drawer="true"]');
  drawers.forEach(drawer => {
    if (window.KTDrawer) {
      const instance = window.KTDrawer.getInstance(drawer);
      if (instance) {
        instance.hide();
        // Give it a moment to hide, then dispose
        setTimeout(() => {
          if (instance.dispose) {
            instance.dispose();
          }
        }, 50);
      }
    }
  });
});

// Reinitialize KT components after Inertia navigation AND clean up orphaned drawers
router.on('navigate', (event) => {
  // Remove any drawer elements that are direct children of body
  // These are orphaned drawers from previous pages
  const bodyDrawers = document.querySelectorAll('body > [data-kt-drawer="true"]');
  bodyDrawers.forEach(drawer => {
    drawer.remove();
  });
  
  // Also remove drawer overlays that might be lingering
  const overlays = document.querySelectorAll('.drawer-overlay, [data-kt-drawer-overlay="true"]');
  overlays.forEach(overlay => {
    overlay.remove();
  });
  
  setTimeout(() => {
    if (window.KTDrawer) {
      window.KTDrawer.init();
    }
    if (window.KTMenu) {
      window.KTMenu.init();
    }
    if (window.KTTab) {
      window.KTTab.init();
    }
    if (window.KTDropdown) {
      window.KTDropdown.init();
    }
    if (window.KTToggle) {
      window.KTToggle.init();
    }
    if (window.KTReparent) {
      window.KTReparent.init();
    }
    if (window.KTComponents) {
      window.KTComponents.init();
    }
  }, 0);
});