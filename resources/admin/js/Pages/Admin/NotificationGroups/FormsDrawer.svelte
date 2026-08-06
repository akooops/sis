<script>
    /**
     * Group → forms: which forms notify this group on submission.
     *
     * The SAME pivot as Forms/NotificationGroupsDrawer, read from the other end:
     * one table, one model, one observer, two entry points. That is why the
     * index is flat — filter[form_id] there, filter[notification_group_id] here
     * — and why the store payload names its parent instead of nesting under one.
     *
     * Either way the activity lands on the form, because that is the thing whose
     * behaviour changed.
     */
    import PivotDrawer from '@/components/data/PivotDrawer.svelte';
    import Alert from '@/components/feedback/Alert.svelte';
    import Badge from '@/components/ui/Badge.svelte';
    import { FORM_STATUS_LABELS, FORM_STATUS_VARIANTS } from '@/lib/form';
    import { hasPermission } from '@/lib/permissions';
    import { router } from '@inertiajs/svelte';

    let { open = $bindable(false), group = null } = $props();

    /*
     * Out to the Forms page, filtered to this group — the same deep link the
     * Forms table uses for submissions. The filter travels in the URL rather
     * than in component state, because useIndex reads filters out of the query
     * string at init: that is what makes the destination linkable, sortable and
     * exportable instead of a dead end the admin has to filter by hand.
     */
    const openForms = () =>
        router.visit(route('web.admin.forms.index', { 'filter[notification_group_id]': group.id }));

    const columns = [
        { key: 'name', label: 'Form', truncate: false, maxWidth: '200px' },
        { key: 'slug', label: 'Slug', truncate: false },
        { key: 'status', label: 'Status', truncate: false },
    ];
</script>

<PivotDrawer
    bind:open
    title={`Forms — ${group?.name ?? ''}`}
    parentId={group?.id}
    parentFilter={group ? { notification_group_id: group.id } : null}
    storePayload={group ? { notification_group_id: group.id } : null}
    indexRoute="api.v1.admin.form-notification-groups.index"
    storeRoute="api.v1.admin.form-notification-groups.store"
    destroyRoute="api.v1.admin.form-notification-groups.destroy"
    resource="api.v1.admin.forms.index"
    payloadKey="forms"
    relation="form"
    {columns}
    assignLabel="Notify this group about"
    addLabel="Add"
    emptyTitle="No form notifies this group"
    emptyBody="Add a form to route its submissions here."
    searchPlaceholder="Search forms…"
    confirmBody={(row) => `"${row.form?.name ?? 'This form'}" will stop notifying this group.`}
    {banner}
    {cells}
/>

{#snippet banner()}
    <Alert variant="info">
        <i class="ki-filled ki-information-2"></i>
        <span>
            This group is notified about every form listed here, <strong>on top of</strong> the notification
            types it already subscribes to.
        </span>
    </Alert>

    {#if group && hasPermission('forms.index')}
        <div class="flex justify-end">
            <button type="button" class="kt-btn kt-btn-xs kt-btn-secondary" onclick={openForms}>
                <i class="ki-filled ki-exit-right-corner"></i>Open these forms
            </button>
        </div>
    {/if}
{/snippet}

{#snippet cells(row, column)}
    {#if column.key === 'name'}
        <span class="text-sm font-medium text-mono">{row.form?.name ?? ''}</span>
    {:else if column.key === 'slug'}
        <Badge variant="secondary">{row.form?.slug ?? ''}</Badge>
    {:else if column.key === 'status'}
        <Badge variant={FORM_STATUS_VARIANTS[row.form?.status] ?? 'secondary'}>
            {FORM_STATUS_LABELS[row.form?.status] ?? row.form?.status ?? ''}
        </Badge>
    {:else}
        {row.form?.[column.key] ?? row[column.key] ?? ''}
    {/if}
{/snippet}
