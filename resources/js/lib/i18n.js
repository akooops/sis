/**
 * Frontend i18n, driven by the `i18n` Inertia shared prop
 * ({ locale, dir, area, messages }). Messages are the flattened admin/user
 * catalog loaded from Laravel lang files (see HandleInertiaRequests).
 *
 * Usage in a component:
 *   import { t, dir, locale } from '@/lib/i18n';
 *   <h1>{$t('users.title')}</h1>            // dot-path into the catalog
 *   <p>{$t('common.table.showing', { from, to, total })}</p>   // :placeholder replacement
 *
 * Everything is reactive: an Inertia reload with a new locale swaps the catalog,
 * flips `$dir`, and re-renders translated strings automatically.
 */

import { derived } from 'svelte/store';
import { page } from '@inertiajs/svelte';
import { setDateLocale } from './date';

/** Resolve a dot-path (e.g. "common.actions.save") within a nested object. */
function resolve(messages, key) {
    if (!messages || !key) return undefined;
    return key.split('.').reduce((acc, part) => (acc == null ? undefined : acc[part]), messages);
}

/** Replace :name placeholders with values from `replacements`. */
function interpolate(text, replacements) {
    if (typeof text !== 'string' || !replacements) return text;
    return text.replace(/:(\w+)/g, (match, name) =>
        Object.prototype.hasOwnProperty.call(replacements, name) ? String(replacements[name]) : match,
    );
}

/** Reactive translator: `$t('path.to.key', { placeholder })`. Falls back to the key. */
export const t = derived(page, ($page) => {
    const messages = $page?.props?.i18n?.messages ?? {};
    return (key, replacements) => {
        const value = resolve(messages, key);
        if (value === undefined || value === null) return key;
        return interpolate(value, replacements);
    };
});

/** Reactive current locale (e.g. 'en', 'ar'). */
export const locale = derived(page, ($page) => $page?.props?.i18n?.locale ?? 'en');

/** Reactive text direction ('ltr' | 'rtl'). */
export const dir = derived(page, ($page) => $page?.props?.i18n?.dir ?? 'ltr');

/** Reactive active translation area ('admin' | 'user'). */
export const area = derived(page, ($page) => $page?.props?.i18n?.area ?? 'admin');

// Keep the <html> attributes and the date formatter in sync with the locale so
// direction + date localization follow the shared prop without per-page wiring.
if (typeof document !== 'undefined') {
    let current;
    page.subscribe(($page) => {
        const info = $page?.props?.i18n;
        if (!info || info.locale === current) return;
        current = info.locale;
        setDateLocale(info.locale);
        document.documentElement.setAttribute('lang', info.locale);
        document.documentElement.setAttribute('dir', info.dir ?? 'ltr');
    });
}
