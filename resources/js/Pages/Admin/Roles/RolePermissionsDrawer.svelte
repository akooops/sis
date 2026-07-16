<script>
    /** Roles → Permissions pivot drawer. */
    import PivotDrawer from '@/components/data/PivotDrawer.svelte';
    import ClampText from '@/components/ui/ClampText.svelte';

    let { open = $bindable(false), role = null } = $props();
</script>

<PivotDrawer
    bind:open
    title="Permissions for {role?.name ?? ''}"
    parentId={role?.id}
    indexRoute="api.v1.admin.role-permissions.index"
    storeRoute="api.v1.admin.role-permissions.store"
    destroyRoute="api.v1.admin.role-permissions.destroy"
    resource="api.v1.admin.permissions.index"
    payloadKey="permissions"
    relation="permission"
    assignLabel="Assign permissions"
    currentLabel="Assigned permissions"
    emptyLabel="No permissions assigned yet."
    searchPlaceholder="Search permissions…"
    {item}
/>

{#snippet item(row)}
    <div class="flex min-w-0 flex-col">
        <div class="text-sm font-medium text-mono">
            <ClampText value={row.permission?.name ?? row.permission_id} title={row.permission?.name} />
        </div>
        <div class="text-xs text-muted-foreground">
            <ClampText value={row.permission?.code ?? ''} title={row.permission?.code} />
        </div>
    </div>
{/snippet}
