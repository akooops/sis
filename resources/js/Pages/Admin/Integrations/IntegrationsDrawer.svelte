<script>
    /**
     * The integrations configured under one type — the standard PivotDrawer:
     * a table (name, provider, on/off), a fly-in create/edit form, and delete.
     * The type is the parent; the integrations index is flat and scoped by
     * `filter[integration_type_id]`, so it's a `parentFilter` pivot.
     */
    import PivotDrawer from '@/components/data/PivotDrawer.svelte';
    import Switch from '@/components/form/Switch.svelte';
    import Badge from '@/components/ui/Badge.svelte';
    import IntegrationForm from './IntegrationForm.svelte';
    import { api } from '@/lib/api/client';
    import { toast } from '@/lib/toast';
    import { hasPermission } from '@/lib/permissions';

    let { open = $bindable(false), type = null } = $props();

    // Drivers are a small per-type catalogue the create form picks from.
    let drivers = $state([]);
    let loadedFor = null;

    $effect(() => {
        if (!open || !type?.id) {
            if (!open) loadedFor = null;
            return;
        }
        if (type.id === loadedFor) return;
        loadedFor = type.id;
        loadDrivers(type.id);
    });

    async function loadDrivers(typeId) {
        try {
            drivers = (await api.get(route('api.v1.admin.integration-types.drivers', typeId))) ?? [];
        } catch (e) {
            toast.error(e?.message ?? 'Could not load drivers.');
        }
    }

    const driverName = (code) => drivers.find((d) => d.code === code)?.name ?? code;

    // Optimistic on/off: flip the row, call the server, revert on failure. The
    // row is a member of PivotDrawer's reactive list, so the Switch re-renders.
    async function toggle(row) {
        const prev = row.is_enabled;
        row.is_enabled = !prev;
        try {
            await api.patch(route('api.v1.admin.integrations.toggle', row.id));
        } catch (e) {
            row.is_enabled = prev;
            toast.error(e?.message ?? 'Something went wrong. Please try again.');
        }
    }

    const columns = [
        { key: 'name', label: 'Name', truncate: false, maxWidth: '200px' },
        { key: 'driver', label: 'Provider' },
        { key: 'status', label: 'On', truncate: false, align: 'end', width: '70px' },
    ];
</script>

<PivotDrawer
    bind:open
    title={type ? `${type.name} integrations` : 'Integrations'}
    parentId={type?.id}
    parentFilter={type ? { integration_type_id: type.id } : null}
    indexRoute="api.v1.admin.integrations.index"
    destroyRoute="api.v1.admin.integrations.destroy"
    {columns}
    addLabel="Add integration"
    emptyTitle="No integrations yet"
    emptyBody="Add one to start using this service."
    searchPlaceholder="Search integrations…"
    confirmBody={(row) => `"${row.name}" will be removed. This can't be undone.`}
    {cells}
    {form}
/>

{#snippet cells(row, column)}
    {#if column.key === 'driver'}
        {driverName(row.driver)}
    {:else if column.key === 'status'}
        {#if hasPermission('integrations.update')}
            <Switch value={row.is_enabled} onchange={() => toggle(row)} />
        {:else}
            <Badge variant={row.is_enabled ? 'success' : 'secondary'}>{row.is_enabled ? 'On' : 'Off'}</Badge>
        {/if}
    {:else}
        {row[column.key] ?? '—'}
    {/if}
{/snippet}

{#snippet form({ row, close, saved })}
    <IntegrationForm {type} {drivers} integration={row} onsaved={saved} oncancel={close} />
{/snippet}
