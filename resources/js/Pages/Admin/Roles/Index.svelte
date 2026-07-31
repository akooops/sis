<script>
    /** Roles index — Metronic list card with fly-in create/edit + permissions drawer. */
    import AdminLayout from '@/layouts/AdminLayout.svelte';
    import IndexCard from '@/components/data/IndexCard.svelte';
    import DataTable from '@/components/data/DataTable.svelte';
    import SearchBar from '@/components/data/SearchBar.svelte';
    import Filters from '@/components/data/Filters.svelte';
    import FilterButton from '@/components/data/FilterButton.svelte';
    import ExportButton from '@/components/data/ExportButton.svelte';
    import Badge from '@/components/ui/Badge.svelte';
    import DateTime from '@/components/ui/DateTime.svelte';
    import IdBadge from '@/components/data/IdBadge.svelte';
    import RowActions from '@/components/data/RowActions.svelte';
    import DetailDrawer from '@/components/data/DetailDrawer.svelte';
    import ActivityDrawer from '@/components/activity/ActivityDrawer.svelte';
    import RoleForm from './RoleForm.svelte';
    import RolePermissionsDrawer from './RolePermissionsDrawer.svelte';
    import { useIndex } from '@/lib/api/useIndex.svelte';
    import { hasPermission } from '@/lib/permissions';
    import { api } from '@/lib/api/client';
    import { toast } from '@/lib/toast';
    import { confirm } from '@/lib/confirm';

    const list = useIndex('api.v1.admin.roles.index', { perPage: 15, sort: '-created_at' });

    let showForm = $state(false);
    let editing = $state(null);
    let filtersOpen = $state(false);
    let permsOpen = $state(false);
    let permsRole = $state(null);
    let viewOpen = $state(false);
    let viewing = $state(null);
    let activityOpen = $state(false);
    let activityRow = $state(null);

    const columns = $derived([
        { key: 'id', label: 'ID', sortable: true, width: '90px', truncate: false },
        { key: 'name', label: 'Name', sortable: true },
        { key: 'code', label: 'Code', sortable: true },
    ]);

    // Mirrors the controller's allowedSorts — the drawer and the table headers
    // drive the same `sort`, so a column here must be sortable server-side.
    const sortOptions = [
        { value: 'id', label: 'ID' },
        { value: 'name', label: 'Name' },
        { value: 'code', label: 'Code' },
        { value: 'created_at', label: 'Created' },
    ];

    const filterConfig = [];

    const create = () => { editing = null; showForm = true; };
    const edit = (r) => { editing = r; showForm = true; };
    const closeForm = () => { showForm = false; editing = null; };
    const saved = () => { closeForm(); list.refresh(); };
    const managePerms = (r) => { permsRole = r; permsOpen = true; };
    const view = (r) => { viewing = r; viewOpen = true; };
    const showActivity = (r) => { activityRow = r; activityOpen = true; };

    const viewFields = $derived(
        viewing
            ? [
                  { label: 'Name', value: viewing.name },
                  { label: 'Code', value: viewing.code },
              ]
            : [],
    );

    async function remove(r) {
        if (!(await confirm({ body: 'Are you sure you want to delete this record? This action cannot be undone.', variant: 'destructive' }))) return;
        try {
            await api.delete(route('api.v1.admin.roles.destroy', r.id));
            toast.success('Deleted successfully.');
            list.refresh();
        } catch (e) {
            toast.error(e?.message ?? 'Something went wrong. Please try again.');
        }
    }
</script>

<svelte:head><title>Saud International Schools — Roles</title></svelte:head>

<AdminLayout title="Roles">
    <IndexCard {showForm} {toolbar} {form} {table} />
    <Filters
        bind:open={filtersOpen}
        config={filterConfig}
        values={list.params.filter}
        sort={list.sort}
        {sortOptions}
        onapply={(filter, sort) => list.apply({ filter, sort })}
    />
    <RolePermissionsDrawer bind:open={permsOpen} role={permsRole} />
    <DetailDrawer bind:open={viewOpen} title="Roles" id={viewing?.id} fields={viewFields} createdAt={viewing?.created_at} updatedAt={viewing?.updated_at} />
    <ActivityDrawer bind:open={activityOpen} subjectType="role" subjectId={activityRow?.id} title={activityRow?.name} />
</AdminLayout>

{#snippet toolbar(inForm)}
    {#if !inForm}
        <div class="flex items-center gap-2">
            <SearchBar placeholder="Search roles…" value={list.search} onsearch={(v) => list.setSearch(v)} />
            <FilterButton count={list.activeFilters} onclick={() => (filtersOpen = true)} />
            <ExportButton rows={list.rows} columns={[
                { key: 'name', label: 'Name' },
                { key: 'code', label: 'Code' },
                { key: 'created_at', label: 'Created' },
            ]} filename="roles" />
        </div>
        {#if hasPermission('roles.store')}
            <button class="kt-btn kt-btn-sm kt-btn-primary" onclick={create}><i class="ki-filled ki-plus"></i>Add role</button>
        {/if}
    {:else}
        <button class="kt-btn kt-btn-sm kt-btn-secondary" onclick={closeForm}><i class="ki-filled ki-black-left"></i>Cancel</button>
    {/if}
{/snippet}

{#snippet form()}
    <RoleForm role={editing} onsaved={saved} oncancel={closeForm} />
{/snippet}

{#snippet table()}
    <DataTable {columns} rows={list.rows} loading={list.loading} meta={list.meta} sort={list.params.sort} onSort={list.toggleSort} onPageChange={list.goToPage} onPerPageChange={list.setPerPage} onRowClick={view} {cells} {rowActions} />
{/snippet}

{#snippet cells(row, column)}
    {#if column.key === 'id'}
        <IdBadge id={row.id} onclick={() => view(row)} />
    {:else if column.key === 'permissions'}
        <Badge variant="secondary">{(row.permissions ?? []).length}</Badge>
    {:else if column.key === 'created_at'}
        <DateTime value={row.created_at} />
    {:else}
        {row[column.key] ?? ''}
    {/if}
{/snippet}

{#snippet rowActions(row)}
    <RowActions actions={[
        { icon: 'ki-eye', label: 'View', onclick: () => view(row) },
        hasPermission('roles.update') && { icon: 'ki-pencil', label: 'Edit', onclick: () => edit(row) },
        hasPermission('activities.index') && { icon: 'ki-time', label: 'Activity', onclick: () => showActivity(row) },
        hasPermission('role-permissions.index') && { icon: 'ki-key', label: 'Manage permissions', onclick: () => managePerms(row) },
        hasPermission('roles.destroy') && { icon: 'ki-trash', label: 'Delete', onclick: () => remove(row), variant: 'destructive' },
    ].filter(Boolean)} />
{/snippet}
