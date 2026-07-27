<script>
    /**
     * Stream create/edit. No `order` field by design — position is set by dragging
     * in the Reorder drawer, and a new stream goes last within its program.
     *
     * Create asks for the DEFAULT language only — one column, no tabs — because
     * there is nothing to translate until the stream exists. Edit splits into
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
    import ColorInput from '@/components/form/ColorInput.svelte';
    import HtmlEditor from '@/components/form/HtmlEditor.svelte';
    import Tabs from '@/components/ui/Tabs.svelte';
    import Button from '@/components/ui/Button.svelte';
    import { useForm } from '@/lib/api/useForm.svelte';
    import { api } from '@/lib/api/client';
    import { toast } from '@/lib/toast';

    // `programId` is the page's active program filter: adding a stream after
    // drilling in from Programs should not ask which program again.
    let { stream = null, programId = null, ready = true, onsaved, oncancel } = $props();

    const editing = $derived(!!stream);

    let languages = $state([]);
    let activeTab = $state('details');
    let activeLocale = $state(null);

    const form = useForm(
        stream
            ? {
                  program_id: stream.program?.id ?? stream.program_id ?? null,
                  name: stream.name ?? '',
                  slug: stream.slug ?? '',
                  color: stream.color ?? '#1B84FF',
                  title: { ...(stream.title ?? {}) },
                  description: { ...(stream.description ?? {}) },
                  content: { ...(stream.content ?? {}) },
                  cta: { ...(stream.cta ?? {}) },
              }
            : {
                  program_id: programId,
                  name: '',
                  slug: '',
                  color: '#1B84FF',
                  title: '',
                  description: '',
                  content: '',
                  cta: '',
              },
    );

    const activeLanguage = $derived(languages.find((l) => l.code === activeLocale) ?? null);

    /** Any validation error under this locale, so a collapsed tab isn't a mystery. */
    function localeHasError(code) {
        return ['title', 'description', 'content', 'cta'].some((field) => !!form.errors[`${field}.${code}`]);
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
                        for (const field of ['title', 'description', 'content', 'cta']) {
                            if (form.data[field][l.code] === undefined) form.data[field][l.code] = '';
                        }
                    }
                }
            })
            .catch(() => {});
    });

    async function submit(event) {
        event.preventDefault();
        const url = editing ? route('api.v1.admin.streams.update', stream.id) : route('api.v1.admin.streams.store');
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
            <Field label="Program" error={form.errors.program_id} required hint="The program this stream is a variant of.">
                <Select
                    resource="api.v1.admin.programs.index"
                    bind:value={form.data.program_id}
                    labelKey="name"
                    placeholder="Search programs…"
                    invalid={!!form.errors.program_id}
                    initialOptions={stream?.program ? [{ value: stream.program.id, label: stream.program.name }] : []}
                />
            </Field>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <Field label="Name" error={form.errors.name} required hint="Internal label — not shown to the public.">
                    <Input bind:value={form.data.name} invalid={!!form.errors.name} />
                </Field>
                <Field label="Slug" error={form.errors.slug} required hint="Filled in from the name until you edit it. Unique within the program only.">
                    <SlugInput bind:value={form.data.slug} source={form.data.name} invalid={!!form.errors.slug} />
                </Field>
            </div>

            <Field label="Colour" error={form.errors.color} required hint="Background of this stream’s chip on the public site.">
                <ColorInput bind:value={form.data.color} invalid={!!form.errors.color} />
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
            <Field label="Call to action" error={form.errors.cta} required hint="The button label.">
                <Input bind:value={form.data.cta} invalid={!!form.errors.cta} />
            </Field>
            <Field label="Content" error={form.errors.content} required>
                <HtmlEditor bind:value={form.data.content} {ready} />
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

                <Field label="Call to action" error={form.errors[`cta.${activeLocale}`]} required={!!activeLanguage?.is_default}>
                    <Input
                        bind:value={form.data.cta[activeLocale]}
                        invalid={!!form.errors[`cta.${activeLocale}`]}
                        dir={activeLanguage?.is_rtl ? 'rtl' : 'ltr'}
                    />
                </Field>

                <Field label="Content" error={form.errors[`content.${activeLocale}`]} required={!!activeLanguage?.is_default}>
                    <HtmlEditor bind:value={form.data.content[activeLocale]} rtl={!!activeLanguage?.is_rtl} {ready} />
                </Field>
            {/if}
        </div>
    {/if}

    <div class="flex items-center justify-end gap-3 border-t border-border pt-4">
        <Button variant="secondary" onclick={oncancel}>Cancel</Button>
        <Button variant="primary" type="submit" loading={form.processing}>Save</Button>
    </div>
</form>
