<script>
    /** API key → Permissions pivot drawer. */
    import PivotDrawer from '@/components/data/PivotDrawer.svelte';

    let { open = $bindable(false), apiKey = null } = $props();
</script>

<PivotDrawer
    bind:open
    title="Permissions for {apiKey?.name ?? ''}"
    parentId={apiKey?.id}
    indexRoute="api.v1.admin.api-key-permissions.index"
    storeRoute="api.v1.admin.api-key-permissions.store"
    destroyRoute="api.v1.admin.api-key-permissions.destroy"
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
