/**
 * Locale resolution for form schemas — admin's own copy.
 *
 * Every translatable value on a form arrives as a `{ locale: value }` map, not a
 * resolved string, because the builder swaps the whole canvas between locales
 * without refetching. A plain string is accepted too, so a caller that has
 * already resolved a value server-side does not have to wrap it.
 *
 * The public renderer carries its own copy (resources/site/js/lib/forms/i18n.js)
 * with the direction helpers it needs. Sharing one module across the two sides
 * would put a public-page import inside the admin bundle for the sake of one
 * function; the `{ locale: value }` shape is a server contract, so the copies
 * only have to agree with the API, not with each other.
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
