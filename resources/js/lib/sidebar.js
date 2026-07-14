/**
 * Sidebar theme — a light/dark mode for the sidebar that is INDEPENDENT of the
 * page dark mode (Metronic demo1 behaviour). Persisted in localStorage; toggled
 * from the Topbar and consumed by the Sidebar (which stamps a scoped `dark`
 * class on its root so the theme tokens flip only inside the sidebar).
 */
import { writable } from 'svelte/store';

const KEY = 'sidebar-theme';

function initial() {
    if (typeof localStorage === 'undefined') return 'light';
    return localStorage.getItem(KEY) === 'dark' ? 'dark' : 'light';
}

export const sidebarTheme = writable(initial());

if (typeof window !== 'undefined') {
    sidebarTheme.subscribe((value) => {
        try {
            localStorage.setItem(KEY, value);
        } catch {
            /* ignore */
        }
    });
}

export function toggleSidebarTheme() {
    sidebarTheme.update((value) => (value === 'dark' ? 'light' : 'dark'));
}

/**
 * Accordion open/closed state, keyed by group `labelKey`. Lives at module scope
 * (persisted) so it SURVIVES the sidebar being remounted on every Inertia visit
 * — otherwise the active group re-expands (and re-animates) on each navigation.
 * A group with no stored value defaults to open when it's the active section.
 */
const EXPANDED_KEY = 'sidebar-expanded';

function initialExpanded() {
    if (typeof localStorage === 'undefined') return {};
    try {
        return JSON.parse(localStorage.getItem(EXPANDED_KEY) ?? '{}') || {};
    } catch {
        return {};
    }
}

export const sidebarExpanded = writable(initialExpanded());

if (typeof window !== 'undefined') {
    sidebarExpanded.subscribe((value) => {
        try {
            localStorage.setItem(EXPANDED_KEY, JSON.stringify(value));
        } catch {
            /* ignore */
        }
    });
}
