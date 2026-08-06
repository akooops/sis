<script>
    /**
     * Brand asset group create/edit. No `order` field by design — position is set
     * by dragging in the Reorder drawer, and a new group goes last within its brand.
     *
     * Create asks for the DEFAULT language's title only — there is nothing to
     * translate until the group exists. Edit splits into [Details | Translations],
     * matching every other content module.
     */
    import Field from '@/components/form/Field.svelte';
    import Input from '@/components/form/Input.svelte';
    import Select from '@/components/form/Select.svelte';
    import Tabs from '@/components/ui/Tabs.svelte';
    import Button from '@/components/ui/Button.svelte';
    import { useForm } from '@/lib/api/useForm.svelte';
    import { api } from '@/lib/api/client';
    import { toast } from '@/lib/toast';

    // `brandId` is the page's active brand filter: adding a group after drilling in
    // from Brands should not ask which brand again.
    let { group = null, brandId = null, onsaved, oncancel } = $props();

    const editing = $derived(!!group);

    let languages = $state([]);
    let activeTab = $state('details');
    let activeLocale = $state(null);

    const form = useForm(
        group
            ? {
                  brand_id: group.brand?.id ?? group.brand_id ?? null,
                  name: group.name ?? '',
                  title: { ...(group.title ?? {}) },
              }
            : {
                  brand_id: brandId,
                  name: '',
                  title: '',
              },
    );

    const activeLanguage = $derived(languages.find((l) => l.code === activeLocale) ?? null);

    /** Any validation error under this locale, so a collapsed tab isn't a mystery. */
    function localeHasError(code) {
        return !!form.errors[`title.${code}`];
    }

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

    async function submit(event) {
        event.preventDefault();
        const url = editing
            ? route('api.v1.admin.brand-asset-groups.update', group.id)
            : route('api.v1.admin.brand-asset-groups.store');
        try {
            const res = await form.submit(editing ? 'put' : 'post', url);
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
            <!-- Remote select: loads page one and narrows by search. A preselected
                 id with no seeded label resolves itself, so create needs none. -->
            <Field label="Brand" error={form.errors.brand_id} required hint="The brand whose kit this group belongs to.">
                <Select
                    resource="api.v1.admin.brands.index"
                    bind:value={form.data.brand_id}
                    labelKey="name"
                    placeholder="Search brands…"
                    invalid={!!form.errors.brand_id}
                    initialOptions={group?.brand ? [{ value: group.brand.id, label: group.brand.name }] : []}
                />
            </Field>

            <Field label="Name" error={form.errors.name} required hint="Internal label — not shown to the public. Unique within the brand.">
                <Input bind:value={form.data.name} invalid={!!form.errors.name} />
            </Field>
        </div>
    {/if}

    {#if !editing}
        <!-- Create: the default language's title, inline. -->
        <div class="flex flex-col gap-5 border-t border-border pt-5">
            <Field label="Title" error={form.errors.title} required hint="The public heading for this group. Translatable once created.">
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
