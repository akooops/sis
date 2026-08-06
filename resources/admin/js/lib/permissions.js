/**
 * Permission checks driven by Inertia shared props (`auth.permissions`,
 * `auth.enable_permissions`). Replaces the old `window.hasPermission` global.
 */

import { page } from '@inertiajs/svelte';
import { get } from 'svelte/store';

/** The authenticated user from shared props, or null. */
export function authUser() {
    return get(page).props?.auth?.user ?? null;
}

/**
 * Whether the current user holds a permission code. When permission
 * enforcement is disabled server-side, everything is allowed.
 * @param {string} permission
 * @returns {boolean}
 */
export function hasPermission(permission) {
    const auth = get(page).props?.auth;
    if (!auth) return false;
    if (!auth.enable_permissions) return true;
    return Array.isArray(auth.permissions) && auth.permissions.includes(permission);
}

/**
 * Whether the user holds *any* of the given permission codes.
 * @param {string[]} permissions
 */
export function hasAnyPermission(permissions) {
    return permissions.some((p) => hasPermission(p));
}
