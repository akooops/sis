/**
 * Public entry point — the end-user side.
 *
 * Deliberately NOT app.js: that one boots createInertiaApp and drags in the
 * whole admin bundle. Nothing here may import from resources/admin.
 *
 * Two tiers. The EAGER modules run on every page because they are the site's
 * chrome — a sticky header that never initialises is a visible bug on every
 * URL. The ON_DEMAND ones are dynamic imports gated on a selector, so a page
 * with no lightbox never downloads GLightbox, and a page with no form never
 * downloads FormRenderer and its sixteen element components.
 *
 * The stylesheet is NOT imported here. vite.config.js lists site.css and site.js
 * as two separate inputs and the Blade shell loads both through
 * @vite([...], 'build/site'); importing it would ship the sheet twice.
 */
import initBackgrounds from './site/backgrounds';
import initDisclosure from './site/disclosure';
import initHeader from './site/header';
import initReveal from './site/aos';
import initSwipers from './site/swiper';

/**
 * [selector, loader] — the module is fetched only if the selector matches.
 *
 * The form marker is `[data-sisf]`, the wrapper site::partials.forms.embed puts
 * around a rendered form. It used to wrap the CONFIRMATION too, so that the
 * module would load on that render and report a conversion; the form emits no
 * third-party analytics any more, so a confirmation is plain HTML and this
 * loads only where there is actually a form to mount.
 */
const ON_DEMAND = [
    ['[data-sisf]', () => import('./site/forms')],
    /*
     * The booking flow, which mounts the SAME FormRenderer behind a
     * service-and-slot gate. Which is why the visits page passes `marker: false`
     * to site::partials.forms.embed: the `[data-sisf]` marker above would
     * otherwise load site/forms.js as well and mount a second renderer into the
     * one root.
     */
    ['[data-visits-root]', () => import('./site/visits')],
    ['[data-lightbox]', () => import('./site/lightbox')],
    ['[data-calendar]', () => import('./site/calendar')],
    ['[data-datepicker]', () => import('./site/datepicker')],
    ['[data-phone-input]', () => import('./site/phone')],
];

function boot() {
    initBackgrounds();
    initDisclosure();
    initHeader();
    initReveal();
    initSwipers();

    for (const [selector, load] of ON_DEMAND) {
        if (!document.querySelector(selector)) {
            continue;
        }

        load()
            .then((module) => module.default?.())
            .catch((error) => console.error(`[site] failed to load "${selector}"`, error));
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot, { once: true });
} else {
    boot();
}
