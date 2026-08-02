<script>
    /**
     * Form → notification groups: who gets told when this form is submitted.
     *
     * The mirror image of NotificationGroups/FormsDrawer — one pivot table, one
     * model, one observer, two entry points. The index is flat and scoped by
     * filter[form_id], so the parent travels in `parentFilter` (read) and
     * `storePayload` (write) rather than in the URL.
     *
     * This is a UNION with the group's own type subscription, not a replacement:
     * attaching a group here IS the opt-in, so the hint says so out loud rather
     * than leaving an admin wondering why the group also needs the type.
     */
    import PivotDrawer from '@/components/data/PivotDrawer.svelte';
    import Alert from '@/components/feedback/Alert.svelte';
    import Badge from '@/components/ui/Badge.svelte';
    import { hasPermission } from '@/lib/permissions';
    import { router } from '@inertiajs/svelte';

    let { open = $bindable(false), form = null } = $props();

    /*
     * Out to the Notification Groups page, filtered to this form — the mirror of
     * FormsDrawer's link and the same deep link the Forms table uses for
     * submissions. The filter travels in the URL because useIndex reads filters
     * out of the query string at init; carrying it in component state would make
     * the destination unlinkable.
     */
    const openGroups = () =>
        router.visit(route('web.admin.notification-groups.index', { 'filter[form_id]': form.id }));

    const columns = [
        { key: 'name', label: 'Group', truncate: false, maxWidth: '200px' },
        { key: 'code', label: 'Code', truncate: false },
        { key: 'delivery', label: 'Delivery', truncate: false },
        { key: 'members_count', label: 'Members', truncate: false, align: 'end', width: '90px' },
    ];
</script>

<PivotDrawer
    bind:open
    title={`Notified groups — ${form?.name ?? ''}`}
    parentId={form?.id}
    parentFilter={form ? { form_id: form.id } : null}
    storePayload={form ? { form_id: form.id } : null}
    indexRoute="api.v1.admin.form-notification-groups.index"
    storeRoute="api.v1.admin.form-notification-groups.store"
    destroyRoute="api.v1.admin.form-notification-groups.destroy"
    resource="api.v1.admin.notification-groups.index"
    payloadKey="notification_groups"
    relation="group"
    {columns}
    assignLabel="Notify these groups"
    addLabel="Add"
    emptyTitle="Nobody is notified"
    emptyBody="Submissions to this form still arrive — no one is told about them."
    searchPlaceholder="Search groups…"
    confirmBody={(row) => `"${row.group?.name ?? 'This group'}" will stop being notified about this form.`}
    {banner}
    {cells}
/>

{#snippet banner()}
    <Alert variant="info">
        <i class="ki-filled ki-information-2"></i>
        <span>
            These groups are notified <strong>in addition</strong> to any group already subscribed to the
            submission notification type. A group listed here does not also need the subscription.
        </span>
    </Alert>

    {#if form && hasPermission('notification-groups.index')}
        <div class="flex justify-end">
            <button type="button" class="kt-btn kt-btn-xs kt-btn-secondary" onclick={openGroups}>
                <i class="ki-filled ki-exit-right-corner"></i>Open these groups
            </button>
        </div>
    {/if}
{/snippet}

{#snippet cells(row, column)}
    {#if column.key === 'name'}
        <span class="text-sm font-medium text-mono">{row.group?.name ?? ''}</span>
    {:else if column.key === 'code'}
        <Badge variant="secondary">{row.group?.code ?? ''}</Badge>
    {:else if column.key === 'delivery'}
        {#if row.group?.integration}
            <Badge variant="secondary"><i class="ki-filled ki-sms me-1"></i>{row.group.integration.name}</Badge>
        {:else}
            <span class="text-xs text-muted-foreground">In-app only</span>
        {/if}
    {:else if column.key === 'members_count'}
        <span class="text-sm text-mono">{row.group?.members_count ?? 0}</span>
    {:else}
        {row.group?.[column.key] ?? row[column.key] ?? ''}
    {/if}
{/snippet}
