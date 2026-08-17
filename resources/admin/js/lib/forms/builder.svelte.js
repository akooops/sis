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
import { emptyValue } from '@/lib/forms/values';

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

/**
 * One page's elements, each repeatable group followed by its own children.
 *
 * Children share the form-wide key namespace and the `unique(form_id, key)`
 * index with everything else, so anything asking "what keys are taken?" or
 * "where does this id live?" has to see them.
 */
function flatFields(page) {
    return (page?.fields ?? []).flatMap((field) => [field, ...(field.children ?? [])]);
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

    const allKeys = () => new Set(doc.pages.flatMap(flatFields).map((f) => f.key));

    /** Every field in the document, children included. */
    const allFields = () => doc.pages.flatMap(flatFields);

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

        /**
         * The selected element, its page, and — when it sits inside a repeatable
         * group — that group. `parent` is what tells the inspector to hide the
         * settings that only mean something on a page (Must be unique, Go to
         * page), rather than offering a switch the save would silently drop.
         */
        get selected() {
            if (!doc || !selectedId) return null;

            for (const page of doc.pages) {
                for (const field of page.fields) {
                    if (field.id === selectedId) return { field, page, parent: null };

                    const child = (field.children ?? []).find((c) => c.id === selectedId);

                    if (child) return { field: child, page, parent: field };
                }
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

        /**
         * Add an element to a page, or — with $parentId — inside a repeatable
         * group.
         */
        addField(type, pageId, parentId = null) {
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
                children: [],
            };

            if (parentId) {
                mapPages((p) => ({
                    ...p,
                    fields: p.fields.map((f) =>
                        f.id === parentId ? { ...f, children: [...(f.children ?? []), field] } : f,
                    ),
                }));
            } else {
                const target = pageId ?? doc.pages[0]?.id;
                mapPages((p) => (p.id === target ? { ...p, fields: [...p.fields, field] } : p));
            }

            selectedId = field.id;
            selectedPageId = null;

            return field;
        },

        removeField(fieldId) {
            mapPages((p) => ({
                ...p,
                fields: p.fields
                    .filter((f) => f.id !== fieldId)
                    .map((f) =>
                        (f.children ?? []).some((c) => c.id === fieldId)
                            ? { ...f, children: f.children.filter((c) => c.id !== fieldId) }
                            : f,
                    ),
            }));

            if (selectedId === fieldId) selectedId = null;
        },

        duplicateField(fieldId) {
            const page = doc.pages.find((p) => flatFields(p).some((f) => f.id === fieldId));
            if (!page) return;

            // The group this belongs to, when it is a child — a copy has to land
            // beside its original, not at the top of the page.
            const parent = page.fields.find((f) => (f.children ?? []).some((c) => c.id === fieldId)) ?? null;
            const siblings = parent ? (parent.children ?? []) : page.fields;
            const field = siblings.find((f) => f.id === fieldId);
            if (!field) return;

            const taken = allKeys();

            // Each clone claims its key BEFORE the next is minted: duplicating a
            // group mints one per child, and a shared `taken` snapshot would hand
            // every one of them the same name.
            const clone = (f) => {
                const key = makeKey(f.type, taken);
                taken.add(key);

                return {
                    ...structuredClone(f),
                    id: ulid(),
                    key,
                    options: (f.options ?? []).map((o) => ({ ...structuredClone(o), id: ulid() })),
                };
            };

            const copy = clone(field);

            // A duplicated group needs duplicated CHILDREN. Sharing their ids
            // would make one save write the same rows twice, and whichever group
            // was written second would take the children off the first.
            copy.children = (field.children ?? []).map(clone);

            const at = siblings.findIndex((f) => f.id === fieldId) + 1;
            const next = [...siblings.slice(0, at), copy, ...siblings.slice(at)];

            if (parent) {
                this.setChildren(parent.id, next);
            } else {
                this.setFields(page.id, next);
            }

            selectedId = copy.id;
            selectedPageId = null;
        },

        patchField(fieldId, patch) {
            mapPages((p) => ({
                ...p,
                fields: p.fields.map((f) => {
                    if (f.id === fieldId) return { ...f, ...patch };

                    if (!(f.children ?? []).some((c) => c.id === fieldId)) return f;

                    return {
                        ...f,
                        children: f.children.map((c) => (c.id === fieldId ? { ...c, ...patch } : c)),
                    };
                }),
            }));
        },

        /** Replace one page's field list — what dnd finalize hands back. */
        setFields(pageId, fields) {
            mapPages((p) => (p.id === pageId ? { ...p, fields } : p));
        },

        /** The same, for the zone inside one repeatable group. */
        setChildren(fieldId, children) {
            mapPages((p) => ({
                ...p,
                fields: p.fields.map((f) => (f.id === fieldId ? { ...f, children } : f)),
            }));
        },

        /*
         * No moveFieldToPage(). Its only callers were the canvas card's
         * "Move to…" select and the inspector's Page field, both removed —
         * a cross-page move is a DRAG, and svelte-dnd-action does it through
         * setFields() on each zone rather than through a move method.
         */

        /**
         * Step a field among its SIBLINGS. The keyboard path — drag is not the
         * only way in.
         *
         * Siblings, not "the page": a child steps within its group and stops at
         * its boundary, the same way a top-level field stops at the page's. There
         * is no keyboard route in or out of a group, for the same reason there is
         * none between pages — that move is a drag.
         */
        stepField(fieldId, delta) {
            const swap = (list) => {
                const from = list.findIndex((f) => f.id === fieldId);
                if (from < 0) return null;

                const to = from + delta;
                if (to < 0 || to >= list.length) return list;

                const next = [...list];
                [next[from], next[to]] = [next[to], next[from]];

                return next;
            };

            mapPages((p) => {
                const fields = swap(p.fields);

                if (fields) return { ...p, fields };

                return {
                    ...p,
                    fields: p.fields.map((f) => {
                        const children = swap(f.children ?? []);

                        return children ? { ...f, children } : f;
                    }),
                };
            });
        },

        /* ---------------- options ---------------- */

        addOption(fieldId) {
            const field = allFields().find((f) => f.id === fieldId);
            if (!field) return;

            const n = (field.options?.length ?? 0) + 1;
            this.patchField(fieldId, {
                options: [...(field.options ?? []), makeOption(doc, `option_${n}`, `Option ${n}`)],
            });
        },

        removeOption(fieldId, optionId) {
            const field = allFields().find((f) => f.id === fieldId);
            if (!field) return;

            this.patchField(fieldId, { options: (field.options ?? []).filter((o) => o.id !== optionId) });
        },

        patchOption(fieldId, optionId, patch) {
            const field = allFields().find((f) => f.id === fieldId);
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

    // Applied to a page field and to a group child alike — a child is an
    // ordinary element and every input the inspector binds to it needs the same
    // locale keys present.
    const field = (f) => ({
        ...f,
        settings: f.settings ?? {},
        validation: f.validation ?? {},
        label: fill(f.label),
        placeholder: fill(f.placeholder),
        value: fill(f.value),
        content: fill(f.content),
        options: (f.options ?? []).map((o) => ({ ...o, label: fill(o.label) })),
        children: (f.children ?? []).map((c) => field(c)),
    });

    return {
        ...tree,
        title: fill(tree.title),
        description: fill(tree.description),
        content: fill(tree.content),
        confirmation_message: fill(tree.confirmation_message),
        pages: (tree.pages ?? []).map((page) => ({
            ...page,
            title: fill(page.title),
            fields: (page.fields ?? []).map(field),
        })),
    };
}

/** Strip the drag library's shadow items and send position as order. */
function serialise(doc) {
    // Shadow items exist in a group's zone too, and one sent to the server would
    // fail `ulid` on an id the library invented.
    const real = (list) => (list ?? []).filter((f) => !f['isDndShadowItem']);

    const field = (f) => ({
        id: f.id,
        type: f.type,
        key: f.key,
        is_required: !!f.is_required,
        is_unique: !!f.is_unique,
        settings: f.settings ?? {},
        validation: f.validation ?? {},
        target_form_page_id: f.target_form_page_id || null,
        css_id: f.css_id || null,
        css_class: f.css_class || null,
        label: f.label,
        placeholder: f.placeholder,
        value: f.value,
        content: f.content,
        options: (f.options ?? []).map((o) => ({
            id: o.id,
            value: o.value,
            is_default: !!o.is_default,
            label: o.label,
        })),
        children: real(f.children).map(child),
    });

    // One level: a group cannot hold a group, so a child sends no children of
    // its own. FieldTypeRegistry::childCodes() is what enforces that server-side.
    const child = (c) => {
        const { children, ...rest } = field(c);

        return rest;
    };

    return doc.pages.map((page) => ({
        id: page.id,
        name: page.name,
        is_interstitial: !!page.is_interstitial,
        css_id: page.css_id || null,
        css_class: page.css_class || null,
        title: page.title,
        fields: real(page.fields).map(field),
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
        // The optional children segment comes BEFORE the `rest` capture, so a
        // child's own error lands on the child's card rather than being read as
        // an attribute called "children.2.key" on its group.
        const m = path.match(/^pages\.(\d+)(?:\.fields\.(\d+))?(?:\.children\.(\d+))?(?:\.(.+))?$/);

        if (!m) {
            out.form.push(message);
            continue;
        }

        const [, p, f, c, rest] = m;
        const page = doc.pages[Number(p)];

        if (f === undefined) {
            out[page?.id ?? 'form'] ??= {};
            out[page?.id ?? 'form'][rest ?? 'page'] = message;
            continue;
        }

        const parent = page?.fields?.[Number(f)];
        const field = c === undefined ? parent : parent?.children?.[Number(c)];
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
