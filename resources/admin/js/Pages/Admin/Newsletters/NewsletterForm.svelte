<script>
    /**
     * Newsletter create/edit — THREE cards, one per concern, so the two sides can
     * never be read as one:
     *
     *   General  — the name and the two switches.
     *   Website  — file, translated title, published_status + published_at.
     *   Email    — subject, groups, integration, sent_status + sent_at, body.
     *
     * Each side card only exists while its switch is on, and its fields are
     * stripped from the payload when it is off, so a switch turned back off never
     * leaves stale values on the record.
     *
     * Neither status is a direct write: picking Published or Sent means "now", and
     * the server schedules it for this instant for the matching command to pick up.
     * That is why re-scheduling an already-sent issue is confirmed here, at the
     * moment of the decision, rather than on submit.
     *
     * `title` is the only translatable field, so the per-language tabs live inside
     * the Website card — with publishing off there are no tabs at all.
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
    import Button from '@/components/ui/Button.svelte';
    import { useForm } from '@/lib/api/useForm.svelte';
    import { api } from '@/lib/api/client';
    import { toast } from '@/lib/toast';
    import { confirm } from '@/lib/confirm';
    import { formatDateTime } from '@/lib/date';
    import {
        EMAIL_PLACEHOLDER,
        NEWSLETTER_PUBLISH_STATUS_LABELS,
        NEWSLETTER_SEND_STATUS_LABELS,
        UNSUBSCRIBE_PLACEHOLDER,
        needsPublishAt,
        needsSendAt,
        publishReachableStatuses,
        sendReachableStatuses,
    } from '@/lib/newsletter';

    let { newsletter = null, ready = true, onsaved, oncancel } = $props();

    const editing = $derived(!!newsletter);

    // The two ladders, resolved separately and never crossed. A failed issue is
    // offered a way out but never a way back into failed.
    const publishStatuses = publishReachableStatuses(newsletter?.published_status ?? null);
    const sendStatuses = sendReachableStatuses(newsletter?.sent_status ?? null);

    const publishStatusOptions = publishStatuses.map((value) => ({ value, label: NEWSLETTER_PUBLISH_STATUS_LABELS[value] ?? value }));
    const sendStatusOptions = sendStatuses.map((value) => ({ value, label: NEWSLETTER_SEND_STATUS_LABELS[value] ?? value }));

    // The mail genuinely went out — leaving `sent` re-sends it, so ask first.
    const alreadySent = !!(newsletter?.sent_status === 'sent' && newsletter?.sent_at);

    // Both tokens ShipNewsletter substitutes; the unsubscribe one must land as a link.
    const placeholders = [
        { label: 'Unsubscribe link', html: `<a href="${UNSUBSCRIBE_PLACEHOLDER}">Unsubscribe</a>` },
        { label: 'Recipient email', value: EMAIL_PLACEHOLDER },
    ];

    let languages = $state([]);
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
        published_status: publishStatuses.includes(newsletter?.published_status) ? newsletter.published_status : publishStatuses[0],
        published_at: newsletter?.published_at ? newsletter.published_at.slice(0, 16).replace('T', ' ') : null,
        subject: newsletter?.subject ?? '',
        content: newsletter?.content ?? '',
        integration_id: newsletter?.integration_id ?? null,
        sent_status: sendStatuses.includes(newsletter?.sent_status) ? newsletter.sent_status : sendStatuses[0],
        sent_at: newsletter?.sent_at ? newsletter.sent_at.slice(0, 16).replace('T', ' ') : null,
        group_ids: [...(newsletter?.group_ids ?? [])],
    });

    // The last send status the admin has agreed to — what a declined confirm reverts to.
    let lastSendStatus = $state(form.data.sent_status);

    const activeLanguage = $derived(languages.find((l) => l.code === activeLocale) ?? null);

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

    /**
     * Confirm at the moment of the decision, not on submit: leaving `sent` puts the
     * issue back in the queue and every subscriber gets it a second time. Going
     * back to `sent` is the safe original, so it never asks.
     */
    async function onSendStatusChange(next) {
        if (!alreadySent || next === 'sent') {
            lastSendStatus = next;

            return;
        }

        const ok = await confirm({
            title: 'Send this newsletter again?',
            body: `This newsletter was already sent on ${formatDateTime(newsletter.sent_at)}. Re-scheduling it will send it again to every subscriber of its groups.`,
            confirmLabel: 'Re-schedule',
            variant: 'destructive',
        });

        if (!ok) {
            form.data.sent_status = lastSendStatus;

            return;
        }

        lastSendStatus = next;
    }

    function payload(data) {
        const out = { ...data };

        // Send nothing for a switch that is off: the server must see nulls, not
        // whatever the hidden card still held from before it was turned off.
        if (out.is_published) {
            // No new pick means "keep the current file" — omitting it says so.
            if (!out.file) delete out.file;
            if (!needsPublishAt(out.published_status)) out.published_at = null;
        } else {
            delete out.file;
            // {} merges nothing, so switching publishing back on finds its title intact.
            out.title = editing ? {} : null;
            out.published_status = 'draft';
            out.published_at = null;
        }

        if (out.is_sendable) {
            if (!needsSendAt(out.sent_status)) out.sent_at = null;
        } else {
            out.subject = null;
            out.content = null;
            out.group_ids = [];
            out.sent_status = 'draft';
            out.sent_at = null;
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
            } else if (editing && form.data.is_published) {
                // The failing title may be behind a tab the user can't see.
                const failing = languages.find((l) => localeHasError(l.code));
                if (failing) activeLocale = failing.code;
            }
        } catch (err) {
            toast.error(err?.message ?? 'Something went wrong. Please try again.');
        }
    }
</script>

<form class="flex w-full flex-col gap-5" onsubmit={submit}>
    <!-- CARD 1 — what the issue is, and which of the two sides apply at all. -->
    <div class="kt-card">
        <div class="kt-card-header">
            <h3 class="kt-card-title">General</h3>
        </div>
        <div class="kt-card-content flex flex-col gap-5 p-5">
            <Field label="Name" error={form.errors.name} required hint="Internal label — not shown to the public or to subscribers.">
                <Input bind:value={form.data.name} invalid={!!form.errors.name} />
            </Field>

            <!-- Two switches, not a choice: an issue can be archived on the site,
                 emailed, or both. At least one must be on. -->
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <Field
                    label="Publish on website"
                    error={form.errors.is_published}
                    hint="Puts the issue in the public archive — needs a file and a title."
                >
                    <Switch bind:value={form.data.is_published} />
                </Field>
                <Field
                    label="Send by email"
                    error={form.errors.is_sendable}
                    hint="Emails the issue to its groups — needs a subject, a body and an audience."
                >
                    <Switch bind:value={form.data.is_sendable} />
                </Field>
            </div>
        </div>
    </div>

    {#if form.data.is_published}
        <!-- CARD 2 — the website archive alone. Nothing here affects the email. -->
        <div class="kt-card">
            <div class="kt-card-header">
                <h3 class="kt-card-title">Website</h3>
                <span class="text-xs text-muted-foreground">The public archive</span>
            </div>
            <div class="kt-card-content flex flex-col gap-5 p-5">
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
                {:else}
                    <div class="flex flex-col gap-5">
                        <div class="kt-tabs kt-tabs-line overflow-x-auto" role="tablist">
                            {#each languages as language (language.code)}
                                <!-- The BARE data-kt-tab-toggle attribute is what Metronic
                                     styles the active tab off — the class alone is not enough. -->
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
                            <Field
                                label="Title"
                                error={form.errors[`title.${activeLocale}`]}
                                required={!!activeLanguage?.is_default}
                                hint="The public label, per language."
                            >
                                <Input
                                    bind:value={form.data.title[activeLocale]}
                                    invalid={!!form.errors[`title.${activeLocale}`]}
                                    dir={activeLanguage?.is_rtl ? 'rtl' : 'ltr'}
                                />
                            </Field>
                        {/if}
                    </div>
                {/if}

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <Field
                        label="Publish status"
                        error={form.errors.published_status}
                        required
                        hint="The archive only — it never affects the email send. Publishing goes live on the next minute's run."
                    >
                        <Select options={publishStatusOptions} bind:value={form.data.published_status} clearable={false} />
                    </Field>
                    {#if needsPublishAt(form.data.published_status)}
                        <Field label="Publish at" error={form.errors.published_at} required hint="Must be in the future — it goes live automatically.">
                            <DatePicker enableTime bind:value={form.data.published_at} invalid={!!form.errors.published_at} />
                        </Field>
                    {/if}
                </div>
            </div>
        </div>
    {/if}

    {#if form.data.is_sendable}
        <!-- CARD 3 — the email broadcast alone. Nothing here affects the archive. -->
        <div class="kt-card">
            <div class="kt-card-header">
                <h3 class="kt-card-title">Email</h3>
                <span class="text-xs text-muted-foreground">The broadcast to subscribers</span>
            </div>
            <div class="kt-card-content flex flex-col gap-5 p-5">
                {#if alreadySent}
                    <div class="flex items-center gap-1.5 text-xs text-muted-foreground">
                        <i class="ki-filled ki-check-circle text-success"></i>
                        Sent on {formatDateTime(newsletter.sent_at)}
                    </div>
                {/if}

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

                <Field
                    label="Email integration"
                    error={form.errors.integration_id}
                    required
                    hint="The account this broadcast goes out from."
                >
                    <Select
                        resource={emailTypeId ? 'api.v1.admin.integrations.index' : null}
                        resourceParams={{ filter: { integration_type_id: emailTypeId } }}
                        bind:value={form.data.integration_id}
                        initialOptions={integrationSeed}
                        placeholder="Select an integration"
                    />
                </Field>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <Field
                        label="Send status"
                        error={form.errors.sent_status}
                        required
                        hint="Sending happens on the scheduled minute, through the queue — there is no send-now, so sending now means scheduling it for now."
                    >
                        <Select
                            options={sendStatusOptions}
                            bind:value={form.data.sent_status}
                            onchange={onSendStatusChange}
                            clearable={false}
                        />
                    </Field>
                    {#if needsSendAt(form.data.sent_status)}
                        <Field label="Send at" error={form.errors.sent_at} required hint="Must be in the future.">
                            <DatePicker enableTime bind:value={form.data.sent_at} invalid={!!form.errors.sent_at} />
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
        </div>
    {/if}

    <div class="flex items-center justify-end gap-3 border-t border-border pt-4">
        <Button variant="secondary" onclick={oncancel}>Cancel</Button>
        <Button variant="primary" type="submit" loading={form.processing}>Save</Button>
    </div>
</form>
