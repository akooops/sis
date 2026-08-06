/**
 * Portal action — moves a node to document.body so overlays (modals, drawers,
 * dropdowns) are never clipped by a transformed/overflow-hidden ancestor.
 *
 *   <div use:portal>…</div>
 */
export function portal(node, target = 'body') {
    const host = typeof target === 'string' ? document.querySelector(target) : target;
    host?.appendChild(node);
    return {
        destroy() {
            node.parentNode?.removeChild(node);
        },
    };
}
