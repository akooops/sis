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
    assignLabel="Assign permissions"
    currentLabel="Assigned permissions"
    emptyLabel="No permissions assigned yet."
    searchPlaceholder="Search permissions…"
    {item}
/>

{#snippet item(row)}
    <div class="flex flex-col">
        <span class="text-sm font-medium text-mono">{row.permission?.name ?? row.permission_id}</span>
        <span class="text-xs text-muted-foreground">{row.permission?.code ?? ''}</span>
    </div>
{/snippet}
