<script>
    /**
     * Visit service create/edit.
     *
     * Create asks for the DEFAULT language only — one column, no tabs — because
     * there is nothing to translate until the visit exists. Edit splits into
     * [Details | Translations], and Translations nests a tab per enabled language.
     *
     * The HtmlEditor is rendered ONCE, outside the language loop, and its bound
     * target swaps with the active locale. One instance rather than one per tab:
     * see the component's own docblock for why.
     *
     * `content` here is the body of the Read-more popup on the public card, which
     * is why the two CSS fields sit beside it: they are what style that popup, and
     * the editor previews through them.
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
    import { useForm } from '@/lib/api/useForm.svelte';
    import { api } from '@/lib/api/client';
    import { toast } from '@/lib/toast';
    import { VISIT_SERVICE_STATUS_LABELS, needsPublishedAt, reachableStatuses } from '@/lib/visitService';

    let { visitService = null, ready = true, onsaved, oncancel } = $props();

    const editing = $derived(!!visitService);

    const TRANSLATED = ['title', 'description', 'content'];

    let languages = $state([]);
    let activeTab = $state('details');
    let activeLocale = $state(null);

    const form = useForm(
        visitService
            ? {
                  name: visitService.name ?? '',
                  slug: visitService.slug ?? '',
                  duration_minutes: visitService.duration_minutes ?? 60,
                  max_visitors: visitService.max_visitors ?? 5,
                  order: visitService.order ?? 0,
                  title: { ...(visitService.title ?? {}) },
                  description: { ...(visitService.description ?? {}) },
                  content: { ...(visitService.content ?? {}) },
                  status: visitService.status ?? 'draft',
                  published_at: visitService.published_at ? visitService.published_at.slice(0, 16).replace('T', ' ') : null,
                  css_url: visitService.css_url ?? '',
                  custom_css: visitService.custom_css ?? '',
                  thumbnail: null,
              }
            : {
                  name: '',
                  slug: '',
                  duration_minutes: 60,
                  max_visitors: 5,
                  order: 0,
                  title: '',
                  description: '',
                  content: '',
                  status: 'draft',
                  published_at: null,
                  css_url: '',
                  custom_css: '',
                  thumbnail: null,
              },
    );

    const statusOptions = $derived(
        reachableStatuses(visitService?.status ?? null).map((value) => ({
            value,
            label: VISIT_SERVICE_STATUS_LABELS[value] ?? value,
        })),
    );

    const activeLanguage = $derived(languages.find((l) => l.code === activeLocale) ?? null);

    /** Any validation error under this locale, so a collapsed tab isn't a mystery. */
    function localeHasError(code) {
        return Object.keys(form.errors).some((key) => TRANSLATED.some((field) => key.startsWith(`${field}.${code}`)));
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
                        for (const field of TRANSLATED) {
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
        // The number inputs hand back strings.
        out.duration_minutes = Number(out.duration_minutes) || 0;
        out.max_visitors = Number(out.max_visitors) || 0;
        out.order = Number(out.order) || 0;

        return out;
    }

    async function submit(event) {
        event.preventDefault();
        const url = editing
            ? route('api.v1.admin.visit-services.update', visitService.id)
            : route('api.v1.admin.visit-services.store');
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
            <Field label="Thumbnail" error={form.errors.thumbnail} required={!editing} hint="The photograph on the visit's card.">
                <MediaPicker accept={['images']} bind:value={form.data.thumbnail} previewUrl={visitService?.thumbnail_url} />
            </Field>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <Field label="Name" error={form.errors.name} required hint="Internal label — not shown to the public.">
                    <Input bind:value={form.data.name} invalid={!!form.errors.name} />
                </Field>
                <Field label="Slug" error={form.errors.slug} required hint="Filled in from the name until you edit it. The booking calendar loads by it.">
                    <SlugInput bind:value={form.data.slug} source={form.data.name} invalid={!!form.errors.slug} />
                </Field>
            </div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                <Field label="Duration (minutes)" error={form.errors.duration_minutes} required hint="Advertised on the card. Time slots set their own length.">
                    <Input type="number" min="5" max="480" step="5" bind:value={form.data.duration_minutes} invalid={!!form.errors.duration_minutes} />
                </Field>
                <Field label="Max visitors" error={form.errors.max_visitors} required hint="The ceiling of the party-size counter, and of the students on the form.">
                    <Input type="number" min="1" max="50" bind:value={form.data.max_visitors} invalid={!!form.errors.max_visitors} />
                </Field>
                <Field label="Order" error={form.errors.order} required hint="Low numbers first, on the page and in this list.">
                    <Input type="number" min="0" bind:value={form.data.order} invalid={!!form.errors.order} />
                </Field>
            </div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <Field label="Status" error={form.errors.status} required>
                    <Select options={statusOptions} bind:value={form.data.status} clearable={false} />
                </Field>
                {#if needsPublishedAt(form.data.status)}
                    <Field label="Publish at" error={form.errors.published_at} required hint="When the visit becomes public.">
                        <DatePicker enableTime bind:value={form.data.published_at} invalid={!!form.errors.published_at} />
                    </Field>
                {/if}
            </div>

            <div class="flex flex-col gap-5 border-t border-border pt-5">
                <Field label="Stylesheet URL" error={form.errors.css_url} hint="Optional. Loaded inside the Read more popup, before the custom CSS below.">
                    <Input bind:value={form.data.css_url} invalid={!!form.errors.css_url} placeholder="https://…" />
                </Field>
                <Field
                    label="Custom CSS"
                    error={form.errors.custom_css}
                    hint="Scope every selector to the popup body, whose id is visit-content- plus the visit's id."
                >
                    <textarea
                        class="kt-input min-h-[90px] font-mono text-2sm"
                        class:border-destructive={!!form.errors.custom_css}
                        bind:value={form.data.custom_css}
                    ></textarea>
                </Field>
            </div>
        </div>
    {/if}

    {#if !editing}
        <!-- Create: the default language's copy, inline. -->
        <div class="flex flex-col gap-5 border-t border-border pt-5">
            <Field label="Title" error={form.errors.title} required hint="The heading on the card.">
                <Input bind:value={form.data.title} invalid={!!form.errors.title} />
            </Field>
            <Field label="Description" error={form.errors.description} required hint="The two lines under the heading.">
                <textarea class="kt-input min-h-[90px]" class:border-destructive={!!form.errors.description} bind:value={form.data.description}></textarea>
            </Field>
            <Field label="Content" error={form.errors.content} required hint="The body of the Read more popup.">
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

    <div class="flex items-center justify-end gap-3 border-t border-border pt-4">
        <Button variant="secondary" onclick={oncancel}>Cancel</Button>
        <Button variant="primary" type="submit" loading={form.processing}>Save</Button>
    </div>
</form>
