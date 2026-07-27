<script>
    /**
     * Grade create/edit.
     *
     * Create asks for the DEFAULT language's title only — there is nothing to
     * translate until the grade exists. Edit splits into [Details | Translations],
     * matching every other content module.
     *
     * No `order` field by design: position is set by dragging in the Reorder
     * drawer, and a new grade goes on the end of its program.
     */
    import Field from '@/components/form/Field.svelte';
    import Input from '@/components/form/Input.svelte';
    import Select from '@/components/form/Select.svelte';
    import Tabs from '@/components/ui/Tabs.svelte';
    import Button from '@/components/ui/Button.svelte';
    import MediaPickerMulti from '@/components/media/MediaPickerMulti.svelte';
    import { useForm } from '@/lib/api/useForm.svelte';
    import { api } from '@/lib/api/client';
    import { toast } from '@/lib/toast';

    let { grade = null, onsaved, oncancel } = $props();

    const editing = $derived(!!grade);

    let languages = $state([]);
    let activeTab = $state('details');
    let activeLocale = $state(null);

    const form = useForm({
        program_id: grade?.program_id ?? null,
        name: grade?.name ?? '',
        title: grade ? { ...(grade.title ?? {}) } : '',
        // Ids only; the array order IS the display order.
        guidelines: (grade?.guidelines ?? []).map((f) => f.id),
    });

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

    async function submit(e) {
        e.preventDefault();
        const url = editing
            ? route('api.v1.admin.grades.update', grade.id)
            : route('api.v1.admin.grades.store');
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
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <!-- Remote select: loads page one and narrows by search. -->
                <Field
                    label="Program"
                    error={form.errors.program_id}
                    required
                    hint="Moving a grade to another program puts it last in that program's order."
                >
                    <Select
                        resource="api.v1.admin.programs.index"
                        bind:value={form.data.program_id}
                        labelKey="name"
                        placeholder="Search programs…"
                        invalid={!!form.errors.program_id}
                        initialOptions={grade?.program ? [{ value: grade.program.id, label: grade.program.name }] : []}
                    />
                </Field>
                <Field label="Name" error={form.errors.name} required hint="Internal label — not shown to the public.">
                    <Input bind:value={form.data.name} invalid={!!form.errors.name} />
                </Field>
            </div>
        </div>
    {/if}

    {#if !editing}
        <!-- Create: the default language's title, inline. -->
        <div class="flex flex-col gap-5 border-t border-border pt-5">
            <Field label="Title" error={form.errors.title} required hint="The public label. Translatable once created.">
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

    <!--
        The guidelines get their own card at the end rather than sitting among the
        Details fields: they are the tallest thing on the form and belong to the
        grade as a whole rather than to any one language, so they stay visible
        whichever tab is open.
    -->
    <div class="kt-card">
        <div class="kt-card-header">
            <h3 class="kt-card-title">Guidelines</h3>
            <span class="text-xs text-muted-foreground">Documents and images — drag to reorder</span>
        </div>
        <div class="kt-card-content py-4 px-2">
            <!-- Removing a file here detaches it back to the media library; it is
                 not deleted. -->
            <Field error={form.errors.guidelines ?? form.errors['guidelines.0']}>
                <MediaPickerMulti
                    bind:value={form.data.guidelines}
                    initial={grade?.guidelines ?? []}
                    accept={['documents', 'images']}
                />
            </Field>
        </div>
    </div>

    <div class="flex items-center justify-end gap-3 border-t border-border pt-4">
        <Button variant="secondary" onclick={oncancel}>Cancel</Button>
        <Button variant="primary" type="submit" loading={form.processing}>Save</Button>
    </div>
</form>
