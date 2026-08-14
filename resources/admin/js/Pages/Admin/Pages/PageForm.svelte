<script>
    /**
     * Page create/edit.
     *
     * Create asks for the DEFAULT language only — one column, no tabs — because
     * there is nothing to translate until the page exists. Edit splits into
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
    import { useForm } from '@/lib/api/useForm.svelte';
    import { api } from '@/lib/api/client';
    import { toast } from '@/lib/toast';
    import { PAGE_STATUS_LABELS, needsPublishedAt, reachableStatuses } from '@/lib/page';

    let { page = null, ready = true, onsaved, oncancel } = $props();

    const editing = $derived(!!page);

    let languages = $state([]);
    let activeTab = $state('details');
    let activeLocale = $state(null);

    // Create submits flat strings for the default language; edit submits the full
    // locale maps. Two shapes, so the form data is seeded accordingly.
    const form = useForm(
        page
            ? {
                  name: page.name ?? '',
                  slug: page.slug ?? '',
                  menu_id: page?.menu?.id ?? null,
                  title: { ...(page.title ?? {}) },
                  description: { ...(page.description ?? {}) },
                  content: { ...(page.content ?? {}) },
                  status: page.status ?? 'draft',
                  published_at: page.published_at ? page.published_at.slice(0, 16).replace('T', ' ') : null,
                  css_url: page.css_url ?? '',
                  custom_css: page.custom_css ?? '',
                  thumbnail: null,
              }
            : {
                  name: '',
                  slug: '',
                  menu_id: null,
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
        reachableStatuses(page?.status ?? null).map((value) => ({ value, label: PAGE_STATUS_LABELS[value] ?? value })),
    );

    const activeLanguage = $derived(languages.find((l) => l.code === activeLocale) ?? null);

    /** Any validation error under this locale, so a collapsed tab isn't a mystery. */
    function localeHasError(code) {
        return ['title', 'description', 'content'].some((field) => !!form.errors[`${field}.${code}`]);
    }

    $effect(() => {
        api.get(route('api.v1.admin.languages.index'), { filter: { is_enabled: 1 }, per_page: 100, sort: 'name' })
            .then((d) => {
                // Default language first — it is the one whose copy is required,
                // so it should be the tab you land on and the one you scan for.
                languages = (d?.data ?? []).slice().sort((a, b) => Number(!!b.is_default) - Number(!!a.is_default));
                if (!activeLocale && languages.length) {
                    activeLocale = languages[0].code;
                }
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
        const url = editing ? route('api.v1.admin.pages.update', page.id) : route('api.v1.admin.pages.store');
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
            <Field label="Thumbnail" error={form.errors.thumbnail} required={!editing} hint="Shown wherever the page is listed.">
                <MediaPicker accept={['images']} bind:value={form.data.thumbnail} previewUrl={page?.thumbnail_url} />
            </Field>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <Field label="Name" error={form.errors.name} required hint="Internal label — not shown to the public.">
                    <Input bind:value={form.data.name} invalid={!!form.errors.name} />
                </Field>
                <Field
                    label="Slug"
                    error={form.errors.slug}
                    required
                    hint={page?.is_system
                        ? 'This page ships with the app — its slug is fixed.'
                        : 'Filled in from the name until you edit it. This is the public URL.'}
                >
                    <SlugInput bind:value={form.data.slug} source={form.data.name} disabled={!!page?.is_system} invalid={!!form.errors.slug} />
                </Field>
            </div>

            <!-- Remote select: loads page one and narrows by search. -->
            <Field label="Menu" error={form.errors.menu_id} hint="Optional — a menu this page renders alongside its content.">
                <Select
                    resource="api.v1.admin.menus.index"
                    bind:value={form.data.menu_id}
                    labelKey="name"
                    placeholder="Search menus…"
                    clearable
                    initialOptions={page?.menu ? [{ value: page.menu.id, label: page.menu.name }] : []}
                />
            </Field>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <Field label="Status" error={form.errors.status} required>
                    <Select options={statusOptions} bind:value={form.data.status} clearable={false} />
                </Field>
                {#if needsPublishedAt(form.data.status)}
                    <!-- Only a schedule asks for a date; publishing is stamped now. -->
                    <Field
                        label="Publish at"
                        error={form.errors.published_at}
                        required
                        hint="Must be in the future — it goes live automatically."
                    >
                        <DatePicker enableTime bind:value={form.data.published_at} invalid={!!form.errors.published_at} />
                    </Field>
                {/if}
            </div>

            <Field label="Stylesheet URL" error={form.errors.css_url} hint="Optional. A hosted stylesheet for this page's content HTML only. Scope every selector to #page-content — the id on the content container — so nothing leaks into the rest of the page.">
                <Input bind:value={form.data.css_url} invalid={!!form.errors.css_url} placeholder="https://…" />
            </Field>

            <Field label="Custom CSS" error={form.errors.custom_css} hint="Inline CSS for this page's content HTML only, loaded after the stylesheet above so rules here win. Scope every selector to #page-content — the id on the content container — so nothing leaks into the rest of the page.">
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
            <!-- Nested strip, hand-rolled so it reads as secondary to the outer
                 Tabs — the same markup MediaLibraryModal and Media/Index use. -->
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
                    label="Description"
                    error={form.errors[`description.${activeLocale}`]}
                    required={!!activeLanguage?.is_default}
                >
                    <textarea
                        class="kt-input min-h-[90px]"
                        class:border-destructive={!!form.errors[`description.${activeLocale}`]}
                        dir={activeLanguage?.is_rtl ? 'rtl' : 'ltr'}
                        bind:value={form.data.description[activeLocale]}
                    ></textarea>
                </Field>

                <Field
                    label="Content"
                    error={form.errors[`content.${activeLocale}`]}
                    required={!!activeLanguage?.is_default}
                >
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
