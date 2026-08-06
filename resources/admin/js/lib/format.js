/**
 * Value formatting helpers (file sizes, currency, FormData building).
 *
 * Ported from the old `window.*` helpers in MainLayout.svelte so components
 * import them explicitly instead of relying on globals.
 */

import { page } from '@inertiajs/svelte';
import { get } from 'svelte/store';

/**
 * Human-readable file size, e.g. 10485760 -> "10 MB".
 * @param {number} bytes
 */
export function formatFileSize(bytes) {
    if (!bytes || bytes <= 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.min(Math.floor(Math.log(bytes) / Math.log(k)), sizes.length - 1);
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

/**
 * Build a FormData body from a plain object, applying the app's submission
 * conventions: `_method=PUT` on updates, `_id` fields always sent (even empty),
 * booleans as '1'/'0', arrays JSON-encoded, and empty/nullish scalars skipped.
 *
 * @param {Record<string, any>} form
 * @param {boolean} [update]
 * @returns {FormData}
 */
export function prepareFormData(form, update = false) {
    const formData = new FormData();

    if (update) {
        formData.append('_method', 'PUT');
    }

    if (form) {
        for (const key of Object.keys(form)) {
            const value = form[key];

            if (key.includes('_id')) {
                formData.append(key, value !== null && value !== undefined ? value : '');
            } else if (value === true || value === false) {
                formData.append(key, value ? '1' : '0');
            } else if (Array.isArray(value)) {
                formData.append(key, JSON.stringify(value));
            } else if (value !== null && value !== undefined && value !== '') {
                formData.append(key, value);
            }
        }
    }

    return formData;
}
