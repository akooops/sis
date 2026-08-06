/**
 * Locale resolution for form schemas.
 *
 * Every translatable value on a form arrives as a `{ locale: value }` map, not a
 * resolved string, because the builder swaps the whole canvas between locales
 * without refetching. The public page holds exactly the same shape and just
 * never changes `locale` after mount — one renderer, one code path.
 *
 * A plain string is accepted too, so a caller that has already resolved a value
 * server-side does not have to wrap it.
 */
export function translate(map, locale, fallback = null) {
    if (map === null || map === undefined) return '';
    if (typeof map === 'string') return map;
    if (typeof map !== 'object') return String(map);

    const direct = map[locale];
    if (direct !== undefined && direct !== null && direct !== '') return direct;

    // Falling back is deliberate: a half-translated form should show the
    // default language rather than a blank label nobody can act on.
    if (fallback && fallback !== locale) {
        const value = map[fallback];
        if (value !== undefined && value !== null && value !== '') return value;
    }

    return '';
}

/**
 * Locales written right-to-left.
 *
 * A last-resort fallback, not the source of truth: the direction properly comes
 * from the language row's `is_rtl`, which an admin controls. This exists because
 * a schema that simply forgot to carry it would otherwise render Arabic
 * left-to-right — visibly broken, and silent.
 */
const RTL_LOCALES = new Set(['ar', 'he', 'fa', 'ur', 'ps', 'sd', 'ug', 'yi', 'dv', 'ckb']);

/** Text direction for a locale, honouring an explicit schema flag first. */
export function directionFor(locale, explicit = null) {
    if (explicit === 'rtl' || explicit === 'ltr') return explicit;
    if (explicit === true) return 'rtl';
    if (explicit === false) return 'ltr';

    // Match on the language subtag, so ar-DZ counts as Arabic.
    const base = String(locale ?? '').toLowerCase().split(/[-_]/)[0];

    return RTL_LOCALES.has(base) ? 'rtl' : 'ltr';
}

/** Whether a locale map has anything for this locale specifically. */
export function hasTranslation(map, locale) {
    if (!map || typeof map !== 'object') return !!map;

    const value = map[locale];

    return value !== undefined && value !== null && value !== '';
}
