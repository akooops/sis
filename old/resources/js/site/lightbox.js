import GLightbox from 'glightbox';

/**
 * Lightbox. Any `[data-lightbox]` opts in; galleries group by `data-gallery`.
 */
export default function initLightbox() {
    return GLightbox({
        selector: '[data-lightbox]',
        touchNavigation: true,
        loop: true,
        openEffect: 'fade',
        closeEffect: 'fade',
        slideEffect: 'slide',
    });
}
