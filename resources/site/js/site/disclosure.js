/**
 * One delegated click handler for every show/hide interaction on the site:
 * the nav drawer, dropdowns, accordions and tab strips. Behaviour is declared
 * in markup, mirroring the `data-swiper` convention.
 *
 *   <button data-toggle="drawer"   data-target="#nav-drawer">…</button>
 *   <button data-toggle="dropdown" data-target="#services-menu">…</button>
 *   <button data-toggle="collapse" data-target="#menu-item-4">…</button>
 *   <button data-toggle="tab"      data-target="#panel-2">…</button>
 *   <button data-dismiss="drawer">…</button>
 *
 * A toggle gets `aria-expanded`, its target gets `aria-hidden`, and the panel
 * carries `.is-open` for CSS to animate.
 *
 * And every open or close announces itself: a bubbling `site:disclosure`
 * CustomEvent is dispatched ON THE PANEL, `detail.open` saying which way it
 * went. That is a public contract other scripts bind to — hiding a panel only
 * hides its subtree, so anything with state inside one has no other way to know
 * it was put away. This file stays ignorant of who is listening and what they do
 * with it; a panel is a box, not a feature.
 */

const OPEN = 'is-open';
const BODY_LOCK = 'has-drawer-open';

/** Exported so a listener cannot drift from the name by a typo. */
export const DISCLOSURE_EVENT = 'site:disclosure';

function target(trigger) {
    const selector = trigger.dataset.target;

    return selector ? document.querySelector(selector) : null;
}

function setExpanded(panel, expanded) {
    panel.classList.toggle(OPEN, expanded);
    panel.setAttribute('aria-hidden', String(!expanded));

    document
        .querySelectorAll(`[data-target="#${CSS.escape(panel.id)}"]`)
        .forEach((trigger) => trigger.setAttribute('aria-expanded', String(expanded)));

    // Announced here, the one place the state is actually written, and AFTER it
    // is written — so a listener that reads the panel back sees what the event
    // said, and no route through this file (collapse, drawer, dropdown, dismiss,
    // Escape) can change a panel without saying so.
    panel.dispatchEvent(new CustomEvent(DISCLOSURE_EVENT, { bubbles: true, detail: { open: expanded } }));
}

/**
 * Height animation for collapsible panels. CSS alone cannot transition to
 * `auto`, so the open height is measured and applied, then released back to
 * `auto` once the transition lands — that way nested content can still grow.
 */
function setCollapsed(panel, expanded) {
    panel.style.height = `${panel.scrollHeight}px`;

    if (expanded) {
        const release = () => {
            window.clearTimeout(panel.dataset.settleTimer);
            panel.removeEventListener('transitionend', onEnd);

            // A fast re-toggle may have closed it again in the meantime.
            if (panel.classList.contains(OPEN)) {
                panel.style.height = 'auto';
            }
        };

        const onEnd = (event) => {
            if (event.propertyName === 'height') {
                release();
            }
        };

        panel.addEventListener('transitionend', onEnd);

        // transitionend never fires in a backgrounded or non-compositing tab,
        // so fall back to releasing the height on a timer.
        panel.dataset.settleTimer = window.setTimeout(release, 400);
    } else {
        // Force a reflow so the browser has a pixel value to animate away from.
        panel.getBoundingClientRect();
        panel.style.height = '0px';
    }

    setExpanded(panel, expanded);
}

/* -------------------------------------------------------------------------- */
/* Drawer (the off-canvas navigation)                                         */
/* -------------------------------------------------------------------------- */

let lastFocused = null;

function openDrawer(panel) {
    lastFocused = document.activeElement;
    setExpanded(panel, true);
    document.body.classList.add(BODY_LOCK);

    const focusable = panel.querySelector(
        'input, button, a[href], select, textarea, [tabindex]:not([tabindex="-1"])'
    );

    focusable?.focus();
}

function closeDrawer(panel) {
    setExpanded(panel, false);

    if (!document.querySelector(`.drawer.${OPEN}`)) {
        document.body.classList.remove(BODY_LOCK);
    }

    lastFocused?.focus();
    lastFocused = null;
}

function closeAllDrawers() {
    document.querySelectorAll(`.drawer.${OPEN}`).forEach(closeDrawer);
}

/* -------------------------------------------------------------------------- */
/* Dropdown                                                                   */
/* -------------------------------------------------------------------------- */

function closeAllDropdowns(except = null) {
    document.querySelectorAll(`.dropdown-menu.${OPEN}`).forEach((menu) => {
        if (menu !== except) {
            setExpanded(menu, false);
        }
    });
}

/* -------------------------------------------------------------------------- */
/* Tabs                                                                       */
/* -------------------------------------------------------------------------- */

function activateTab(trigger, panel) {
    const strip = trigger.closest('[data-tabs]') || trigger.parentElement?.parentElement;

    strip?.querySelectorAll('[data-toggle="tab"]').forEach((other) => {
        const isCurrent = other === trigger;

        other.classList.toggle('is-active', isCurrent);
        other.setAttribute('aria-selected', String(isCurrent));

        const otherPanel = target(other);

        if (otherPanel) {
            otherPanel.hidden = !isCurrent;
        }
    });

    panel.hidden = false;
}

/* -------------------------------------------------------------------------- */

/* -------------------------------------------------------------------------- */
/* Alerts                                                                     */
/* -------------------------------------------------------------------------- */

function initAlerts(root) {
    root.querySelectorAll('[data-auto-dismiss]').forEach((alert) => {
        window.setTimeout(() => alert.remove(), 5000);
    });

    root.addEventListener('click', (event) => {
        event.target.closest('[data-dismiss-alert]')?.closest('[role="alert"]')?.remove();
    });
}

/**
 * A filter form that submits itself when a control changes.
 *
 * PROGRESSIVE ENHANCEMENT, not a replacement for the button: the form is a plain
 * GET form and works with this script blocked, which is why the submit button is
 * in the markup at all. It is hidden HERE rather than in the template, so a page
 * whose script failed to load still shows the way to apply the filter.
 */
function initAutoSubmit(root) {
    root.querySelectorAll('form[data-auto-submit]').forEach((form) => {
        form.querySelectorAll('[data-auto-submit-fallback]').forEach((el) => el.remove());

        form.addEventListener('change', () => form.requestSubmit());
    });
}

export default function initDisclosure(root = document) {
    initAlerts(root);
    initAutoSubmit(root);

    root.addEventListener('click', (event) => {
        const dismiss = event.target.closest('[data-dismiss]');

        if (dismiss) {
            const panel = target(dismiss) || dismiss.closest('dialog, .drawer, .dropdown-menu');

            if (panel) {
                event.preventDefault();

                if (panel.tagName === 'DIALOG') {
                    panel.close();
                } else if (panel.classList.contains('drawer')) {
                    closeDrawer(panel);
                } else {
                    setExpanded(panel, false);
                }

                return;
            }
        }

        const trigger = event.target.closest('[data-toggle]');

        if (!trigger) {
            // A click anywhere else dismisses open dropdowns.
            closeAllDropdowns();
            return;
        }

        const panel = target(trigger);

        if (!panel) {
            return;
        }

        event.preventDefault();

        switch (trigger.dataset.toggle) {
            case 'drawer':
                panel.classList.contains(OPEN) ? closeDrawer(panel) : openDrawer(panel);
                break;

            case 'dropdown': {
                const willOpen = !panel.classList.contains(OPEN);

                closeAllDropdowns(panel);
                setExpanded(panel, willOpen);
                break;
            }

            case 'collapse':
                setCollapsed(panel, !panel.classList.contains(OPEN));
                break;

            case 'tab':
                activateTab(trigger, panel);
                break;

            // Native <dialog>: the browser handles focus trapping and Escape.
            case 'dialog':
                panel.showModal();
                break;
        }
    });

    // Escape closes whatever is open, outermost last.
    root.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }

        const dropdown = document.querySelector(`.dropdown-menu.${OPEN}`);

        if (dropdown) {
            setExpanded(dropdown, false);
            return;
        }

        closeAllDrawers();
    });

    // Following a link inside the drawer should not leave it hanging open.
    document.querySelectorAll('.drawer a[href]').forEach((link) => {
        link.addEventListener('click', () => closeAllDrawers());
    });

    // Hover opens dropdowns on pointer devices, matching the old navbar.
    document.querySelectorAll('[data-toggle="dropdown"]').forEach((trigger) => {
        const panel = target(trigger);
        const host = trigger.closest('.dropdown');

        if (!panel || !host) {
            return;
        }

        host.addEventListener('mouseenter', () => {
            if (window.matchMedia('(hover: hover) and (width >= 992px)').matches) {
                closeAllDropdowns(panel);
                setExpanded(panel, true);
            }
        });

        host.addEventListener('mouseleave', () => {
            if (window.matchMedia('(hover: hover) and (width >= 992px)').matches) {
                setExpanded(panel, false);
            }
        });
    });
}
