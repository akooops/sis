import AOS from 'aos';

/**
 * Scroll reveal. Markup opts in per element with `data-aos="fade-up"`, so
 * there is nothing to configure here beyond the shared timing.
 */
export default function initReveal() {
    AOS.init({
        duration: 700,
        easing: 'ease-out-cubic',
        once: true,
        offset: 60,
        disable: () => window.matchMedia('(prefers-reduced-motion: reduce)').matches,
    });
}
