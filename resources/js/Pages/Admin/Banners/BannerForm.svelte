<script>
    /**
     * Banner create/edit. No `order` field by design — position is set by
     * dragging in the Reorder drawer, and a new banner goes on the end.
     *
     * Create asks for the DEFAULT language's copy only — there is nothing to
     * translate until the banner exists. Edit splits into [Details | Translations],
     * matching every other translatable module.
     */
    import Field from '@/components/form/Field.svelte';
    import Input from '@/components/form/Input.svelte';
    import ContentLinkInput from '@/components/form/ContentLinkInput.svelte';
    import Tabs from '@/components/ui/Tabs.svelte';
    import Button from '@/components/ui/Button.svelte';
    import MediaPicker from '@/components/media/MediaPicker.svelte';
    import { useForm } from '@/lib/api/useForm.svelte';
    import { api } from '@/lib/api/client';
    import { toast } from '@/lib/toast';
    import { linkKind } from '@/lib/linkable';

    let { banner = null, onsaved, oncancel } = $props();

    const editing = $derived(!!banner);

    let languages = $state([]);
    let activeTab = $state('details');
    let activeLocale = $state(null);

    const form = useForm({
        name: banner?.name ?? '',
        // The picker's kind, which is the morph alias plus a 'url' choice the
        // server does not know — payload() drops it again.
        linkable_type: linkKind(banner),
        linkable_id: banner?.linkable_id ?? null,
        url: banner?.url ?? '',
        title: banner ? { ...(banner.title ?? {}) } : '',
        cta: banner ? { ...(banner.cta ?? {}) } : '',
        thumbnail: null,
        video: null,
    });

    const activeLanguage = $derived(languages.find((l) => l.code === activeLocale) ?? null);

    // One error line under the picker: the server may reject any of the three.
    const linkError = $derived(form.errors.url ?? form.errors.linkable_type ?? form.errors.linkable_id ?? null);

    /** Any validation error under this locale, so a collapsed tab isn't a mystery. */
    function localeHasError(code) {
        return ['title', 'cta'].some((field) => !!form.errors[`${field}.${code}`]);
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
                        for (const field of ['title', 'cta']) {
                            if (form.data[field][l.code] === undefined) form.data[field][l.code] = '';
                        }
                    }
                }
            })
            .catch(() => {});
    });

    function payload(data) {
        const out = { ...data };
        if (!out.thumbnail) delete out.thumbnail;
        if (!out.video) delete out.video;

        // 'url' is a picker kind, not a morph alias; '' would fail the url rule
        // instead of clearing the link.
        if (out.linkable_type === 'url') out.linkable_type = null;
        out.url = out.url || null;

        return out;
    }

    async function submit(event) {
        event.preventDefault();
        const url = editing ? route('api.v1.admin.banners.update', banner.id) : route('api.v1.admin.banners.store');
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
                <Field label="Thumbnail" error={form.errors.thumbnail} required={!editing} hint="The slide artwork.">
                    <MediaPicker accept={['images']} bind:value={form.data.thumbnail} previewUrl={banner?.thumbnail_url} />
                </Field>
                <Field label="Video" error={form.errors.video} hint="Optional — plays over the image.">
                    <MediaPicker accept={['videos']} bind:value={form.data.video} />
                    <!-- The picker previews images only, so an already-attached
                         video would otherwise be invisible here. -->
                    {#if banner?.video_url && !form.data.video}
                        <a href={banner.video_url} target="_blank" rel="noreferrer noopener" class="kt-link text-xs">Current video</a>
                    {/if}
                </Field>
            </div>

            <Field label="Name" error={form.errors.name} required hint="Internal label — not shown to the public.">
                <Input bind:value={form.data.name} invalid={!!form.errors.name} />
            </Field>

            <Field label="Links to" error={linkError} hint="A record on this site, an external address, or nothing at all.">
                <ContentLinkInput
                    bind:type={form.data.linkable_type}
                    bind:id={form.data.linkable_id}
                    bind:url={form.data.url}
                    initialOption={banner?.linkable ? { value: banner.linkable.id, label: banner.linkable.name } : null}
                    invalid={!!linkError}
                />
            </Field>
        </div>
    {/if}

    {#if !editing}
        <!-- Create: the default language's copy, inline. -->
        <div class="flex flex-col gap-5 border-t border-border pt-5">
            <Field label="Title" error={form.errors.title} required hint="The headline on the slide. Translatable once created.">
                <Input bind:value={form.data.title} invalid={!!form.errors.title} />
            </Field>
            <Field label="Call to action" error={form.errors.cta} required hint="The button label, e.g. Read more.">
                <Input bind:value={form.data.cta} invalid={!!form.errors.cta} />
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

                <Field label="Call to action" error={form.errors[`cta.${activeLocale}`]} required={!!activeLanguage?.is_default}>
                    <Input
                        bind:value={form.data.cta[activeLocale]}
                        invalid={!!form.errors[`cta.${activeLocale}`]}
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
