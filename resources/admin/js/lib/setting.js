/**
 * Setting types — mirrors Setting::TYPES and the catalogue in config/settings.php.
 *
 * Five types plus a multiplicity flag, not ten types: an array of strings is
 * text + is_multiple and a multi-select is select + is_multiple. Both the table
 * cell and the edit form lean on that — they repeat one control instead of
 * carrying a second parallel branch for every shape.
 */
export const SETTING_TYPE_LABELS = {
    text: 'Text',
    number: 'Number',
    date: 'Date',
    select: 'Choice',
    model: 'Record',
};

/** The five codes, for the index's type filter. */
export const SETTING_TYPE_OPTIONS = Object.entries(SETTING_TYPE_LABELS).map(([value, label]) => ({
    value,
    label,
}));

/** Type as a row shows it — the badge has to say `is_multiple` too, or a list and a scalar read alike. */
export function settingTypeLabel(setting) {
    const label = SETTING_TYPE_LABELS[setting?.type] ?? setting?.type ?? '';

    return setting?.is_multiple ? `${label} list` : label;
}

/**
 * Which records a model-typed setting may point at, taken straight off the row:
 * the server ships the setting's config/settings.php `models` entry with it, so
 * the picker's route and label column stay config. A map here would have to grow
 * every time a model becomes pickable, and a stale one is a picker that silently
 * loads nothing.
 */
export function settingModelConfig(setting) {
    const entry = setting?.model;
    if (!entry?.resource) return null;

    return {
        name: entry.name ?? setting.model_type,
        resource: entry.resource,
        labelKey: entry.label ?? 'name',
    };
}

/** Every stored value as a list — a single-valued setting is a one-item list, an unset one is empty. */
export function settingValues(setting) {
    const value = setting?.value;
    if (value === null || value === undefined || value === '') return [];

    return (Array.isArray(value) ? value : [value]).filter((v) => v !== null && v !== undefined && v !== '');
}

/** Nothing stored. Shown as an explicit "Not set" — a blank cell reads as a rendering bug. */
export function isSettingUnset(setting) {
    return settingValues(setting).length === 0;
}

/** A choice's label, falling back to the raw value for an option that has left config. */
export function settingOptionLabel(setting, value) {
    return (setting?.options ?? []).find((o) => String(o.value) === String(value))?.label ?? String(value ?? '');
}

/**
 * What the server resolved for a model setting, in the Select's option shape.
 * Seeding a picker with these is what makes an edit form open on names instead
 * of ULIDs, without a second round trip.
 *
 * The entries are objects — {id, label, missing}, straight off SettingResolver —
 * so a record that has been deleted keeps its place in the list and carries the
 * tombstone the resolver wrote into `label`.
 */
export function settingResolvedOptions(setting) {
    return (setting?.resolved ?? []).map((r) => ({ value: r.id, label: r.label }));
}

/**
 * What the value reads as to a human: option labels for a choice, the resolved
 * record names for a reference, the raw scalars otherwise. Dates stay raw — the
 * table hands them to <DateTime>, which wants the ISO string.
 */
export function settingDisplay(setting) {
    if (setting?.type === 'model') {
        const resolved = settingResolvedOptions(setting);

        // A row that was never hydrated falls back to the raw ids: a reference
        // that exists must never render as "Not set".
        return resolved.length ? resolved.map((o) => o.label) : settingValues(setting).map(String);
    }

    if (setting?.type === 'select') {
        return settingValues(setting).map((v) => settingOptionLabel(setting, v));
    }

    return settingValues(setting).map(String);
}

/**
 * Which entries point at a record that is gone, aligned index-for-index with
 * settingDisplay(). The resolver already decided this — every resolved entry
 * carries `missing` — and the tombstone it writes into `label` still reads like
 * a name at a glance, so the badge that shows it has to say otherwise.
 *
 * Empty for every other type, and all-false on the fallback path, where the row
 * was never hydrated and nothing is known about what the ids point at.
 */
export function settingMissingFlags(setting) {
    if (setting?.type !== 'model') return [];

    const resolved = setting?.resolved ?? [];

    return resolved.length ? resolved.map((r) => !!r.missing) : settingValues(setting).map(() => false);
}
