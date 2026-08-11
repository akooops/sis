import '../css/site.css';

import initBackgrounds from './site/backgrounds';
import initDisclosure from './site/disclosure';
import initHeader from './site/header';
import initReveal from './site/aos';
import initSwipers from './site/swiper';

/**
 * Public-site entry point. Everything is declared in markup via data
 * attributes, so this is only ever a list of what to switch on.
 *
 * Anything that pulls in a heavy library is imported on demand and only when
 * the page actually carries its attribute, so a page that does not use one
 * downloads nothing for it.
 */
const ON_DEMAND = [
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
    initSwipers();
    initReveal();

    ON_DEMAND.forEach(([selector, load]) => {
        if (document.querySelector(selector)) {
            load().then((module) => module.default());
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot, { once: true });
} else {
    boot();
}
