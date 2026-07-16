<script>
    /** Users → Roles pivot drawer: assign/detach roles for a user. */
    import PivotDrawer from '@/components/data/PivotDrawer.svelte';
    import Badge from '@/components/ui/Badge.svelte';
    import ClampText from '@/components/ui/ClampText.svelte';

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
    assignLabel="Assign roles"
    currentLabel="Assigned roles"
    emptyLabel="No roles assigned yet."
    searchPlaceholder="Search roles…"
    {item}
/>

{#snippet item(row)}
    <Badge variant="primary" class="max-w-full">
        <ClampText value={row.role?.name ?? row.role_id} title={row.role?.name} />
    </Badge>
{/snippet}
