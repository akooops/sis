<script>
    /**
     * Job offer create/edit.
     *
     * Create asks for the DEFAULT language only — one column, no tabs — because
     * there is nothing to translate until the offer exists. Edit splits into
     * [Details | Translations], and Translations nests a tab per enabled language.
     *
     * The HtmlEditor is rendered ONCE, outside the language loop, and its bound
     * target swaps with the active locale. One instance rather than one per tab:
     * see the component's own docblock for why.
     *
     * Skills travel as a plain string array per locale; the ";;;" join the column
     * stores is the server's business.
     */
    import Field from '@/components/form/Field.svelte';
    import Input from '@/components/form/Input.svelte';
    import SlugInput from '@/components/form/SlugInput.svelte';
    import Select from '@/components/form/Select.svelte';
    import DatePicker from '@/components/form/DatePicker.svelte';
    import TagsInput from '@/components/form/TagsInput.svelte';
    import HtmlEditor from '@/components/form/HtmlEditor.svelte';
    import Tabs from '@/components/ui/Tabs.svelte';
    import Button from '@/components/ui/Button.svelte';
    import MediaPicker from '@/components/media/MediaPicker.svelte';
    import { useForm } from '@/lib/api/useForm.svelte';
    import { api } from '@/lib/api/client';
    import { toast } from '@/lib/toast';
    import {
        EDUCATION_LEVEL_LABELS,
        EMPLOYMENT_TYPE_LABELS,
        JOB_OFFER_STATUS_LABELS,
        WORK_MODE_LABELS,
        needsPublishedAt,
        reachableStatuses,
    } from '@/lib/jobOffer';

    let { jobOffer = null, ready = true, onsaved, oncancel } = $props();

    const editing = $derived(!!jobOffer);

    // Skills is one of these: its errors come back keyed skills.en.2.
    const TRANSLATED = ['title', 'description', 'content', 'address', 'skills'];

    let languages = $state([]);
    let activeTab = $state('details');
    let activeLocale = $state(null);
    let defaultCategory = $state(null);

    const form = useForm(
        jobOffer
            ? {
                  name: jobOffer.name ?? '',
                  slug: jobOffer.slug ?? '',
                  category_id: jobOffer.category?.id ?? null,
                  employment_type: jobOffer.employment_type ?? 'full_time',
                  work_mode: jobOffer.work_mode ?? 'onsite',
                  experience_years: jobOffer.experience_years ?? '',
                  education_level: jobOffer.education_level ?? null,
                  start_date: jobOffer.start_date ?? null,
                  deadline_at: jobOffer.deadline_at ? jobOffer.deadline_at.slice(0, 16).replace('T', ' ') : null,
                  title: { ...(jobOffer.title ?? {}) },
                  description: { ...(jobOffer.description ?? {}) },
                  content: { ...(jobOffer.content ?? {}) },
                  address: { ...(jobOffer.address ?? {}) },
                  skills: { ...(jobOffer.skills ?? {}) },
                  status: jobOffer.status ?? 'draft',
                  published_at: jobOffer.published_at ? jobOffer.published_at.slice(0, 16).replace('T', ' ') : null,
                  css_url: jobOffer.css_url ?? '',
                  custom_css: jobOffer.custom_css ?? '',
                  thumbnail: null,
              }
            : {
                  name: '',
                  slug: '',
                  category_id: null,
                  employment_type: 'full_time',
                  work_mode: 'onsite',
                  experience_years: '',
                  education_level: null,
                  start_date: null,
                  deadline_at: null,
                  title: '',
                  description: '',
                  content: '',
                  address: '',
                  skills: [],
                  status: 'draft',
                  published_at: null,
                  css_url: '',
                  custom_css: '',
                  thumbnail: null,
              },
    );

    const statusOptions = $derived(
        reachableStatuses(jobOffer?.status ?? null).map((value) => ({ value, label: JOB_OFFER_STATUS_LABELS[value] ?? value })),
    );

    const employmentTypeOptions = Object.entries(EMPLOYMENT_TYPE_LABELS).map(([value, label]) => ({ value, label }));
    const workModeOptions = Object.entries(WORK_MODE_LABELS).map(([value, label]) => ({ value, label }));
    const educationLevelOptions = Object.entries(EDUCATION_LEVEL_LABELS).map(([value, label]) => ({ value, label }));

    const activeLanguage = $derived(languages.find((l) => l.code === activeLocale) ?? null);

    /** Any validation error under this locale, so a collapsed tab isn't a mystery. */
    function localeHasError(code) {
        return Object.keys(form.errors).some((key) => TRANSLATED.some((field) => key.startsWith(`${field}.${code}`)));
    }

    /** One bad skill is keyed skills.2 (skills.en.2 when editing) — surface it anyway. */
    function skillsError(prefix) {
        const key = Object.keys(form.errors).find((k) => k === prefix || k.startsWith(`${prefix}.`));

        return key ? form.errors[key] : null;
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
                        for (const field of ['title', 'description', 'content', 'address']) {
                            if (form.data[field][l.code] === undefined) form.data[field][l.code] = '';
                        }
                        if (!Array.isArray(form.data.skills[l.code])) form.data.skills[l.code] = [];
                    }
                }
            })
            .catch(() => {});
    });

    // Preselect the default category on create, so publishing is never blocked by
    // a taxonomy decision. The server falls back to the same one when nothing is
    // sent — this is the visible half of that.
    $effect(() => {
        if (editing) return;

        api.get(route('api.v1.admin.categories.index'), { filter: { is_default: 1 }, per_page: 1 })
            .then((d) => {
                const fallback = d?.data?.[0];
                if (!fallback || form.data.category_id) return;
                form.data.category_id = fallback.id;
                defaultCategory = fallback;
            })
            .catch(() => {});
    });

    function payload(data) {
        const out = { ...data };
        if (!out.thumbnail) delete out.thumbnail;
        if (!needsPublishedAt(out.status)) out.published_at = null;
        // The number input hands back a string; blank is "unspecified", not 0.
        out.experience_years = out.experience_years === '' || out.experience_years === null ? null : Number(out.experience_years);

        return out;
    }

    async function submit(event) {
        event.preventDefault();
        const url = editing ? route('api.v1.admin.job-offers.update', jobOffer.id) : route('api.v1.admin.job-offers.store');
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
            <Field label="Thumbnail" error={form.errors.thumbnail} required={!editing} hint="Shown wherever the job offer is listed.">
                <MediaPicker accept={['images']} bind:value={form.data.thumbnail} previewUrl={jobOffer?.thumbnail_url} />
            </Field>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <Field label="Name" error={form.errors.name} required hint="Internal label — not shown to the public.">
                    <Input bind:value={form.data.name} invalid={!!form.errors.name} />
                </Field>
                <Field label="Slug" error={form.errors.slug} required hint="Filled in from the name until you edit it. This is the public URL.">
                    <SlugInput bind:value={form.data.slug} source={form.data.name} invalid={!!form.errors.slug} />
                </Field>
            </div>

            <!-- Remote select: loads page one and narrows by search. -->
            <Field label="Category" error={form.errors.category_id} hint="Defaults to the fallback category if you leave it alone.">
                <Select
                    resource="api.v1.admin.categories.index"
                    bind:value={form.data.category_id}
                    labelKey="name"
                    placeholder="Search categories…"
                    initialOptions={jobOffer?.category
                        ? [{ value: jobOffer.category.id, label: jobOffer.category.name }]
                        : defaultCategory
                          ? [{ value: defaultCategory.id, label: defaultCategory.name }]
                          : []}
                />
            </Field>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <Field label="Employment type" error={form.errors.employment_type} required>
                    <Select options={employmentTypeOptions} bind:value={form.data.employment_type} clearable={false} />
                </Field>
                <Field label="Work mode" error={form.errors.work_mode} required>
                    <Select options={workModeOptions} bind:value={form.data.work_mode} clearable={false} />
                </Field>
            </div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <Field label="Experience years" error={form.errors.experience_years} hint="Leave blank for unspecified; 0 means entry level.">
                    <Input type="number" min="0" max="60" bind:value={form.data.experience_years} invalid={!!form.errors.experience_years} />
                </Field>
                <Field label="Education level" error={form.errors.education_level} hint="Optional minimum qualification.">
                    <Select options={educationLevelOptions} bind:value={form.data.education_level} placeholder="Unspecified" />
                </Field>
            </div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <!-- When the job starts - unrelated to when the listing goes live. -->
                <Field label="Start date" error={form.errors.start_date} hint="When the successful applicant would begin.">
                    <DatePicker bind:value={form.data.start_date} invalid={!!form.errors.start_date} />
                </Field>
                <Field label="Deadline" error={form.errors.deadline_at} hint="Applications close then. It does not change the status.">
                    <DatePicker enableTime bind:value={form.data.deadline_at} invalid={!!form.errors.deadline_at} />
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

            <Field label="Stylesheet URL" error={form.errors.css_url} hint="Optional external CSS applied to this job offer's content.">
                <Input bind:value={form.data.css_url} invalid={!!form.errors.css_url} placeholder="https://…" />
            </Field>

            <Field label="Custom CSS" error={form.errors.custom_css} hint="Inline CSS applied to this job offer's content.">
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
            <Field label="Skills" error={skillsError('skills')} hint="Press Enter or comma to add one.">
                <TagsInput bind:value={form.data.skills} placeholder="Add a skill…" invalid={!!skillsError('skills')} />
            </Field>
            <Field label="Address" error={form.errors.address} hint="Where the job is based. A remote offer can leave it blank.">
                <textarea class="kt-input min-h-[70px]" class:border-destructive={!!form.errors.address} bind:value={form.data.address}></textarea>
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

                <Field label="Skills" error={skillsError(`skills.${activeLocale}`)} hint="Press Enter or comma to add one.">
                    <TagsInput
                        bind:value={form.data.skills[activeLocale]}
                        placeholder="Add a skill…"
                        invalid={!!skillsError(`skills.${activeLocale}`)}
                        dir={activeLanguage?.is_rtl ? 'rtl' : 'ltr'}
                    />
                </Field>

                <Field label="Address" error={form.errors[`address.${activeLocale}`]}>
                    <textarea
                        class="kt-input min-h-[70px]"
                        class:border-destructive={!!form.errors[`address.${activeLocale}`]}
                        dir={activeLanguage?.is_rtl ? 'rtl' : 'ltr'}
                        bind:value={form.data.address[activeLocale]}
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
