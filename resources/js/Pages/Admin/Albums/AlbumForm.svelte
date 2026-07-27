<script>
    /**
     * Album create/edit.
     *
     * Create asks for the DEFAULT language only — one column, no tabs — because
     * there is nothing to translate until the album exists. Edit splits into
     * [Details | Translations], and Translations nests a tab per enabled language.
     *
     * The HtmlEditor is rendered ONCE, outside the language loop, and its bound
     * target swaps with the active locale. One instance rather than one per tab:
     * see the component's own docblock for why.
     */
    import Field from '@/components/form/Field.svelte';
    import Input from '@/components/form/Input.svelte';
    import SlugInput from '@/components/form/SlugInput.svelte';
    import Select from '@/components/form/Select.svelte';
    import DatePicker from '@/components/form/DatePicker.svelte';
    import HtmlEditor from '@/components/form/HtmlEditor.svelte';
    import Tabs from '@/components/ui/Tabs.svelte';
    import Button from '@/components/ui/Button.svelte';
    import MediaPicker from '@/components/media/MediaPicker.svelte';
    import MediaPickerMulti from '@/components/media/MediaPickerMulti.svelte';
    import { useForm } from '@/lib/api/useForm.svelte';
    import { api } from '@/lib/api/client';
    import { toast } from '@/lib/toast';
    import { ALBUM_STATUS_LABELS, needsPublishedAt, reachableStatuses } from '@/lib/album';

    let { album = null, ready = true, onsaved, oncancel } = $props();

    const editing = $derived(!!album);

    let languages = $state([]);
    let activeTab = $state('details');
    let activeLocale = $state(null);

    const form = useForm(
        album
            ? {
                  name: album.name ?? '',
                  slug: album.slug ?? '',
                  title: { ...(album.title ?? {}) },
                  description: { ...(album.description ?? {}) },
                  content: { ...(album.content ?? {}) },
                  status: album.status ?? 'draft',
                  published_at: album.published_at ? album.published_at.slice(0, 16).replace('T', ' ') : null,
                  css_url: album.css_url ?? '',
                  custom_css: album.custom_css ?? '',
                  thumbnail: null,
                  // Ids only; the order IS the display order.
                  files: (album.files ?? []).map((f) => f.id),
              }
            : {
                  name: '',
                  slug: '',
                  title: '',
                  description: '',
                  content: '',
                  status: 'draft',
                  published_at: null,
                  css_url: '',
                  custom_css: '',
                  thumbnail: null,
                  files: [],
              },
    );

    const statusOptions = $derived(
        reachableStatuses(album?.status ?? null).map((value) => ({ value, label: ALBUM_STATUS_LABELS[value] ?? value })),
    );

    const activeLanguage = $derived(languages.find((l) => l.code === activeLocale) ?? null);

    /** Any validation error under this locale, so a collapsed tab isn't a mystery. */
    function localeHasError(code) {
        return ['title', 'description', 'content'].some((field) => !!form.errors[`${field}.${code}`]);
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
                        for (const field of ['title', 'description', 'content']) {
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
        if (!needsPublishedAt(out.status)) out.published_at = null;

        return out;
    }

    async function submit(event) {
        event.preventDefault();
        const url = editing ? route('api.v1.admin.albums.update', album.id) : route('api.v1.admin.albums.store');
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
            <Field label="Cover" error={form.errors.thumbnail} required={!editing} hint="Shown wherever the album is listed.">
                <MediaPicker accept={['images']} bind:value={form.data.thumbnail} previewUrl={album?.thumbnail_url} />
            </Field>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <Field label="Name" error={form.errors.name} required hint="Internal label — not shown to the public.">
                    <Input bind:value={form.data.name} invalid={!!form.errors.name} />
                </Field>
                <Field label="Slug" error={form.errors.slug} required hint="Filled in from the name until you edit it. This is the public URL.">
                    <SlugInput bind:value={form.data.slug} source={form.data.name} invalid={!!form.errors.slug} />
                </Field>
            </div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <Field label="Status" error={form.errors.status} required>
                    <Select options={statusOptions} bind:value={form.data.status} clearable={false} />
                </Field>
                {#if needsPublishedAt(form.data.status)}
                    <Field label="Publish at" error={form.errors.published_at} required hint="Must be in the future — it goes live automatically.">
                        <DatePicker enableTime bind:value={form.data.published_at} invalid={!!form.errors.published_at} />
                    </Field>
                {/if}
            </div>

            <Field label="Stylesheet URL" error={form.errors.css_url} hint="Optional external CSS applied to this album's content.">
                <Input bind:value={form.data.css_url} invalid={!!form.errors.css_url} placeholder="https://…" />
            </Field>

            <Field label="Custom CSS" error={form.errors.custom_css} hint="Inline CSS applied to this album's content.">
                <textarea
                    class="kt-input min-h-[90px] font-mono text-2sm"
                    class:border-destructive={!!form.errors.custom_css}
                    bind:value={form.data.custom_css}
                ></textarea>
            </Field>
        </div>
    {/if}

    {#if !editing}
        <!-- Create: the default language's copy, inline. -->
        <div class="flex flex-col gap-5 border-t border-border pt-5">
            <Field label="Title" error={form.errors.title} required>
                <Input bind:value={form.data.title} invalid={!!form.errors.title} />
            </Field>
            <Field label="Description" error={form.errors.description} required>
                <textarea class="kt-input min-h-[90px]" class:border-destructive={!!form.errors.description} bind:value={form.data.description}></textarea>
            </Field>
            <Field label="Content" error={form.errors.content} required>
                <HtmlEditor
                    bind:value={form.data.content}
                    {ready}
                    contentCssUrl={form.data.css_url || null}
                    contentStyle={form.data.custom_css}
                />
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

                <Field label="Description" error={form.errors[`description.${activeLocale}`]} required={!!activeLanguage?.is_default}>
                    <textarea
                        class="kt-input min-h-[90px]"
                        class:border-destructive={!!form.errors[`description.${activeLocale}`]}
                        dir={activeLanguage?.is_rtl ? 'rtl' : 'ltr'}
                        bind:value={form.data.description[activeLocale]}
                    ></textarea>
                </Field>

                <Field label="Content" error={form.errors[`content.${activeLocale}`]} required={!!activeLanguage?.is_default}>
                    <HtmlEditor
                        bind:value={form.data.content[activeLocale]}
                        rtl={!!activeLanguage?.is_rtl}
                        {ready}
                        contentCssUrl={form.data.css_url || null}
                        contentStyle={form.data.custom_css}
                    />
                </Field>
            {/if}
        </div>
    {/if}

    <!--
        The gallery gets its own card at the end rather than sitting among the
        Details fields: it is the album's actual payload, it is the tallest thing
        on the form, and it belongs to the album as a whole rather than to any one
        language — so it stays visible whichever tab is open.
    -->
    <div class="kt-card">
        <div class="kt-card-header">
            <h3 class="kt-card-title">Album files</h3>
            <span class="text-xs text-muted-foreground">Images, video and audio — drag to reorder</span>
        </div>
        <div class="kt-card-content py-4 px-2">
            <Field error={form.errors.files ?? form.errors['files.0']}>
                <MediaPickerMulti
                    bind:value={form.data.files}
                    initial={album?.files ?? []}
                    accept={['images', 'videos', 'audio']}
                />
            </Field>
        </div>
    </div>

    <div class="flex items-center justify-end gap-3 border-t border-border pt-4">
        <Button variant="secondary" onclick={oncancel}>Cancel</Button>
        <Button variant="primary" type="submit" loading={form.processing}>Save</Button>
    </div>
</form>
