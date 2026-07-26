/**
 * Category types — mirrors App\Enums\CategoryType.
 *
 * A category belongs to exactly one type, which is what scopes each content
 * form's picker: the Articles form only ever offers `articles` categories.
 * Adding a case to the PHP enum means adding it here too.
 */
export const CATEGORY_TYPE_LABELS = {
    articles: 'Articles',
    achievements: 'Achievements',
};

export const CATEGORY_TYPE_VARIANTS = {
    articles: 'primary',
    achievements: 'success',
};

export const CATEGORY_TYPE_OPTIONS = Object.entries(CATEGORY_TYPE_LABELS).map(([value, label]) => ({ value, label }));
