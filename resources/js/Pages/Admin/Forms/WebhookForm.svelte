<script>
    /**
     * Webhook create/edit — the fly-in form inside the Webhooks drawer.
     *
     * SECRETS ARE WRITE-ONLY. The server never sends a token or header value back
     * (auth_config is encrypted, $hidden and absent from the DTO), so every secret
     * box starts empty and a blank one means "keep the stored value" — the same
     * contract SchemaField has with integrations. What the row does carry is
     * `has_auth_secret` and, for static headers, the header NAMES, which is enough
     * to prefill the repeater without revealing anything.
     *
     * Changing the auth TYPE keeps nothing: the server discards the old config
     * (a bearer token carried into `headers` would go out as a header literally
     * named "token"), so the new type's secret is required again.
     *
     * THE PAYLOAD IS NOT CONFIGURABLE. Every delivery sends the submission id,
     * the form slug, the submitted time and the whole answer set under `data` —
     * parsing that is the receiving system's responsibility.
     */
    import Field from '@/components/form/Field.svelte';
    import Input from '@/components/form/Input.svelte';
    import PasswordInput from '@/components/form/PasswordInput.svelte';
    import Switch from '@/components/form/Switch.svelte';
    import Button from '@/components/ui/Button.svelte';
    import Badge from '@/components/ui/Badge.svelte';
    import DateTime from '@/components/ui/DateTime.svelte';
    import { useForm } from '@/lib/api/useForm.svelte';
    import { toast } from '@/lib/toast';
    import { WEBHOOK_METHODS, WEBHOOK_AUTH_TYPES } from '@/lib/formWebhook';

    let { formId, webhook = null, onsaved, oncancel } = $props();

    const editing = $derived(!!webhook);

    const form = useForm({
        name: webhook?.name ?? '',
        url: webhook?.url ?? '',
        method: webhook?.method ?? 'POST',
        is_enabled: webhook?.is_enabled ?? true,
        auth_type: webhook?.auth_type ?? 'none',
        // Write-only. The header ROWS are seeded from the stored names with empty
        // values, so an admin can reorder or drop one without retyping the rest.
        auth: {
            token: '',
            headers: (webhook?.auth_header_keys ?? []).map((key) => ({ key, value: '' })),
        },
    });

    const storedSecret = $derived(!!webhook?.has_auth_secret && webhook?.auth_type === form.data.auth_type);

    function addHeader() {
        form.data.auth.headers.push({ key: '', value: '' });
    }

    function removeHeader(index) {
        form.data.auth.headers.splice(index, 1);
    }

    /** Only the branch the chosen type uses — the rest is not the server's business. */
    function authPayload(data) {
        if (data.auth_type === 'bearer') return { token: data.auth.token };
        if (data.auth_type === 'headers') {
            return { headers: data.auth.headers.map((h) => ({ key: h.key, value: h.value })) };
        }

        return {};
    }

    async function submit(event) {
        event.preventDefault();

        const url = editing
            ? route('api.v1.admin.form-webhooks.update', webhook.id)
            : route('api.v1.admin.form-webhooks.store', formId);

        try {
            const res = await form.submit(editing ? 'put' : 'post', url, {
                transform: (data) => ({
                    name: data.name,
                    url: data.url,
                    method: data.method,
                    is_enabled: data.is_enabled,
                    auth_type: data.auth_type,
                    auth: authPayload(data),
                }),
            });

            if (res) {
                toast.success(editing ? 'Updated successfully.' : 'Created successfully.');
                onsaved?.();
            }
        } catch (err) {
            toast.error(err?.message ?? 'Something went wrong. Please try again.');
        }
    }
</script>

<form class="flex w-full flex-col gap-5" onsubmit={submit}>
    <!-- Last delivery, read-only: written by the job, never by this form. WHEN
         is all the row keeps — no status code and no failure counter, because
         what a response actually was belongs in the integrations log. -->
    {#if editing}
        <div class="flex flex-wrap items-center gap-x-6 gap-y-2 rounded-lg border border-border bg-muted/40 px-4 py-3">
            <div class="flex items-center gap-2">
                <span class="text-2sm text-muted-foreground">Last delivery</span>
                {#if webhook.last_delivered_at}
                    <span class="text-2sm text-mono"><DateTime value={webhook.last_delivered_at} /></span>
                {:else}
                    <Badge variant="secondary" size="sm">Never sent</Badge>
                {/if}
            </div>
        </div>
    {/if}

    <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
        <div class="sm:col-span-2">
            <Field label="Name" error={form.errors.name} required hint="Internal label — the receiver never sees it.">
                <Input bind:value={form.data.name} invalid={!!form.errors.name} />
            </Field>
        </div>
        <Field label="Method" error={form.errors.method} required>
            <select class="kt-input" bind:value={form.data.method}>
                {#each WEBHOOK_METHODS as method}
                    <option value={method}>{method}</option>
                {/each}
            </select>
        </Field>
    </div>

    <Field label="Endpoint URL" error={form.errors.url} required hint="Must be http or https.">
        <Input bind:value={form.data.url} invalid={!!form.errors.url} placeholder="https://example.com/hooks/forms" />
    </Field>

    <Field label="Enabled" error={form.errors.is_enabled} hint="A disabled webhook is skipped at delivery time and keeps its history.">
        <div class="flex items-center"><Switch bind:value={form.data.is_enabled} /></div>
    </Field>

    <!-- Auth -->
    <div class="flex flex-col gap-4 border-t border-border pt-4">
        <Field label="Authentication" error={form.errors.auth_type} required>
            <select class="kt-input" bind:value={form.data.auth_type}>
                {#each WEBHOOK_AUTH_TYPES as type}
                    <option value={type.value}>{type.label}</option>
                {/each}
            </select>
        </Field>

        {#if form.data.auth_type === 'bearer'}
            <Field
                label="Token"
                error={form.errors['auth.token']}
                required={!storedSecret}
                hint="Sent as `Authorization: Bearer …`."
            >
                <PasswordInput
                    bind:value={form.data.auth.token}
                    invalid={!!form.errors['auth.token']}
                    autocomplete="new-password"
                    placeholder={storedSecret ? 'Leave blank to keep current' : ''}
                />
            </Field>
        {:else if form.data.auth_type === 'headers'}
            <div class="flex flex-col gap-2">
                <div class="flex items-center justify-between">
                    <span class="text-2sm font-medium">Static headers</span>
                    <button type="button" class="kt-btn kt-btn-xs kt-btn-secondary" onclick={addHeader}>
                        <i class="ki-filled ki-plus"></i>Add header
                    </button>
                </div>
                <span class="text-xs text-muted-foreground">
                    Content-Type and Accept are set on every delivery and cannot be overridden here.
                </span>
                {#if form.errors['auth.headers']}
                    <p class="text-xs text-destructive">{form.errors['auth.headers']}</p>
                {/if}

                {#each form.data.auth.headers as header, i}
                    <div class="flex flex-col gap-1">
                        <div class="flex items-start gap-2">
                            <div class="w-2/5 shrink-0">
                                <Input
                                    bind:value={header.key}
                                    placeholder="X-Api-Key"
                                    invalid={!!form.errors[`auth.headers.${i}.key`]}
                                />
                            </div>
                            <div class="grow">
                                <PasswordInput
                                    bind:value={header.value}
                                    autocomplete="new-password"
                                    invalid={!!form.errors[`auth.headers.${i}.value`]}
                                    placeholder={webhook?.auth_header_keys?.includes(header.key) && webhook?.auth_type === 'headers'
                                        ? 'Leave blank to keep current'
                                        : 'Value'}
                                />
                            </div>
                            <button
                                type="button"
                                class="kt-btn kt-btn-icon kt-btn-sm kt-btn-destructive shrink-0"
                                aria-label="Remove header"
                                onclick={() => removeHeader(i)}
                            ><i class="ki-filled ki-cross"></i></button>
                        </div>
                        {#if form.errors[`auth.headers.${i}.key`] || form.errors[`auth.headers.${i}.value`]}
                            <span class="text-xs text-destructive">
                                {form.errors[`auth.headers.${i}.key`] ?? form.errors[`auth.headers.${i}.value`]}
                            </span>
                        {/if}
                    </div>
                {/each}

                {#if !form.data.auth.headers.length}
                    <p class="text-2sm text-muted-foreground">No headers yet.</p>
                {/if}
            </div>
        {/if}
    </div>

    <!-- What gets sent. Fixed, and stated here so nobody goes looking for a
         mapping screen: reshaping the payload is the receiver's job. -->
    <div class="flex flex-col gap-2 border-t border-border pt-4">
        <span class="text-2sm font-medium">Payload</span>
        <span class="text-xs text-muted-foreground">
            Every delivery sends the same body — the submission id, the form slug, the submitted time and
            every answer under <code class="text-mono">data</code>. It is not configurable: the receiving system
            parses what it needs.
        </span>
        <pre class="overflow-x-auto rounded-lg border border-border bg-muted/40 p-3 text-2xs text-mono">{JSON.stringify(
            {
                id: '01JQ8YV5T7M3K2QW9XZC4B6NHD',
                form: 'contact-us',
                submitted_at: '2026-01-01T09:00:00+00:00',
                data: { email: 'someone@example.com' },
            },
            null,
            2,
        )}</pre>
    </div>

    <div class="flex items-center justify-end gap-3 border-t border-border pt-4">
        <Button variant="secondary" onclick={oncancel}>Cancel</Button>
        <Button variant="primary" type="submit" loading={form.processing}>Save</Button>
    </div>
</form>
