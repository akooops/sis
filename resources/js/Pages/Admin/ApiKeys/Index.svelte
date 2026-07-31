<script>
    /** API keys index — Metronic list card with fly-in create/edit, token modal, permissions drawer. */
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
    import ApiKeyForm from './ApiKeyForm.svelte';
    import ApiKeyPermissionsDrawer from './ApiKeyPermissionsDrawer.svelte';
    import TokenModal from './TokenModal.svelte';
    import { useIndex } from '@/lib/api/useIndex.svelte';
    import { hasPermission } from '@/lib/permissions';
    import { api } from '@/lib/api/client';
    import { toast } from '@/lib/toast';
    import { confirm } from '@/lib/confirm';

    const list = useIndex('api.v1.admin.api-keys.index', { perPage: 15, sort: '-created_at' });

    let showForm = $state(false);
    let editing = $state(null);
    let filtersOpen = $state(false);
    let permsOpen = $state(false);
    let permsKey = $state(null);
    let tokenOpen = $state(false);
    let token = $state('');
    let viewOpen = $state(false);
    let viewing = $state(null);
    let activityOpen = $state(false);
    let activityRow = $state(null);

    const columns = $derived([
        { key: 'id', label: 'ID', sortable: true, width: '90px', truncate: false },
        { key: 'name', label: 'Name', sortable: true },
        { key: 'prefix', label: 'Prefix' },
        { key: 'status', label: 'Status', truncate: false },
        { key: 'last_used_at', label: 'Last used', sortable: true, truncate: false },
    ]);
    // Mirrors the controller's allowedSorts — the drawer and the table headers
    // drive the same `sort`, so a column here must be sortable server-side.
    const sortOptions = [
        { value: 'id', label: 'ID' },
        { value: 'name', label: 'Name' },
        { value: 'last_used_at', label: 'Last used' },
        { value: 'expires_at', label: 'Expires at' },
        { value: 'created_at', label: 'Created' },
    ];

    // Mirrors the controller's allowedFilters. Anything not listed there is a
    // 400 from the query builder, and everything else is reachable by search.
    const filterConfig = [];

    const showToken = (v) => { if (v) { token = v; tokenOpen = true; } };
    const create = () => { editing = null; showForm = true; };
    const edit = (k) => { editing = k; showForm = true; };
    const closeForm = () => { showForm = false; editing = null; };
    const saved = (res) => { closeForm(); list.refresh(); showToken(res?.token); };
    const managePerms = (k) => { permsKey = k; permsOpen = true; };
    const view = (k) => { viewing = k; viewOpen = true; };
    const showActivity = (k) => { activityRow = k; activityOpen = true; };

    const viewFields = $derived(
        viewing
            ? [
                  { label: 'Name', value: viewing.name },
                  { label: 'Prefix', value: viewing.prefix },
                  { label: 'Status', value: viewing.is_active ? 'Active' : 'Inactive' },
                  // An empty allow-list means every IP, which is the opposite of
                  // "none" — say so rather than showing a blank.
                  { label: 'Allowed IPs', value: viewing.allowed_ips?.length ? viewing.allowed_ips.join(', ') : 'Any IP' },
                  { label: 'Last used', date: viewing.last_used_at },
                  { label: 'Last used IP', value: viewing.last_used_ip || '' },
                  { label: 'Expires at', date: viewing.expires_at },
                  { label: 'Revoked at', date: viewing.revoked_at },
              ]
            : [],
    );

    async function rotate(k) {
        if (!(await confirm({ title: 'Rotate token', body: 'Are you sure you want to rotate the token? The old one would be invalid.', }))) return;
        try {
            const res = await api.post(route('api.v1.admin.api-keys.rotate', k.id));
            toast.success('Updated successfully.');
            list.refresh();
            showToken(res?.token);
        } catch (e) { toast.error(e?.message ?? 'Something went wrong. Please try again.'); }
    }
    async function revoke(k) {
        if (!(await confirm({ title: 'Revoke', body: 'Are you sure you want to revoke this api key? This action cannot be undone.',  variant: 'destructive' }))) return;
        try {
            await api.post(route('api.v1.admin.api-keys.revoke', k.id));
            toast.success('Updated successfully.');
            list.refresh();
        } catch (e) { toast.error(e?.message ?? 'Something went wrong. Please try again.'); }
    }
    async function remove(k) {
        if (!(await confirm({ body: 'Are you sure you want to delete this record? This action cannot be undone.', variant: 'destructive' }))) return;
        try {
            await api.delete(route('api.v1.admin.api-keys.destroy', k.id));
            toast.success('Deleted successfully.');
            list.refresh();
        } catch (e) { toast.error(e?.message ?? 'Something went wrong. Please try again.'); }
    }
</script>

<svelte:head><title>Saud International Schools — API keys</title></svelte:head>

<AdminLayout title="API keys">
    <IndexCard {showForm} {toolbar} {form} {table} />
    <Filters
        bind:open={filtersOpen}
        config={filterConfig}
        values={list.params.filter}
        sort={list.sort}
        {sortOptions}
        onapply={(filter, sort) => list.apply({ filter, sort })}
    />
    <ApiKeyPermissionsDrawer bind:open={permsOpen} apiKey={permsKey} />
    <TokenModal bind:open={tokenOpen} {token} />
    <DetailDrawer bind:open={viewOpen} title="API keys" id={viewing?.id} fields={viewFields} createdAt={viewing?.created_at} updatedAt={viewing?.updated_at} />
    <ActivityDrawer bind:open={activityOpen} subjectType="api_key" subjectId={activityRow?.id} title={activityRow?.name} />
</AdminLayout>

{#snippet toolbar(inForm)}
    {#if !inForm}
        <div class="flex items-center gap-2">
            <SearchBar placeholder="Search API keys…" value={list.search} onsearch={(v) => list.setSearch(v)} />
            <FilterButton count={list.activeFilters} onclick={() => (filtersOpen = true)} />
            <ExportButton rows={list.rows} columns={[
                { key: 'name', label: 'Name' },
                { key: 'prefix', label: 'Prefix' },
                { key: 'created_at', label: 'Created' },
            ]} filename="api-keys" />
        </div>
        {#if hasPermission('api-keys.store')}
            <button class="kt-btn kt-btn-sm kt-btn-primary" onclick={create}><i class="ki-filled ki-plus"></i>Create API key</button>
        {/if}
    {:else}
        <button class="kt-btn kt-btn-sm kt-btn-secondary" onclick={closeForm}><i class="ki-filled ki-black-left"></i>Cancel</button>
    {/if}
{/snippet}

{#snippet form()}
    <ApiKeyForm apiKey={editing} onsaved={saved} oncancel={closeForm} />
{/snippet}

{#snippet table()}
    <DataTable {columns} rows={list.rows} loading={list.loading} meta={list.meta} sort={list.params.sort} onSort={list.toggleSort} onPageChange={list.goToPage} onPerPageChange={list.setPerPage} onRowClick={view} {cells} {rowActions} />
{/snippet}

{#snippet cells(row, column)}
    {#if column.key === 'id'}
        <IdBadge id={row.id} onclick={() => view(row)} />
    {:else if column.key === 'prefix'}
        <span class="font-mono text-xs text-mono">{row.prefix}</span>
    {:else if column.key === 'status'}
        <Badge variant={row.is_active ? 'success' : 'secondary'}>{row.is_active ? 'Active' : 'Inactive'}</Badge>
    {:else if column.key === 'last_used_at'}
        {#if row.last_used_at}<DateTime value={row.last_used_at} />{:else}{/if}
    {:else if column.key === 'created_at'}
        <DateTime value={row.created_at} />
    {:else}
        {row[column.key] ?? ''}
    {/if}
{/snippet}

{#snippet rowActions(row)}
    <RowActions actions={[
        { icon: 'ki-eye', label: 'View', onclick: () => view(row) },
        hasPermission('api-keys.update') && { icon: 'ki-pencil', label: 'Edit', onclick: () => edit(row) },
        hasPermission('api-keys.rotate') && { icon: 'ki-arrows-circle', label: 'Rotate token', onclick: () => rotate(row) },
        hasPermission('api-keys.revoke') && { icon: 'ki-shield-cross', label: 'Revoke', onclick: () => revoke(row) },
        hasPermission('activities.index') && { icon: 'ki-time', label: 'Activity', onclick: () => showActivity(row) },
        hasPermission('api-key-permissions.index') && { icon: 'ki-key', label: 'Manage permissions', onclick: () => managePerms(row) },
        hasPermission('api-keys.destroy') && { icon: 'ki-trash', label: 'Delete', onclick: () => remove(row), variant: 'destructive' },
    ].filter(Boolean)} />
{/snippet}
