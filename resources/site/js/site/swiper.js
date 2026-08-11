import Swiper from 'swiper';
import {
    A11y,
    Autoplay,
    EffectFade,
    Keyboard,
    Navigation,
    Pagination,
} from 'swiper/modules';

/**
 * Every slider on the site is declared in markup, not in JS:
 *
 *   <div class="swiper" data-swiper='{"slidesPerView": 1, "loop": true}'>
 *       <div class="swiper-wrapper"> … </div>
 *       <div class="swiper-controls">
 *           <div class="swiper-navigation">
 *               <div class="swiper-button-prev"></div>
 *               <div class="swiper-button-next"></div>
 *           </div>
 *           <div class="swiper-pagination"></div>
 *       </div>
 *   </div>
 *
 * Navigation and pagination wire themselves up when the elements are present,
 * so `data-swiper` only ever carries the options that differ from the defaults.
 */

const DEFAULTS = {
    slidesPerView: 1,
    spaceBetween: 30,
    speed: 600,
    grabCursor: true,
    watchOverflow: true,
};

function readOptions(el) {
    const raw = el.dataset.swiper;

    if (!raw) {
        return {};
    }

    try {
        return JSON.parse(raw);
    } catch (error) {
        console.warn('[swiper] invalid data-swiper JSON on', el, error);
        return {};
    }
}

function build(el) {
    const options = { ...DEFAULTS, ...readOptions(el) };
    const modules = [A11y, Keyboard];

    // `data-swiper='{"navigation": false}'` opts a slider out of arrows entirely.
    const next = el.querySelector('.swiper-button-next');
    const prev = el.querySelector('.swiper-button-prev');

    if (options.navigation === false) {
        el.querySelector('.swiper-navigation')?.remove();
    } else if (next && prev) {
        modules.push(Navigation);
        options.navigation = { nextEl: next, prevEl: prev };
    }

    const pagination = el.querySelector('.swiper-pagination');

    if (pagination) {
        modules.push(Pagination);
        options.pagination = {
            el: pagination,
            clickable: true,
            ...(options.pagination || {}),
        };
    }

    if (options.autoplay) {
        modules.push(Autoplay);
    }

    if (options.effect === 'fade') {
        modules.push(EffectFade);
        options.fadeEffect = { crossFade: true, ...(options.fadeEffect || {}) };
    }

    // Swiper reads direction from the option, not from the inherited `dir`.
    if (!options.direction) {
        options.rtl = document.documentElement.dir === 'rtl';
    }

    options.keyboard = { enabled: true, onlyInViewport: true };
    options.a11y = { enabled: true };
    options.modules = modules;

    return new Swiper(el, options);
}

export default function initSwipers(root = document) {
    return Array.from(root.querySelectorAll('[data-swiper]')).map(build);
}
