/**
 * Where a content link may point: the eight models with a public slug, plus a
 * plain external URL.
 *
 * Mirrors Banner::LINKABLE_TYPES — the key IS the MorphType alias the API
 * speaks, so a new sluggable model has to be added in both places. The route is
 * what ContentLinkInput searches; `name` is the label column all eight share.
 */
export const LINKABLE_RESOURCES = {
    page: { label: 'Page', route: 'api.v1.admin.pages.index', labelKey: 'name' },
    article: { label: 'Article', route: 'api.v1.admin.articles.index', labelKey: 'name' },
    achievement: { label: 'Achievement', route: 'api.v1.admin.achievements.index', labelKey: 'name' },
    album: { label: 'Album', route: 'api.v1.admin.albums.index', labelKey: 'name' },
    event: { label: 'Event', route: 'api.v1.admin.events.index', labelKey: 'name' },
    job_offer: { label: 'Job offer', route: 'api.v1.admin.job-offers.index', labelKey: 'name' },
    program: { label: 'Program', route: 'api.v1.admin.programs.index', labelKey: 'name' },
    stream: { label: 'Stream', route: 'api.v1.admin.streams.index', labelKey: 'name' },
};

/** Kind choices for a Select: every record type, then the external URL. */
export const LINKABLE_OPTIONS = [
    ...Object.entries(LINKABLE_RESOURCES).map(([value, { label }]) => ({ value, label })),
    { value: 'url', label: 'External URL' },
];

/** The kind a stored record is in: its alias, 'url', or null for no link. */
export function linkKind(record) {
    return record?.linkable_type ?? (record?.url ? 'url' : null);
}

/** Kind label — 'url' is a kind the picker offers, never a MorphType alias. */
export function linkKindLabel(type) {
    if (!type) return 'No link';

    return type === 'url' ? 'External URL' : (LINKABLE_RESOURCES[type]?.label ?? type);
}

/** What the link actually points at, for a table cell or a drawer row. */
export function linkTarget(record) {
    if (record?.url) return record.url;
    // The morph resolves to null once the target is deleted; the id survives it.
    if (record?.linkable) return record.linkable.name;

    return record?.linkable_id ?? null;
}
