/**
 * Tabs that switch in place.
 *
 * PROGRESSIVE ENHANCEMENT, NOT A SPA. Every tab is a real link carrying its own
 * URL (the programme page's `?stream=`), and the server renders EVERY panel,
 * with the inactive ones `hidden`. With JavaScript off, or before this loads, a
 * click is an ordinary navigation to a page that opens on the right tab, and
 * each tab keeps an indexable URL. This module only stops that navigation and
 * swaps the panels instead.
 *
 * The address bar still follows the click, through replaceState rather than
 * pushState: a shared or reloaded link opens on the tab that was showing, but
 * flipping between tabs doesn't fill the Back button with entries that all
 * look like the same page.
 *
 * Markup contract:
 *   [data-tabs]                 the tab strip (the selector site.js loads on)
 *     a[data-tab="<key>"]       a tab, with its real URL in href
 *   [data-tab-panel="<key>"]    its panel, anywhere on the page
 */
export default function initTabs() {
    document.querySelectorAll('[data-tabs]').forEach(setUp);
}

function setUp(strip) {
    const tabs = [...strip.querySelectorAll('[data-tab]')];

    if (tabs.length < 2) {
        return;
    }

    const panelFor = (tab) => document.querySelector(`[data-tab-panel="${CSS.escape(tab.dataset.tab)}"]`);

    function activate(tab, { focus = false } = {}) {
        for (const other of tabs) {
            const selected = other === tab;

            other.classList.toggle('is-active', selected);
            other.setAttribute('aria-selected', selected ? 'true' : 'false');
            // Roving tabindex: Tab moves INTO the strip once, arrows move within.
            other.tabIndex = selected ? 0 : -1;

            const panel = panelFor(other);

            if (panel) {
                panel.hidden = !selected;
            }
        }

        if (focus) {
            tab.focus();
        }

        history.replaceState(history.state, '', tab.href);
    }

    strip.addEventListener('click', (event) => {
        const tab = event.target.closest('[data-tab]');

        // Let a modified click through, so ctrl/cmd-click still opens the tab's
        // own URL in a new window.
        if (!tab || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return;
        }

        event.preventDefault();
        activate(tab);
    });

    // Arrow, Home and End keys, per the ARIA tabs pattern. Left and right swap
    // meaning in RTL so the arrow still points where the next tab visually is.
    strip.addEventListener('keydown', (event) => {
        const index = tabs.indexOf(document.activeElement);

        if (index === -1) {
            return;
        }

        const rtl = getComputedStyle(strip).direction === 'rtl';
        const step = { ArrowRight: rtl ? -1 : 1, ArrowLeft: rtl ? 1 : -1 }[event.key];

        let next = null;

        if (step) {
            next = tabs[(index + step + tabs.length) % tabs.length];
        } else if (event.key === 'Home') {
            next = tabs[0];
        } else if (event.key === 'End') {
            next = tabs[tabs.length - 1];
        }

        if (next) {
            event.preventDefault();
            activate(next, { focus: true });
        }
    });

    // Sync the roving tabindex with what the server rendered as active.
    tabs.forEach((tab) => {
        tab.tabIndex = tab.getAttribute('aria-selected') === 'true' ? 0 : -1;
    });
}
