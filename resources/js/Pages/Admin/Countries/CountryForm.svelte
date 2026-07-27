<script>
    /**
     * Country create/edit.
     *
     * Create asks for the DEFAULT language's copy only — there is nothing to
     * translate until the country exists. Edit splits into [Details | Translations],
     * matching every other translatable module.
     */
    import Field from '@/components/form/Field.svelte';
    import Input from '@/components/form/Input.svelte';
    import Switch from '@/components/form/Switch.svelte';
    import Tabs from '@/components/ui/Tabs.svelte';
    import Button from '@/components/ui/Button.svelte';
    import MediaPicker from '@/components/media/MediaPicker.svelte';
    import { useForm } from '@/lib/api/useForm.svelte';
    import { api } from '@/lib/api/client';
    import { toast } from '@/lib/toast';

    let { country = null, onsaved, oncancel } = $props();

    const editing = $derived(!!country);

    let languages = $state([]);
    let activeTab = $state('details');
    let activeLocale = $state(null);

    const form = useForm({
        name: country?.name ?? '',
        code: country?.code ?? '',
        alpha3: country?.alpha3 ?? '',
        // Round-tripped, never edited: there is no slug control, and omitting it
        // would wipe the seeded artwork on save.
        flag: country?.flag ?? null,
        title: country ? { ...(country.title ?? {}) } : '',
        nationality: country ? { ...(country.nationality ?? {}) } : '',
        is_enabled: country?.is_enabled ?? true,
        flag_image: null,
    });

    const activeLanguage = $derived(languages.find((l) => l.code === activeLocale) ?? null);

    /** Any validation error under this locale, so a collapsed tab isn't a mystery. */
    function localeHasError(code) {
        return ['title', 'nationality'].some((field) => !!form.errors[`${field}.${code}`]);
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
                        for (const field of ['title', 'nationality']) {
                            if (form.data[field][l.code] === undefined) form.data[field][l.code] = '';
                        }
                    }
                }
            })
            .catch(() => {});
    });

    function payload(data) {
        const out = { ...data };
        // The columns are ISO codes; the inputs only look uppercase.
        out.code = (out.code ?? '').toUpperCase();
        out.alpha3 = out.alpha3 ? out.alpha3.toUpperCase() : null;
        if (!out.flag_image) delete out.flag_image;

        return out;
    }

    async function submit(event) {
        event.preventDefault();
        const url = editing
            ? route('api.v1.admin.countries.update', country.id)
            : route('api.v1.admin.countries.store');
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
            <Field label="Flag" error={form.errors.flag_image} hint="The bundled artwork is used when empty.">
                <MediaPicker accept={['images']} bind:value={form.data.flag_image} previewUrl={country?.flag_url} />
            </Field>

            <Field label="Name" error={form.errors.name} required hint="Internal label — not shown to the public.">
                <Input bind:value={form.data.name} invalid={!!form.errors.name} />
            </Field>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <Field label="Code" error={form.errors.code} required hint="ISO 3166-1 alpha-2, e.g. SA.">
                    <Input bind:value={form.data.code} invalid={!!form.errors.code} maxlength="2" class="uppercase" />
                </Field>
                <Field label="Alpha-3" error={form.errors.alpha3} hint="ISO 3166-1 alpha-3, e.g. SAU.">
                    <Input bind:value={form.data.alpha3} invalid={!!form.errors.alpha3} maxlength="3" class="uppercase" />
                </Field>
            </div>

            <Field label="Enabled" error={form.errors.is_enabled} hint="Disabled countries stay out of the public pickers.">
                <Switch bind:value={form.data.is_enabled} />
            </Field>
        </div>
    {/if}

    {#if !editing}
        <!-- Create: the default language's copy, inline. -->
        <div class="flex flex-col gap-5 border-t border-border pt-5">
            <Field label="Title" error={form.errors.title} required hint="The public country name. Translatable once created.">
                <Input bind:value={form.data.title} invalid={!!form.errors.title} />
            </Field>
            <Field label="Nationality" error={form.errors.nationality} required hint="The demonym, masculine singular — Saudi, British.">
                <Input bind:value={form.data.nationality} invalid={!!form.errors.nationality} />
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

                <Field
                    label="Nationality"
                    error={form.errors[`nationality.${activeLocale}`]}
                    required={!!activeLanguage?.is_default}
                    hint="Masculine singular — Arabic demonyms inflect (سعودي / سعودية)."
                >
                    <Input
                        bind:value={form.data.nationality[activeLocale]}
                        invalid={!!form.errors[`nationality.${activeLocale}`]}
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
