<script>
    /** Group → members pivot drawer: add/remove the users a notification group delivers to. */
    import PivotDrawer from '@/components/data/PivotDrawer.svelte';

    let { open = $bindable(false), group = null } = $props();
</script>

<PivotDrawer
    bind:open
    title={`Members — ${group?.name ?? ''}`}
    parentId={group?.id}
    indexRoute="api.v1.admin.notification-group-users.index"
    storeRoute="api.v1.admin.notification-group-users.store"
    destroyRoute="api.v1.admin.notification-group-users.destroy"
    resource="api.v1.admin.users.index"
    resourceLabelKey="username"
    payloadKey="users"
    relation="user"
    columns={[
        { key: 'username', label: 'Username', truncate: false, maxWidth: '220px' },
        { key: 'email', label: 'Email' },
    ]}
    assignLabel="Add members"
    emptyTitle="No members yet"
    emptyBody="Add members using the field above."
    searchPlaceholder="Search members…"
/>
