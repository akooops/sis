/**
 * KTUI init helper. KTComponents.init() re-scans the DOM for data-kt-* widgets
 * (sidebar accordion + collapse, sticky header, topbar dropdowns, theme switch).
 * Safe to call repeatedly — it only initialises not-yet-initialised elements.
 */
import { KTComponents } from '@keenthemes/ktui/core';

export function initKt() {
    // rAF so the freshly-mounted DOM is committed before KTUI scans it.
    requestAnimationFrame(() => {
        try {
            KTComponents.init();
        } catch {
            /* ignore */
        }
    });
}
