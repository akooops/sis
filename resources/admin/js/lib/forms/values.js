/**
 * The empty value a form field starts from, so nothing binds to undefined.
 *
 * Admin's own copy, and deliberately its own module rather than an import from
 * the renderer's element registry: that registry statically imports all sixteen
 * control components, so reaching into it for this one pure function would drag
 * the entire public form renderer into the admin bundle.
 *
 * The site keeps the same function next to its registry
 * (resources/site/js/lib/forms/elements.js). Both mirror config('forms.field_types'),
 * which is the actual contract — keep them in step when a type is added.
 */
export function emptyValue(type, field = null) {
    if (type === 'checkbox') return [];
    if (type === 'consent') return false;
    if (type === 'select' && field?.settings?.is_multiple) return [];
    if (type === 'file' && field?.settings?.is_multiple) return [];

    return '';
}
