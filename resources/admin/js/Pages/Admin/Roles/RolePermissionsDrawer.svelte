<script>
    /** Roles → Permissions pivot drawer. */
    import PivotDrawer from '@/components/data/PivotDrawer.svelte';

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
    columns={[
        { key: 'name', label: 'Name', truncate: false, maxWidth: '220px' },
        { key: 'code', label: 'Code' },
    ]}
    assignLabel="Assign permissions"
    emptyTitle="No permissions assigned yet"
    emptyBody="Assign permissions using the field above."
    searchPlaceholder="Search permissions…"
/>
