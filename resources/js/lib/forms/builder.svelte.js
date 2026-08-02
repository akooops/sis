/**
 * The builder document.
 *
 * ONE STATE CONTAINER, and the canvas, palette and inspector all read from it.
 * Cards stay stateless: per-row $state inside an {#each} swaps between rows when
 * the list reorders, which is exactly what a drag does.
 *
 * $state.raw, NOT $state. Three reasons, all of them things that bite:
 *  - svelte-dnd-action keeps its own shadow bookkeeping over the array it is
 *    given; a deep reactive proxy makes those identity checks unreliable.
 *  - structuredClone throws DataCloneError on a proxy, and the library clones.
 *  - a deep proxy re-renders the whole canvas on every keystroke in the
 *    inspector, because every nested read is a subscription.
 *
 * The cost is that every edit REPLACES the document rather than mutating it, so
 * every helper below is an immutable update. That is the trade, and it is worth
 * it — the alternative is a canvas that stutters and a drag library that
 * occasionally drops an item.
 */
import { api } from '@/lib/api/client';
import { ulid } from '@/lib/forms/ulid';
import { emptyValue } from '@/lib/forms/elements';

/** A blank page, ready for the canvas. */
function makePage(index, locales, defaultLocale) {
    return {
        id: ulid(),
        name: `Page ${index + 1}`,
        is_interstitial: false,
        css_id: null,
        css_class: null,
        title: seed(locales, defaultLocale, ''),
        fields: [],
    };
}

/** Every enabled locale needs a key before an input binds to it. */
function seed(locales, defaultLocale, value = '') {
    const out = {};
    for (const l of locales) out[l.code] = l.code === defaultLocale ? value : '';

    return out;
}

/** A unique machine key derived from the type, e.g. text_2. */
function makeKey(type, taken) {
    let n = 1;
    let key = type;

    while (taken.has(key)) {
        n += 1;
        key = `${type}_${n}`;
    }

    return key;
}

export function createBuilder() {
    let doc = $state.raw(null);
    let palette = $state.raw([]);
    let loading = $state(false);
    let saving = $state(false);
    let dirty = $state(false);
    let locale = $state(null);
    // TWO selections, never both: the settings panel shows an element OR a page,
    // and the one that is not showing must be cleared or the panel would have to
    // guess which the admin meant.
    let selectedId = $state(null);
    let selectedPageId = $state(null);
    let errors = $state.raw({});

    /** Replace the document and mark it dirty. Single funnel, so nothing forgets. */
    function commit(next, { touch = true } = {}) {
        doc = next;
        if (touch) dirty = true;
    }

    function mapPages(mapper) {
        commit({ ...doc, pages: doc.pages.map(mapper) });
    }

    const allKeys = () => new Set(doc.pages.flatMap((p) => p.fields.map((f) => f.key)));

    return {
        get doc() { return doc; },
        get pages() { return doc?.pages ?? []; },
        get palette() { return palette; },
        get locales() { return doc?.locales ?? []; },
        get defaultLocale() { return doc?.default_locale ?? 'en'; },
        get locale() { return locale ?? doc?.default_locale ?? 'en'; },
        get language() { return (doc?.locales ?? []).find((l) => l.code === this.locale) ?? null; },
        get loading() { return loading; },
        get saving() { return saving; },
        get dirty() { return dirty; },
        get locked() { return !!doc?.is_locked; },
        get errors() { return errors; },
        get selectedId() { return selectedId; },
        get selectedPageId() { return selectedPageId; },

        get selected() {
            if (!doc || !selectedId) return null;

            for (const page of doc.pages) {
                const field = page.fields.find((f) => f.id === selectedId);
                if (field) return { field, page };
            }

            return null;
        },

        /** The page whose own settings the panel is showing, if any. */
        get selectedPage() {
            if (!doc || !selectedPageId) return null;

            return doc.pages.find((p) => p.id === selectedPageId) ?? null;
        },

        setLocale(code) { locale = code; },
        select(id) { selectedId = id; if (id) selectedPageId = null; },
        selectPage(id) { selectedPageId = id; if (id) selectedId = null; },

        async load(formId) {
            loading = true;
            errors = {};

            try {
                const [tree, types] = await Promise.all([
                    api.get(route('api.v1.admin.forms.builder.show', formId)),
                    api.get(route('api.v1.admin.form-field-types.index')),
                ]);

                palette = types?.data ?? types ?? [];
                commit(normalise(tree), { touch: false });
                dirty = false;
                locale ??= tree?.default_locale ?? 'en';
            } finally {
                loading = false;
            }
        },

        async save() {
            if (!doc) return null;

            saving = true;
            errors = {};

            try {
                const url = route('api.v1.admin.forms.builder.update', doc.id);
                const saved = await api.put(url, { pages: serialise(doc) });

                commit(normalise(saved), { touch: false });
                dirty = false;

                return saved;
            } catch (e) {
                // 422 keys arrive as pages.0.fields.2.key — remap against what
                // was SENT, never against the current document, which the admin
                // may already have changed.
                if (e?.status === 422) errors = remapErrors(e.errors ?? {}, doc);

                throw e;
            } finally {
                saving = false;
            }
        },

        /* ---------------- structure ---------------- */

        addPage() {
            const page = makePage(doc.pages.length, doc.locales, doc.default_locale);
            commit({ ...doc, pages: [...doc.pages, page] });

            return page;
        },

        removePage(pageId) {
            if (doc.pages.length <= 1) return;

            const page = doc.pages.find((p) => p.id === pageId);

            commit({ ...doc, pages: doc.pages.filter((p) => p.id !== pageId) });

            // Nothing selected can outlive the page it was on.
            if (selectedPageId === pageId) selectedPageId = null;
            if (page?.fields.some((f) => f.id === selectedId)) selectedId = null;
        },

        patchPage(pageId, patch) {
            mapPages((p) => (p.id === pageId ? { ...p, ...patch } : p));
        },

        /** Reorder the pages themselves (dnd finalize on the page strip). */
        setPages(pages) {
            commit({ ...doc, pages });
        },

        addField(type, pageId) {
            const spec = palette.find((p) => p.code === type);
            if (!spec) return null;

            const taken = allKeys();
            const field = {
                id: ulid(),
                type,
                key: makeKey(type, taken),
                is_required: false,
                is_unique: false,
                settings: defaultsFor(spec.settings ?? []),
                validation: defaultsFor(spec.validations ?? []),
                target_form_page_id: null,
                css_id: null,
                css_class: null,
                label: seed(doc.locales, doc.default_locale, spec.label),
                placeholder: seed(doc.locales, doc.default_locale),
                value: seed(doc.locales, doc.default_locale),
                content: seed(doc.locales, doc.default_locale, spec.is_input ? '' : spec.label),
                options: spec.has_options ? [makeOption(doc, 'option_1', 'Option 1'), makeOption(doc, 'option_2', 'Option 2')] : [],
            };

            const target = pageId ?? doc.pages[0]?.id;
            mapPages((p) => (p.id === target ? { ...p, fields: [...p.fields, field] } : p));
            selectedId = field.id;
            selectedPageId = null;

            return field;
        },

        removeField(fieldId) {
            mapPages((p) => ({ ...p, fields: p.fields.filter((f) => f.id !== fieldId) }));
            if (selectedId === fieldId) selectedId = null;
        },

        duplicateField(fieldId) {
            const found = this.selected?.field?.id === fieldId ? this.selected : null;
            const page = doc.pages.find((p) => p.fields.some((f) => f.id === fieldId));
            const field = page?.fields.find((f) => f.id === fieldId);
            if (!field) return;

            const taken = allKeys();
            const copy = {
                ...structuredClone(field),
                id: ulid(),
                key: makeKey(field.type, taken),
                options: (field.options ?? []).map((o) => ({ ...structuredClone(o), id: ulid() })),
            };

            const at = page.fields.findIndex((f) => f.id === fieldId) + 1;
            mapPages((p) =>
                p.id === page.id
                    ? { ...p, fields: [...p.fields.slice(0, at), copy, ...p.fields.slice(at)] }
                    : p,
            );
            selectedId = copy.id;
            selectedPageId = null;
        },

        patchField(fieldId, patch) {
            mapPages((p) => ({
                ...p,
                fields: p.fields.map((f) => (f.id === fieldId ? { ...f, ...patch } : f)),
            }));
        },

        /** Replace one page's field list — what dnd finalize hands back. */
        setFields(pageId, fields) {
            mapPages((p) => (p.id === pageId ? { ...p, fields } : p));
        },

        moveFieldToPage(fieldId, pageId) {
            let moving = null;

            const stripped = doc.pages.map((p) => {
                const found = p.fields.find((f) => f.id === fieldId);
                if (found) moving = found;

                return found ? { ...p, fields: p.fields.filter((f) => f.id !== fieldId) } : p;
            });

            if (!moving) return;

            commit({
                ...doc,
                pages: stripped.map((p) => (p.id === pageId ? { ...p, fields: [...p.fields, moving] } : p)),
            });
        },

        /** Step a field within its page. The keyboard path — drag is not the only way in. */
        stepField(fieldId, delta) {
            mapPages((p) => {
                const from = p.fields.findIndex((f) => f.id === fieldId);
                if (from < 0) return p;

                const to = from + delta;
                if (to < 0 || to >= p.fields.length) return p;

                const fields = [...p.fields];
                [fields[from], fields[to]] = [fields[to], fields[from]];

                return { ...p, fields };
            });
        },

        /* ---------------- options ---------------- */

        addOption(fieldId) {
            const field = doc.pages.flatMap((p) => p.fields).find((f) => f.id === fieldId);
            if (!field) return;

            const n = (field.options?.length ?? 0) + 1;
            this.patchField(fieldId, {
                options: [...(field.options ?? []), makeOption(doc, `option_${n}`, `Option ${n}`)],
            });
        },

        removeOption(fieldId, optionId) {
            const field = doc.pages.flatMap((p) => p.fields).find((f) => f.id === fieldId);
            if (!field) return;

            this.patchField(fieldId, { options: (field.options ?? []).filter((o) => o.id !== optionId) });
        },

        patchOption(fieldId, optionId, patch) {
            const field = doc.pages.flatMap((p) => p.fields).find((f) => f.id === fieldId);
            if (!field) return;

            this.patchField(fieldId, {
                options: (field.options ?? []).map((o) => (o.id === optionId ? { ...o, ...patch } : o)),
            });
        },
    };
}

function makeOption(doc, value, label) {
    return {
        id: ulid(),
        value,
        is_default: false,
        label: seed(doc.locales, doc.default_locale, label),
    };
}

/** A driver-style schema's declared defaults, as a plain settings object. */
function defaultsFor(schema) {
    const out = {};

    for (const f of schema) {
        if (f.default !== null && f.default !== undefined) out[f.key] = f.default;
    }

    return out;
}

/**
 * Fill in anything the server left out, so nothing in the UI binds to undefined.
 * Runs on load AND after every save, because a save returns a fresh tree.
 */
function normalise(tree) {
    if (!tree) return null;

    const locales = tree.locales ?? [];
    const fill = (map) => {
        const out = { ...(map ?? {}) };
        for (const l of locales) out[l.code] ??= '';

        return out;
    };

    return {
        ...tree,
        title: fill(tree.title),
        description: fill(tree.description),
        content: fill(tree.content),
        confirmation_message: fill(tree.confirmation_message),
        pages: (tree.pages ?? []).map((page) => ({
            ...page,
            title: fill(page.title),
            fields: (page.fields ?? []).map((field) => ({
                ...field,
                settings: field.settings ?? {},
                validation: field.validation ?? {},
                label: fill(field.label),
                placeholder: fill(field.placeholder),
                value: fill(field.value),
                content: fill(field.content),
                options: (field.options ?? []).map((o) => ({ ...o, label: fill(o.label) })),
            })),
        })),
    };
}

/** Strip the drag library's shadow items and send position as order. */
function serialise(doc) {
    return doc.pages.map((page) => ({
        id: page.id,
        name: page.name,
        is_interstitial: !!page.is_interstitial,
        css_id: page.css_id || null,
        css_class: page.css_class || null,
        title: page.title,
        fields: page.fields
            .filter((f) => !f['isDndShadowItem'])
            .map((field) => ({
                id: field.id,
                type: field.type,
                key: field.key,
                is_required: !!field.is_required,
                is_unique: !!field.is_unique,
                settings: field.settings ?? {},
                validation: field.validation ?? {},
                target_form_page_id: field.target_form_page_id || null,
                css_id: field.css_id || null,
                css_class: field.css_class || null,
                label: field.label,
                placeholder: field.placeholder,
                value: field.value,
                content: field.content,
                options: (field.options ?? []).map((o) => ({
                    id: o.id,
                    value: o.value,
                    is_default: !!o.is_default,
                    label: o.label,
                })),
            })),
    }));
}

/**
 * `pages.0.fields.2.key` → `{ <fieldId>: { key: message } }`.
 *
 * Resolved against the document that was sent, so the message lands on the right
 * card even if the indices no longer mean the same thing.
 */
function remapErrors(raw, doc) {
    const out = { form: [] };

    for (const [path, messages] of Object.entries(raw)) {
        const message = Array.isArray(messages) ? messages[0] : messages;
        const m = path.match(/^pages\.(\d+)(?:\.fields\.(\d+))?(?:\.(.+))?$/);

        if (!m) {
            out.form.push(message);
            continue;
        }

        const [, p, f, rest] = m;
        const page = doc.pages[Number(p)];

        if (f === undefined) {
            out[page?.id ?? 'form'] ??= {};
            out[page?.id ?? 'form'][rest ?? 'page'] = message;
            continue;
        }

        const field = page?.fields?.[Number(f)];
        const id = field?.id ?? 'form';

        if (id === 'form') {
            out.form.push(message);
            continue;
        }

        out[id] ??= {};
        out[id][rest ?? 'field'] = message;
    }

    return out;
}

export { emptyValue };
