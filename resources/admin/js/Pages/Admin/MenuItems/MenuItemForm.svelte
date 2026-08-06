<script>
    /**
     * Menu item create/edit.
     *
     * Create asks for the DEFAULT language's title only — there is nothing to
     * translate until the item exists. Edit splits into [Details | Translations],
     * matching every other content module.
     *
     * No `order` field by design: a new item goes last among its siblings, and both
     * position and nesting are set in the Reorder drawer. Parent is here only so a
     * child can be created without a second trip.
     */
    import Field from '@/components/form/Field.svelte';
    import Input from '@/components/form/Input.svelte';
    import Select from '@/components/form/Select.svelte';
    import ContentLinkInput from '@/components/form/ContentLinkInput.svelte';
    import Tabs from '@/components/ui/Tabs.svelte';
    import Button from '@/components/ui/Button.svelte';
    import { useForm } from '@/lib/api/useForm.svelte';
    import { api } from '@/lib/api/client';
    import { toast } from '@/lib/toast';
    import { linkKind } from '@/lib/linkable';

    let { menuItem = null, menuId = null, onsaved, oncancel } = $props();

    const editing = $derived(!!menuItem);

    let languages = $state([]);
    let activeTab = $state('details');
    let activeLocale = $state(null);
    let parents = $state([]);

    const form = useForm({
        // Seeded from the page filter so a drill-down does not re-ask.
        menu_id: menuItem?.menu_id ?? menuId ?? null,
        parent_id: menuItem?.parent_id ?? null,
        name: menuItem?.name ?? '',
        // The picker's kind, which is the morph alias plus a 'url' choice the
        // server does not know — payload() drops it again.
        linkable_type: linkKind(menuItem),
        linkable_id: menuItem?.linkable_id ?? null,
        url: menuItem?.url ?? '',
        title: menuItem ? { ...(menuItem.title ?? {}) } : '',
    });

    // Depth is capped at 2, so an item with children can never become one itself.
    const hasChildren = $derived(!!menuItem?.children?.length);

    const parentHint = $derived(
        hasChildren
            ? 'This item has children of its own, so it cannot be nested under another.'
            : 'Leave empty for a top-level item. Items nest one level only.',
    );

    const activeLanguage = $derived(languages.find((l) => l.code === activeLocale) ?? null);

    // One error line under the picker: the server may reject any of the three.
    const linkError = $derived(form.errors.url ?? form.errors.linkable_type ?? form.errors.linkable_id ?? null);

    // Plain, not $state: switching menu mid-fetch must not let the older response
    // land, and tracking this would re-run the effect that bumps it.
    let parentToken = 0;

    /** Any validation error under this locale, so a collapsed tab isn't a mystery. */
    function localeHasError(code) {
        return !!form.errors[`title.${code}`];
    }

    /**
     * The menu's top-level items — the only legal parents. The index has no
     * "roots only" filter, so every item is loaded and the roots picked out here;
     * a menu is a handful of rows, and the Reorder drawer already reads them all.
     */
    async function loadRoots(menu) {
        const url = route('api.v1.admin.menu-items.index');
        const all = [];
        let page = 1;
        let lastPage = 1;

        do {
            const res = await api.get(url, { per_page: 100, sort: 'name', page, filter: { menu_id: menu } });
            all.push(...(res?.data ?? []));
            lastPage = res?.meta?.last_page ?? 1;
            page += 1;
        } while (page <= lastPage);

        // An item may not be its own parent, and a child parents nothing.
        return all.filter((r) => !r.parent_id && r.id !== menuItem?.id);
    }

    // Reload on every menu change: a parent only means anything inside its menu.
    $effect(() => {
        const menu = form.data.menu_id;
        const mine = ++parentToken;
        parents = [];

        if (!menu) return;

        loadRoots(menu)
            .then((rows) => {
                if (mine === parentToken) parents = rows.map((r) => ({ value: r.id, label: r.name }));
            })
            .catch(() => {});
    });

    $effect(() => {
        api.get(route('api.v1.admin.languages.index'), { filter: { is_enabled: 1 }, per_page: 100 })
            .then((d) => {
                // Default language first — it is the one whose copy is required.
                languages = (d?.data ?? []).sort((a, b) => Number(b.is_default) - Number(a.is_default) || a.name.localeCompare(b.name));
                if (!activeLocale && languages.length) activeLocale = languages[0].code;
                // Every enabled locale needs a key before a field binds to it —
                // binding to an undefined key throws props_invalid_value.
                if (editing) {
                    for (const l of languages) {
                        if (form.data.title[l.code] === undefined) form.data.title[l.code] = '';
                    }
                }
            })
            .catch(() => {});
    });

    function payload(data) {
        const out = { ...data };

        // 'url' is a picker kind, not a morph alias; '' would fail the url rule
        // instead of clearing the link.
        if (out.linkable_type === 'url') out.linkable_type = null;
        out.url = out.url || null;

        return out;
    }

    async function submit(event) {
        event.preventDefault();
        const url = editing
            ? route('api.v1.admin.menu-items.update', menuItem.id)
            : route('api.v1.admin.menu-items.store');
        try {
            const res = await form.submit(editing ? 'put' : 'post', url, { transform: payload });
            if (res) {
                toast.success(editing ? 'Updated successfully.' : 'Created successfully.');
                onsaved?.();
            } else if (languages.some((l) => localeHasError(l.code))) {
                // The failing field may be behind a tab the user can't see.
                activeTab = 'translations';
                activeLocale = languages.find((l) => localeHasError(l.code))?.code ?? activeLocale;
            }
        } catch (err) {
            toast.error(err?.message ?? 'Something went wrong. Please try again.');
        }
    }
</script>

<form class="flex w-full flex-col gap-5" onsubmit={submit}>
    {#if editing}
        <Tabs
            tabs={[
                { id: 'details', label: 'Details', icon: 'ki-filled ki-document' },
                { id: 'translations', label: 'Translations', icon: 'ki-filled ki-flag' },
            ]}
            bind:active={activeTab}
        />
    {/if}

    {#if !editing || activeTab === 'details'}
        <div class="flex flex-col gap-5">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <Field
                    label="Menu"
                    error={form.errors.menu_id}
                    required
                    hint="Moving an item to another menu takes its children with it."
                >
                    <!-- Clears the parent: it belongs to the menu being left. -->
                    <Select
                        resource="api.v1.admin.menus.index"
                        bind:value={form.data.menu_id}
                        labelKey="name"
                        placeholder="Search menus…"
                        invalid={!!form.errors.menu_id}
                        onchange={() => (form.data.parent_id = null)}
                        initialOptions={menuItem?.menu ? [{ value: menuItem.menu.id, label: menuItem.menu.name }] : []}
                    />
                </Field>
                <Field label="Parent" error={form.errors.parent_id} hint={parentHint}>
                    <Select
                        options={parents}
                        bind:value={form.data.parent_id}
                        placeholder="Top level"
                        disabled={!form.data.menu_id || hasChildren}
                        invalid={!!form.errors.parent_id}
                    />
                </Field>
            </div>

            <Field label="Name" error={form.errors.name} required hint="Internal label — not shown to the public.">
                <Input bind:value={form.data.name} invalid={!!form.errors.name} />
            </Field>

            <Field label="Links to" error={linkError} hint="A record on this site, an external address, or nothing at all — a parent is often just a label.">
                <ContentLinkInput
                    bind:type={form.data.linkable_type}
                    bind:id={form.data.linkable_id}
                    bind:url={form.data.url}
                    initialOption={menuItem?.linkable ? { value: menuItem.linkable.id, label: menuItem.linkable.name } : null}
                    invalid={!!linkError}
                />
            </Field>
        </div>
    {/if}

    {#if !editing}
        <!-- Create: the default language's title, inline. -->
        <div class="flex flex-col gap-5 border-t border-border pt-5">
            <Field label="Title" error={form.errors.title} required hint="The label the public site renders. Translatable once created.">
                <Input bind:value={form.data.title} invalid={!!form.errors.title} />
            </Field>
        </div>
    {:else if activeTab === 'translations'}
        <div class="flex flex-col gap-5">
            <div class="kt-tabs kt-tabs-line overflow-x-auto" role="tablist">
                {#each languages as language (language.code)}
                    <button
                        type="button"
                        role="tab"
                        data-kt-tab-toggle
                        class="kt-tab-toggle {activeLocale === language.code ? 'active' : ''}"
                        aria-selected={activeLocale === language.code}
                        onclick={() => (activeLocale = language.code)}
                    >
                        {language.name}
                        {#if localeHasError(language.code)}
                            <span class="ms-1.5 inline-block size-1.5 rounded-full bg-destructive"></span>
                        {/if}
                    </button>
                {/each}
            </div>

            {#if activeLocale}
                <Field label="Title" error={form.errors[`title.${activeLocale}`]} required={!!activeLanguage?.is_default}>
                    <Input
                        bind:value={form.data.title[activeLocale]}
                        invalid={!!form.errors[`title.${activeLocale}`]}
                        dir={activeLanguage?.is_rtl ? 'rtl' : 'ltr'}
                    />
                </Field>
            {/if}
        </div>
    {/if}

    <div class="flex items-center justify-end gap-3 border-t border-border pt-4">
        <Button variant="secondary" onclick={oncancel}>Cancel</Button>
        <Button variant="primary" type="submit" loading={form.processing}>Save</Button>
    </div>
</form>
