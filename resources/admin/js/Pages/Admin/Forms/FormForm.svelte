<script>
    /**
     * Form create/edit — settings only. The pages and fields live in the builder.
     *
     * TWO TABS, and only while editing: General and Translations. Everything the
     * form can be told about itself is a CARD in the General flow — the same
     * shape as the newsletter form — rather than a tab of its own. Tabs hid the
     * settings behind a click each and, worse, made them look like a second
     * stage: create could not reach them at all, so a form went live in the gap
     * between saving it and finding the tab, uncapped and unfiltered.
     *
     * So create renders the SAME cards as edit. The only thing create does
     * differently is its copy: one card in the default language, because there
     * is nothing to translate until the form exists.
     *
     * A system form keeps every card, but its slug is frozen (the app resolves
     * it by slug) and its structure is untouchable. Both are enforced
     * server-side; disabling the input here is a courtesy, not the guard.
     *
     * The four child lists (notified groups, webhooks, blocked countries,
     * blocked addresses) are their own drawers and cannot be edited inside this
     * form — a form field belongs to a payload, and those are separate records
     * with separate endpoints and their own permission codes. The card that
     * talks about them is where an admin is already thinking about them, so each
     * is one button away here: `onmanage(key)` hands the key up to the index,
     * which owns the drawers. They need a saved form to hang off, so they are
     * drawn on edit only.
     */
    import Field from '@/components/form/Field.svelte';
    import Input from '@/components/form/Input.svelte';
    import SlugInput from '@/components/form/SlugInput.svelte';
    import Select from '@/components/form/Select.svelte';
    import Switch from '@/components/form/Switch.svelte';
    import DatePicker from '@/components/form/DatePicker.svelte';
    import HtmlEditor from '@/components/form/HtmlEditor.svelte';
    import Tabs from '@/components/ui/Tabs.svelte';
    import Button from '@/components/ui/Button.svelte';
    import Alert from '@/components/feedback/Alert.svelte';
    import MediaPicker from '@/components/media/MediaPicker.svelte';
    import { useForm } from '@/lib/api/useForm.svelte';
    import { api } from '@/lib/api/client';
    import { toast } from '@/lib/toast';
    import { hasPermission } from '@/lib/permissions';
    import {
        FORM_STATUS_LABELS,
        LIMIT_BY_OPTIONS,
        CONFIRMATION_TYPE_OPTIONS,
        needsPublishedAt,
        reachableStatuses,
    } from '@/lib/form';

    let { form: record = null, ready = true, onsaved, oncancel, onmanage } = $props();

    const editing = $derived(!!record);

    let languages = $state([]);
    let activeTab = $state('general');
    let activeLocale = $state(null);
    let defaultCategory = $state(null);

    const TRANSLATABLE = ['title', 'description', 'content', 'confirmation_message'];

    /*
     * The settings half of the payload — identical on both sides, which is the
     * whole point: create and edit send the same keys, so StoreFormData and
     * UpdateFormData validate the same shape. Only the copy differs.
     */
    const settings = (r) => ({
        is_limited: !!r?.is_limited,
        submissions_limit: r?.submissions_limit ?? null,
        is_user_limited: !!r?.is_user_limited,
        per_user_limit: r?.per_user_limit ?? null,
        per_user_limit_by: r?.per_user_limit_by ?? 'ip',

        is_spam_filtered: r ? !!r.is_spam_filtered : true,
        min_submit_seconds: r?.min_submit_seconds ?? null,
        is_captcha_enabled: !!r?.is_captcha_enabled,
        is_ip_stored: r ? !!r.is_ip_stored : true,

        confirmation_type: r?.confirmation_type ?? 'message',
        redirect_url: r?.redirect_url ?? '',

        captcha_integration_id: r?.captcha_integration_id ?? null,
        analytics_integration_id: r?.analytics_integration_id ?? null,
    });

    const form = useForm(
        record
            ? {
                  name: record.name ?? '',
                  slug: record.slug ?? '',
                  category_id: record.category?.id ?? record.category_id ?? null,
                  title: { ...(record.title ?? {}) },
                  description: { ...(record.description ?? {}) },
                  content: { ...(record.content ?? {}) },
                  confirmation_message: { ...(record.confirmation_message ?? {}) },
                  status: record.status ?? 'draft',
                  published_at: record.published_at ? record.published_at.slice(0, 16).replace('T', ' ') : null,
                  css_url: record.css_url ?? '',
                  custom_css: record.custom_css ?? '',

                  ...settings(record),

                  thumbnail: null,
              }
            : {
                  name: '',
                  slug: '',
                  category_id: null,
                  // Create sends the default locale as a plain string.
                  title: '',
                  description: '',
                  content: '',
                  confirmation_message: '',
                  status: 'draft',
                  published_at: null,
                  css_url: '',
                  custom_css: '',

                  ...settings(null),

                  thumbnail: null,
              },
    );

    const statusOptions = $derived(
        reachableStatuses(record?.status ?? null).map((value) => ({ value, label: FORM_STATUS_LABELS[value] ?? value })),
    );

    const activeLanguage = $derived(languages.find((l) => l.code === activeLocale) ?? null);

    const tabs = [
        { id: 'general', label: 'General', icon: 'ki-filled ki-document' },
        { id: 'translations', label: 'Translations', icon: 'ki-filled ki-flag' },
    ];

    /** Any validation error under this locale, so a collapsed tab isn't a mystery. */
    function localeHasError(code) {
        return TRANSLATABLE.some((field) => !!form.errors[`${field}.${code}`]);
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
                        for (const field of TRANSLATABLE) {
                            if (form.data[field][l.code] === undefined) form.data[field][l.code] = '';
                        }
                    }
                }
            })
            .catch(() => {});
    });

    // Preselect the default category on create, matching the server's fallback.
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
        // No new pick means "keep the current file" — omitting it says so. On
        // create there is nothing to keep, and the server answers with the
        // required error, which is the correct one.
        if (!out.thumbnail) delete out.thumbnail;
        if (!needsPublishedAt(out.status)) out.published_at = null;

        return out;
    }

    async function submit(event) {
        event.preventDefault();
        const url = editing ? route('api.v1.admin.forms.update', record.id) : route('api.v1.admin.forms.store');
        try {
            const res = await form.submit(editing ? 'put' : 'post', url, { transform: payload });
            if (res) {
                toast.success(editing ? 'Updated successfully.' : 'Created successfully.');
                onsaved?.();
            } else if (editing && languages.some((l) => localeHasError(l.code))) {
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
        <Tabs {tabs} bind:active={activeTab} />
    {/if}

    {#if !editing || activeTab === 'general'}
        {#if editing && record?.is_system}
            <Alert variant="info">
                This form ships with the app. Its settings are yours to change, but its slug is fixed and its
                pages and fields cannot be edited or deleted.
            </Alert>
        {/if}

        <!-- CARD 1 — what the form is, and whether it is public. -->
        <div class="kt-card">
            <div class="kt-card-header">
                <h3 class="kt-card-title">General</h3>
                <span class="text-xs text-muted-foreground">What it is called and where it lives</span>
            </div>
            <div class="kt-card-content flex flex-col gap-5 p-5">
                <Field
                    label="Thumbnail"
                    error={form.errors.thumbnail}
                    required
                    hint={editing ? 'Pick a new image to replace the current one.' : 'Shown wherever the form is listed.'}
                >
                    <MediaPicker accept={['images']} bind:value={form.data.thumbnail} previewUrl={record?.thumbnail_url} />
                </Field>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <Field label="Name" error={form.errors.name} required hint="Internal label — not shown to the public.">
                        <Input bind:value={form.data.name} invalid={!!form.errors.name} />
                    </Field>
                    <Field
                        label="Slug"
                        error={form.errors.slug}
                        required
                        hint={record?.is_system ? 'Fixed: the app resolves this form by its slug.' : 'Filled in from the name until you edit it. This is the public URL.'}
                    >
                        <SlugInput
                            bind:value={form.data.slug}
                            source={form.data.name}
                            disabled={!!record?.is_system}
                            invalid={!!form.errors.slug}
                        />
                    </Field>
                </div>

                <Field label="Category" error={form.errors.category_id} hint="Defaults to the fallback category if you leave it alone.">
                    <Select
                        resource="api.v1.admin.categories.index"
                        bind:value={form.data.category_id}
                        labelKey="name"
                        placeholder="Search categories…"
                        initialOptions={record?.category
                            ? [{ value: record.category.id, label: record.category.name }]
                            : defaultCategory
                              ? [{ value: defaultCategory.id, label: defaultCategory.name }]
                              : []}
                    />
                </Field>

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

                <Field
                    label="Stylesheet URL"
                    error={form.errors.css_url}
                    hint="Optional. Every page and element carries a CSS id and class you set in the builder, and this stylesheet targets them."
                >
                    <Input bind:value={form.data.css_url} invalid={!!form.errors.css_url} placeholder="https://…" />
                </Field>

                <Field
                    label="Custom CSS"
                    error={form.errors.custom_css}
                    hint="Optional inline CSS for this form. Loaded after the stylesheet above, so rules here win — use it for the few tweaks not worth a hosted file."
                >
                    <textarea
                        class="kt-input min-h-[90px] font-mono text-2sm"
                        class:border-destructive={!!form.errors.custom_css}
                        bind:value={form.data.custom_css}
                    ></textarea>
                </Field>
            </div>
        </div>

        {#if !editing}
            <!-- CARD 2 (create only) — the default language's copy, inline.
                 Everything here becomes a per-language tab once the form exists. -->
            <div class="kt-card">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">Copy</h3>
                    <span class="text-xs text-muted-foreground">Translatable once created</span>
                </div>
                <div class="kt-card-content flex flex-col gap-5 p-5">
                    <Field label="Title" error={form.errors.title} required hint="Shown at the top of the public form.">
                        <Input bind:value={form.data.title} invalid={!!form.errors.title} />
                    </Field>
                    <Field label="Description" error={form.errors.description} required hint="Shown under the title, and wherever the form is listed.">
                        <textarea class="kt-input min-h-[90px]" class:border-destructive={!!form.errors.description} bind:value={form.data.description}></textarea>
                    </Field>
                    <Field label="Confirmation message" error={form.errors.confirmation_message} hint="Shown after a successful submission.">
                        <textarea class="kt-input min-h-[70px]" class:border-destructive={!!form.errors.confirmation_message} bind:value={form.data.confirmation_message}></textarea>
                    </Field>
                </div>
            </div>
        {/if}

        <!-- CARD 3 — how many answers it takes, and what happens after one. -->
        <div class="kt-card">
            <div class="kt-card-header">
                <h3 class="kt-card-title">Submissions</h3>
                <span class="text-xs text-muted-foreground">How many, and what happens next</span>
            </div>
            <div class="kt-card-content flex flex-col gap-5 p-5">
                <Field label="Limit the total number of submissions" error={form.errors.is_limited}>
                    <div class="flex items-center gap-3">
                        <Switch bind:value={form.data.is_limited} />
                        {#if editing}
                            <span class="text-sm text-muted-foreground">
                                {record?.submissions_count ?? 0} received so far.
                            </span>
                        {/if}
                    </div>
                </Field>

                {#if form.data.is_limited}
                    <Field label="Maximum submissions" error={form.errors.submissions_limit} required hint="The form stops accepting once it reaches this.">
                        <Input type="number" bind:value={form.data.submissions_limit} invalid={!!form.errors.submissions_limit} />
                    </Field>
                {/if}

                <Field label="Limit submissions per visitor" error={form.errors.is_user_limited}>
                    <Switch bind:value={form.data.is_user_limited} />
                </Field>

                {#if form.data.is_user_limited}
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <Field label="Maximum per visitor" error={form.errors.per_user_limit} required>
                            <Input type="number" bind:value={form.data.per_user_limit} invalid={!!form.errors.per_user_limit} />
                        </Field>
                        <Field
                            label="Identify a visitor by"
                            error={form.errors.per_user_limit_by}
                            required
                            hint="A device signature is approximate — it is a speed bump, not an identity."
                        >
                            <Select options={LIMIT_BY_OPTIONS} bind:value={form.data.per_user_limit_by} clearable={false} />
                        </Field>
                    </div>
                {/if}

                <Field label="After submitting" error={form.errors.confirmation_type} required>
                    <Select options={CONFIRMATION_TYPE_OPTIONS} bind:value={form.data.confirmation_type} clearable={false} />
                </Field>

                {#if form.data.confirmation_type === 'redirect'}
                    <Field label="Redirect to" error={form.errors.redirect_url} required>
                        <Input bind:value={form.data.redirect_url} invalid={!!form.errors.redirect_url} placeholder="https://…" />
                    </Field>
                {:else}
                    <p class="text-2sm text-muted-foreground">
                        {editing
                            ? 'The confirmation message is translated — see the Translations tab.'
                            : 'The confirmation message is in the Copy card above.'}
                    </p>
                {/if}
            </div>
        </div>

        <!-- CARD 4 — who gets to submit at all. -->
        <div class="kt-card">
            <div class="kt-card-header">
                <h3 class="kt-card-title">Spam &amp; security</h3>
                <span class="text-xs text-muted-foreground">Who may submit</span>
            </div>
            <div class="kt-card-content flex flex-col gap-5 p-5">
                <Field
                    label="Spam filter"
                    error={form.errors.is_spam_filtered}
                    hint="Adds a hidden field a person never sees. Its name changes on every render, so it cannot be learned."
                >
                    <Switch bind:value={form.data.is_spam_filtered} />
                </Field>

                {#if form.data.is_spam_filtered}
                    <Field
                        label="Minimum time before submitting"
                        error={form.errors.min_submit_seconds}
                        hint="Seconds. Anything faster is treated as a bot. Leave empty to use the site default."
                    >
                        <Input type="number" bind:value={form.data.min_submit_seconds} invalid={!!form.errors.min_submit_seconds} placeholder="5" />
                    </Field>
                {/if}

                <Field
                    label="Store the visitor's IP address"
                    error={form.errors.is_ip_stored}
                    hint="A hashed copy is always kept — it is what enforces the limits and the blocks. This stores the readable one too."
                >
                    <Switch bind:value={form.data.is_ip_stored} />
                </Field>

                {#if editing}
                    {@render connections('Who may submit', [
                        { key: 'blocked-countries', label: 'Blocked countries', icon: 'ki-geolocation', permission: 'form-blocked-countries.index' },
                        { key: 'blocked-ips', label: 'Blocked addresses', icon: 'ki-shield-cross', permission: 'form-blocked-ips.index' },
                    ])}
                {/if}
            </div>
        </div>

        <!-- CARD 5 — the accounts this form borrows, and where its answers go. -->
        <div class="kt-card">
            <div class="kt-card-header">
                <h3 class="kt-card-title">Integrations</h3>
                <span class="text-xs text-muted-foreground">The accounts this form uses</span>
            </div>
            <div class="kt-card-content flex flex-col gap-5 p-5">
                <Field label="Require a captcha" error={form.errors.is_captcha_enabled}>
                    <Switch bind:value={form.data.is_captcha_enabled} />
                </Field>

                {#if form.data.is_captcha_enabled}
                    <Field
                        label="Captcha integration"
                        error={form.errors.captcha_integration_id}
                        required
                        hint="The form renders this provider's site key and verifies against its secret."
                    >
                        <Select
                            resource="api.v1.admin.integrations.index"
                            resourceParams={{ filter: { type: 'captcha', is_enabled: 1 } }}
                            bind:value={form.data.captcha_integration_id}
                            labelKey="name"
                            placeholder="Search captcha integrations…"
                            initialOptions={record?.captcha_integration
                                ? [{ value: record.captcha_integration.id, label: record.captcha_integration.name }]
                                : []}
                        />
                    </Field>
                {/if}

                <Field
                    label="Analytics integration"
                    error={form.errors.analytics_integration_id}
                    hint="Optional. Reports views, steps and completions to the connected analytics account."
                >
                    <Select
                        resource="api.v1.admin.integrations.index"
                        resourceParams={{ filter: { type: 'analytics', is_enabled: 1 } }}
                        bind:value={form.data.analytics_integration_id}
                        labelKey="name"
                        placeholder="Search analytics integrations…"
                        initialOptions={record?.analytics_integration
                            ? [{ value: record.analytics_integration.id, label: record.analytics_integration.name }]
                            : []}
                    />
                </Field>

                {#if editing}
                    {@render connections('Where submissions go', [
                        { key: 'notification-groups', label: 'Notified groups', icon: 'ki-notification-status', permission: 'form-notification-groups.index' },
                        { key: 'webhooks', label: 'Webhooks', icon: 'ki-cloud-change', permission: 'form-webhooks.index' },
                    ])}
                {/if}
            </div>
        </div>
    {/if}

    {#if editing && activeTab === 'translations'}
        <div class="kt-card">
            <div class="kt-card-header">
                <h3 class="kt-card-title">Translations</h3>
                <span class="text-xs text-muted-foreground">The public copy, per language</span>
            </div>
            <div class="kt-card-content flex flex-col gap-5 p-5">
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
                        label="Confirmation message"
                        error={form.errors[`confirmation_message.${activeLocale}`]}
                        required={!!activeLanguage?.is_default}
                        hint="Shown after a successful submission."
                    >
                        <textarea
                            class="kt-input min-h-[70px]"
                            class:border-destructive={!!form.errors[`confirmation_message.${activeLocale}`]}
                            dir={activeLanguage?.is_rtl ? 'rtl' : 'ltr'}
                            bind:value={form.data.confirmation_message[activeLocale]}
                        ></textarea>
                    </Field>

                    <Field
                        label="Intro content"
                        error={form.errors[`content.${activeLocale}`]}
                        hint="Optional rich content rendered above the first page of the form."
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
        </div>
    {/if}

    <div class="flex items-center justify-end gap-3 border-t border-border pt-4">
        <Button variant="secondary" onclick={oncancel}>Cancel</Button>
        <Button variant="primary" type="submit" loading={form.processing}>Save</Button>
    </div>
</form>

<!--
    The child lists this card talks about. Each is its own record with its own
    endpoint and permission code, so the button hands a key to the index rather
    than pretending to edit them here. Only lists this admin may read are drawn;
    if none are, the whole block disappears rather than leaving a heading over
    nothing.
-->
{#snippet connections(heading, items)}
    {@const shown = items.filter((item) => hasPermission(item.permission))}
    {#if shown.length}
        <div class="flex flex-col gap-2.5 border-t border-border pt-4">
            <span class="text-2sm font-medium text-mono">{heading}</span>

            {#if onmanage}
                <div class="flex flex-wrap gap-2">
                    {#each shown as item (item.key)}
                        <Button variant="secondary" size="sm" onclick={() => onmanage(item.key)}>
                            <i class="ki-filled {item.icon}"></i>{item.label}
                        </Button>
                    {/each}
                </div>
                <span class="text-2sm text-muted-foreground">
                    Each opens over this form — anything you have typed here is still here when you close it.
                    They are also on the form's actions menu in the table.
                </span>
            {:else}
                <!-- Rendered outside the index (no callback): name the real place
                     rather than promise something this component cannot do. -->
                <span class="text-2sm text-muted-foreground">
                    {shown.map((item) => item.label).join(' and ')} — on the form's actions menu in the table.
                </span>
            {/if}
        </div>
    {/if}
{/snippet}
