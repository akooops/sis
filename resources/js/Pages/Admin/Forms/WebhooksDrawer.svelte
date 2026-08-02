<script>
    /**
     * Form → webhooks drawer.
     *
     * A webhook is NOT a pivot — it is a record with its own fields — but the
     * drawer shape is the same one every child list uses, so this is PivotDrawer
     * with a `form` snippet: the table flies out, the editor flies in. The index
     * is the flat endpoint scoped by filter[form_id]; store hangs off the form.
     *
     * Wider than the default drawer because the static-headers repeater is three
     * controls to a row, and 450px turns it into a column of stacked boxes.
     */
    import PivotDrawer from '@/components/data/PivotDrawer.svelte';
    import WebhookForm from './WebhookForm.svelte';
    import Badge from '@/components/ui/Badge.svelte';
    import ClampText from '@/components/ui/ClampText.svelte';
    import DateTime from '@/components/ui/DateTime.svelte';
    import { WEBHOOK_AUTH_LABELS } from '@/lib/formWebhook';

    let { open = $bindable(false), form = null } = $props();
</script>

<PivotDrawer
    bind:open
    title={`Webhooks — ${form?.name ?? ''}`}
    width="w-[760px]"
    parentId={form?.id}
    parentFilter={{ form_id: form?.id }}
    indexRoute="api.v1.admin.form-webhooks.index"
    destroyRoute="api.v1.admin.form-webhooks.destroy"
    columns={[
        { key: 'name', label: 'Name', truncate: false },
        { key: 'url', label: 'Endpoint', truncate: false },
        { key: 'auth_type', label: 'Auth', truncate: false, width: '130px' },
        { key: 'last_delivered_at', label: 'Last delivery', truncate: false, width: '190px' },
    ]}
    addLabel="Add webhook"
    searchPlaceholder="Search webhooks…"
    emptyTitle="No webhooks yet"
    emptyBody="Add one to POST each new submission to another system."
    confirmBody={(row) => `Delete ${row.name}? Submissions will stop being sent to ${row.url}. This cannot be undone.`}
    {cells}
    form={webhookForm}
/>

{#snippet cells(row, column)}
    {#if column.key === 'name'}
        <div class="flex items-center gap-2">
            <span class="text-sm font-medium text-mono">
                <ClampText value={row.name} maxWidth="180px" title={row.name} />
            </span>
            <Badge variant="secondary" size="sm">{row.method}</Badge>
            {#if !row.is_enabled}
                <Badge variant="warning" size="sm">Disabled</Badge>
            {/if}
        </div>
    {:else if column.key === 'url'}
        <ClampText value={row.url} maxWidth="220px" title={row.url} />
    {:else if column.key === 'auth_type'}
        <div class="flex items-center gap-1.5">
            <span class="text-2sm">{WEBHOOK_AUTH_LABELS[row.auth_type] ?? row.auth_type}</span>
            {#if row.has_auth_secret}
                <i class="ki-filled ki-lock-2 text-muted-foreground" title="A secret is stored"></i>
            {/if}
        </div>
    {:else if column.key === 'last_delivered_at'}
        <!-- WHEN, not whether it worked: the row keeps no status code. -->
        {#if row.last_delivered_at}
            <span class="text-2sm text-muted-foreground"><DateTime value={row.last_delivered_at} format="relative" /></span>
        {:else}
            <Badge variant="secondary" size="sm">Never sent</Badge>
        {/if}
    {:else}
        {row[column.key] ?? '—'}
    {/if}
{/snippet}

{#snippet webhookForm({ parentId, row, close, saved })}
    <WebhookForm formId={parentId} webhook={row} oncancel={close} onsaved={saved} />
{/snippet}
