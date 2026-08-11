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
 * NOTE the form marker. It is `[data-sisf]`, wrapped around the content of ALL
 * FOUR site::forms.* views, and NOT `[data-sisf-root]`. The thanks page has no
 * mount root but is the only page that knows a submission completed, so keying
 * on the root would never load the module there and the conversion event would
 * stop firing with no error to notice.
 */
const ON_DEMAND = [
    ['[data-sisf]', () => import('./site/forms')],
    ['[data-island]', () => import('./site/islands')],
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
