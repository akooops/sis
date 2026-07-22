/**
 * Shared notification state — the unread count that drives the topbar bell badge.
 *
 * Rune-backed module state (`.svelte.js`) so any component reading `unread.count`
 * updates reactively: the bell seeds it from the Inertia shared prop on first
 * paint, its poll keeps it fresh, and marking read decrements it here so the
 * badge reacts everywhere at once.
 */

import { api } from './api/client';

let count = $state(0);

export const unread = {
    get count() {
        return count;
    },
    /** Set the absolute count (from the server). */
    set(n) {
        count = Math.max(0, Number(n) || 0);
    },
    /** Optimistically drop the count (after marking one/some read). */
    dec(n = 1) {
        count = Math.max(0, count - n);
    },
    reset() {
        count = 0;
    },
};

/** Refetch the unread count from the API; returns the new value. */
export async function refreshUnread() {
    try {
        const data = await api.get(route('api.v1.admin.notifications.unread-count'));
        if (data && typeof data.unread_count === 'number') {
            unread.set(data.unread_count);
        }
    } catch {
        // Keep the last known count on a transient failure.
    }
    return unread.count;
}

/** Icon for an inbox row: the notification's own icon, else a bell fallback. */
export function notificationIcon(row) {
    return row?.icon || 'ki-notification-status';
}
