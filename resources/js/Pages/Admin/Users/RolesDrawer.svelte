<script>
    /** Users → Roles pivot drawer: assign/detach roles for a user. */
    import PivotDrawer from '@/components/data/PivotDrawer.svelte';

    let { open = $bindable(false), user = null } = $props();
</script>

<PivotDrawer
    bind:open
    title={`Roles for ${user ? `${user.firstname} ${user.lastname}` : ''}`}
    parentId={user?.id}
    indexRoute="api.v1.admin.user-roles.index"
    storeRoute="api.v1.admin.user-roles.store"
    destroyRoute="api.v1.admin.user-roles.destroy"
    resource="api.v1.admin.roles.index"
    payloadKey="roles"
    relation="role"
    columns={[
        { key: 'name', label: 'Name', truncate: false, maxWidth: '220px' },
        { key: 'code', label: 'Code' },
    ]}
    assignLabel="Assign roles"
    emptyTitle="No roles assigned yet"
    emptyBody="Assign roles using the field above."
    searchPlaceholder="Search roles…"
/>
