<script>
    /**
     * Newsletter create/edit.
     *
     * Two independent switches, not a type: "Publish on website" wants a file and
     * a translated title, "Send by email" wants a subject, a body and an audience.
     * Both may be on — that is the normal case. Each switch's fields are hidden
     * AND stripped from the payload when it is off, so a switch turned back off
     * never leaves stale values behind on the record.
     *
     * Each switch also carries its own pipeline, and the two never read each other:
     * publish_status/published_at say when the website archive lists it,
     * status/scheduled_at when it is emailed.
     *
     * `title` is the only translatable field, so the [Details | Translations] tabs
     * exist only while publishing is on; otherwise the form has no tabs at all.
     *
     * Edit prefills straight from the index row (it carries group_ids, the group
     * labels and the integration), so opening the form never waits on a show
     * round-trip.
     */
    import Field from '@/components/form/Field.svelte';
    import Input from '@/components/form/Input.svelte';
    import Switch from '@/components/form/Switch.svelte';
    import Select from '@/components/form/Select.svelte';
    import DatePicker from '@/components/form/DatePicker.svelte';
    import HtmlEditor from '@/components/form/HtmlEditor.svelte';
    import MediaPicker from '@/components/media/MediaPicker.svelte';
    import Tabs from '@/components/ui/Tabs.svelte';
    import Button from '@/components/ui/Button.svelte';
    import { useForm } from '@/lib/api/useForm.svelte';
    import { api } from '@/lib/api/client';
    import { toast } from '@/lib/toast';
    import {
        EMAIL_PLACEHOLDER,
        NEWSLETTER_PUBLISH_STATUS_LABELS,
        NEWSLETTER_STATUS_LABELS,
        UNSUBSCRIBE_PLACEHOLDER,
        needsPublishedAt,
        needsScheduledAt,
        publishReachableStatuses,
        reachableStatuses,
    } from '@/lib/newsletter';

    let { newsletter = null, ready = true, onsaved, oncancel } = $props();

    const editing = $derived(!!newsletter);

    // A failed newsletter can only leave failed, so fall back to the first door open to it.
    const statuses = reachableStatuses(newsletter?.status ?? null);

    // The website pipeline, resolved the same way and entirely separate from it.
    const publishStatuses = publishReachableStatuses(newsletter?.publish_status ?? null);

    // Both tokens ShipNewsletter substitutes; the unsubscribe one must land as a link.
    const placeholders = [
        { label: 'Unsubscribe link', html: `<a href="${UNSUBSCRIBE_PLACEHOLDER}">Unsubscribe</a>` },
        { label: 'Recipient email', value: EMAIL_PLACEHOLDER },
    ];

    let languages = $state([]);
    let activeTab = $state('details');
    let activeLocale = $state(null);
    let emailTypeId = $state(null);

    const form = useForm({
        name: newsletter?.name ?? '',
        // Existing rows are email-only; a new one starts the same way.
        is_published: newsletter?.is_published ?? false,
        is_sendable: newsletter?.is_sendable ?? true,
        // Create sends the default locale as a plain string, edit the whole map.
        title: newsletter ? { ...(newsletter.title ?? {}) } : '',
        file: null,
        publish_status: publishStatuses.includes(newsletter?.publish_status) ? newsletter.publish_status : publishStatuses[0],
        published_at: newsletter?.published_at ? newsletter.published_at.slice(0, 16).replace('T', ' ') : null,
        subject: newsletter?.subject ?? '',
        content: newsletter?.content ?? '',
        integration_id: newsletter?.integration_id ?? null,
        status: statuses.includes(newsletter?.status) ? newsletter.status : statuses[0],
        scheduled_at: newsletter?.scheduled_at ? newsletter.scheduled_at.slice(0, 16).replace('T', ' ') : null,
        group_ids: [...(newsletter?.group_ids ?? [])],
    });

    // Nothing to translate unless the issue is published — title is the only
    // translatable field, so with publishing off there are no tabs at all.
    const showTabs = $derived(editing && form.data.is_published);

    const activeLanguage = $derived(languages.find((l) => l.code === activeLocale) ?? null);

    const statusOptions = statuses.map((value) => ({ value, label: NEWSLETTER_STATUS_LABELS[value] ?? value }));

    const publishStatusOptions = publishStatuses.map((value) => ({ value, label: NEWSLETTER_PUBLISH_STATUS_LABELS[value] ?? value }));

    // Label seeds from the same row, so both selects render names instantly
    // while their option lists load in the background.
    const groupSeed = (newsletter?.groups ?? []).map((g) => ({ value: g.id, label: g.name }));
    const integrationSeed = newsletter?.integration
        ? [{ value: newsletter.integration.id, label: newsletter.integration.name }]
        : [];

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

    $effect(() => {
        // The integration picker only offers email integrations — resolve the
        // email type id first, then the Select browses integrations filtered by it.
        api.get(route('api.v1.admin.integration-types.index'))
            .then((d) => { emailTypeId = (d ?? []).find((t) => t.code === 'email')?.id ?? null; })
            .catch(() => {});
    });

    function payload(data) {
        const out = { ...data };

        // Send nothing for a switch that is off: the server must see nulls, not
        // whatever the hidden fields still held from before it was turned off.
        if (out.is_published) {
            // No new pick means "keep the current file" — omitting it says so.
            if (!out.file) delete out.file;
            if (!needsPublishedAt(out.publish_status)) out.published_at = null;
        } else {
            delete out.file;
            out.title = editing ? {} : null;
            // The publish pipeline governs the website alone; the controller forces
            // this too, but a form that shows draft should also send draft.
            out.publish_status = 'draft';
            out.published_at = null;
        }

        if (out.is_sendable) {
            if (!needsScheduledAt(out.status)) out.scheduled_at = null;
        } else {
            out.subject = null;
            out.content = null;
            out.group_ids = [];
            // The status pipeline governs sending alone; the controller forces
            // this too, but a form that shows draft should also send draft.
            out.status = 'draft';
            out.scheduled_at = null;
        }

        return out;
    }

    async function submit(event) {
        event.preventDefault();
        const url = editing ? route('api.v1.admin.newsletters.update', newsletter.id) : route('api.v1.admin.newsletters.store');
        try {
            const res = await form.submit(editing ? 'put' : 'post', url, { transform: payload });
            if (res) {
                toast.success(editing ? 'Updated successfully.' : 'Created successfully.');
                onsaved?.();
            } else if (showTabs && languages.some((l) => localeHasError(l.code))) {
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
    {#if showTabs}
        <Tabs
            tabs={[
                { id: 'details', label: 'Details', icon: 'ki-filled ki-sms' },
                { id: 'translations', label: 'Translations', icon: 'ki-filled ki-flag' },
            ]}
            bind:active={activeTab}
        />
    {/if}

    {#if !showTabs || activeTab === 'details'}
        <div class="flex flex-col gap-5">
            <Field label="Name" error={form.errors.name} required hint="Internal label — not shown to the public or to subscribers.">
                <Input bind:value={form.data.name} invalid={!!form.errors.name} />
            </Field>

            <!-- Two switches, not a choice: an issue can be archived on the site,
                 emailed, or both. At least one must be on. -->
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <Field
                    label="Publish on website"
                    error={form.errors.is_published}
                    hint="Puts the issue on the public site — needs a file and a title."
                >
                    <Switch
                        bind:value={form.data.is_published}
                        onchange={(v) => { if (!v) activeTab = 'details'; }}
                    />
                </Field>
                <Field
                    label="Send by email"
                    error={form.errors.is_sendable}
                    hint="Emails the issue to its groups — needs a subject, a body and an audience."
                >
                    <Switch bind:value={form.data.is_sendable} />
                </Field>
            </div>

            {#if form.data.is_published}
                <div class="flex flex-col gap-5 border-t border-border pt-5">
                    <Field
                        label="File"
                        error={form.errors.file}
                        required={!editing}
                        hint={editing ? 'Pick a new file to replace the current one.' : 'The issue people download — usually a PDF.'}
                    >
                        <MediaPicker accept={['documents', 'images']} bind:value={form.data.file} previewUrl={newsletter?.file_url} />
                    </Field>

                    {#if !editing}
                        <!-- Create: the default language's title, inline. -->
                        <Field label="Title" error={form.errors.title} required hint="The public label. Translatable once created.">
                            <Input bind:value={form.data.title} invalid={!!form.errors.title} />
                        </Field>
                    {/if}

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <Field
                            label="Publish status"
                            error={form.errors.publish_status}
                            required
                            hint="The website archive only — it never affects the email send."
                        >
                            <Select options={publishStatusOptions} bind:value={form.data.publish_status} clearable={false} />
                        </Field>
                        {#if needsPublishedAt(form.data.publish_status)}
                            <Field label="Publish at" error={form.errors.published_at} required hint="Must be in the future — it goes live automatically.">
                                <DatePicker enableTime bind:value={form.data.published_at} invalid={!!form.errors.published_at} />
                            </Field>
                        {/if}
                    </div>
                </div>
            {/if}

            {#if form.data.is_sendable}
                <div class="flex flex-col gap-5 border-t border-border pt-5">
                    <Field label="Subject" error={form.errors.subject} required hint="The email subject line.">
                        <Input bind:value={form.data.subject} invalid={!!form.errors.subject} />
                    </Field>

                    <!-- Remote multiselect: the groups list is paginated, so it searches rather
                         than loading everything. -->
                    <Field label="Groups" error={form.errors.group_ids} required hint="Every active subscriber of these groups receives it.">
                        <Select
                            resource="api.v1.admin.newsletter-groups.index"
                            initialOptions={groupSeed}
                            bind:value={form.data.group_ids}
                            multiple
                            placeholder="Select groups"
                        />
                    </Field>

                    <Field label="Email integration" error={form.errors.integration_id} hint="Leave empty to use the app default mailer.">
                        <Select
                            resource={emailTypeId ? 'api.v1.admin.integrations.index' : null}
                            resourceParams={{ filter: { integration_type_id: emailTypeId } }}
                            bind:value={form.data.integration_id}
                            clearable
                            initialOptions={integrationSeed}
                            placeholder="App default mailer"
                        />
                    </Field>

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <Field
                            label="Status"
                            error={form.errors.status}
                            required
                            hint="Sending happens on the scheduled minute, through the queue — there is no send-now, so sending now means scheduling it for now."
                        >
                            <Select options={statusOptions} bind:value={form.data.status} clearable={false} />
                        </Field>
                        {#if needsScheduledAt(form.data.status)}
                            <Field label="Schedule at" error={form.errors.scheduled_at} required hint="Must be in the future.">
                                <DatePicker enableTime bind:value={form.data.scheduled_at} invalid={!!form.errors.scheduled_at} />
                            </Field>
                        {/if}
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <Field label="Content" error={form.errors.content} required hint="The email body.">
                            <!-- The only place these two are on: links may attach a
                                 document, and the body needs the send-time tokens. -->
                            <HtmlEditor bind:value={form.data.content} {ready} fileUpload {placeholders} />
                        </Field>
                        <p class="text-xs text-muted-foreground">
                            The <span class="font-medium text-mono">Placeholders</span> menu inserts
                            <span class="font-medium text-mono">{UNSUBSCRIBE_PLACEHOLDER}</span> and
                            <span class="font-medium text-mono">{EMAIL_PLACEHOLDER}</span> — each is filled in with that
                            recipient's own unsubscribe link and address when the newsletter is sent.
                        </p>
                    </div>
                </div>
            {/if}
        </div>
    {/if}

    {#if showTabs && activeTab === 'translations'}
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
