/**
 * Header behaviour: the navbar shrinks and gains a blurred backdrop once the
 * page scrolls, and the scroll-to-top ring fills as you go. Both read the same
 * scroll position, so they share one rAF-throttled listener.
 */

const SCROLLED = 'is-scrolled';
const VISIBLE = 'is-visible';

function initStickyHeader(header) {
    if (!header) {
        return () => {};
    }

    let scrolled = false;

    return (top) => {
        const shouldBeScrolled = top > 0;

        if (shouldBeScrolled !== scrolled) {
            scrolled = shouldBeScrolled;
            header.classList.toggle(SCROLLED, scrolled);
        }
    };
}

function initScrollTop(widget) {
    if (!widget) {
        return () => {};
    }

    const path = widget.querySelector('path');

    if (!path) {
        return () => {};
    }

    const length = path.getTotalLength();

    path.style.strokeDasharray = `${length} ${length}`;
    path.style.strokeDashoffset = length;
    path.getBoundingClientRect();
    path.style.transition = 'stroke-dashoffset 10ms linear';

    widget.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    return (top) => {
        const height = document.documentElement.scrollHeight - window.innerHeight;
        const progress = height > 0 ? length - (top * length) / height : length;

        path.style.strokeDashoffset = progress;
        widget.classList.toggle(VISIBLE, top > 150);
    };
}

export default function initHeader() {
    const onScroll = [
        initStickyHeader(document.querySelector('[data-sticky-header]')),
        initScrollTop(document.querySelector('.scroll-top')),
    ];

    let ticking = false;

    const update = () => {
        const top = window.pageYOffset || document.documentElement.scrollTop;

        onScroll.forEach((fn) => fn(top));
        ticking = false;
    };

    window.addEventListener(
        'scroll',
        () => {
            if (!ticking) {
                window.requestAnimationFrame(update);
                ticking = true;
            }
        },
        { passive: true }
    );

    update();
}
