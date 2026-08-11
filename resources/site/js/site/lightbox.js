import GLightbox from 'glightbox';

/**
 * Lightbox.
 *
 * `[data-lightbox]` marks the CONTAINER — it is what site.js gates the dynamic
 * import on, so one attribute both loads the module and scopes it. GLightbox's
 * `selector`, though, has to match the LINKS it opens: pointed at the container
 * it bound to a <div> with no href and silently did nothing, which is why the
 * album galleries never opened. Hence the descendant selector, and `[href]` so a
 * stray anchor inside the grid cannot become a blank slide.
 *
 * Galleries still group by `data-gallery` on each link.
 */
export default function initLightbox() {
    return GLightbox({
        selector: '[data-lightbox] a[href]',
        touchNavigation: true,
        loop: true,
        openEffect: 'fade',
        closeEffect: 'fade',
        slideEffect: 'slide',
    });
}
