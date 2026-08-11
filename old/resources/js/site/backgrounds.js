/**
 * Elements that carry their image as `data-bg` get it applied as a background,
 * which keeps long CMS-driven URLs out of inline style attributes.
 */
export default function initBackgrounds(root = document) {
    root.querySelectorAll('[data-bg]').forEach((el) => {
        el.style.backgroundImage = `url("${el.dataset.bg}")`;
    });
}
