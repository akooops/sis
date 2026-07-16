<script>
    /** API keys index — Metronic list card with fly-in create/edit, token modal, permissions drawer. */
    import AdminLayout from '@/layouts/AdminLayout.svelte';
    import IndexCard from '@/components/data/IndexCard.svelte';
    import DataTable from '@/components/data/DataTable.svelte';
    import SearchBar from '@/components/data/SearchBar.svelte';
    import Filters from '@/components/data/Filters.svelte';
    import ExportButton from '@/components/data/ExportButton.svelte';
    import Badge from '@/components/ui/Badge.svelte';
    import DateTime from '@/components/ui/DateTime.svelte';
    import Dropdown from '@/components/ui/Dropdown.svelte';
    import IdBadge from '@/components/data/IdBadge.svelte';
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

    const list = useIndex('api.v1.admin.api-keys.index', { perPage: 15, sort: '-created_at', pollMs: 20000 });

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
        { key: 'id', label: 'ID', width: '90px', truncate: false },
        { key: 'name', label: 'Name', sortable: true },
        { key: 'prefix', label: 'Prefix' },
        { key: 'status', label: 'Status', truncate: false },
        { key: 'last_used_at', label: 'Last used', sortable: true, truncate: false },
    ]);
    const filterConfig = $derived([{ key: 'prefix', type: 'text', label: 'Prefix' }]);

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
                  { label: 'Last used', date: viewing.last_used_at },
              ]
            : [],
    );

    async function rotate(k) {
        if (!(await confirm({ title: 'Rotate token' }))) return;
        try {
            const res = await api.post(route('api.v1.admin.api-keys.rotate', k.id));
            toast.success('Updated successfully.');
            list.refresh();
            showToken(res?.token);
        } catch (e) { toast.error(e?.message ?? 'Something went wrong. Please try again.'); }
    }
    async function revoke(k) {
        if (!(await confirm({ title: 'Revoke', variant: 'destructive' }))) return;
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

<svelte:head><title>Novonordisk — API keys</title></svelte:head>

<AdminLayout title="API keys">
    <IndexCard {showForm} {toolbar} {form} {table} />
    <Filters bind:open={filtersOpen} config={filterConfig} values={list.params.filter} onapply={(v) => list.setFilters(v)} />
    <ApiKeyPermissionsDrawer bind:open={permsOpen} apiKey={permsKey} />
    <TokenModal bind:open={tokenOpen} {token} />
    <DetailDrawer bind:open={viewOpen} title="API keys" id={viewing?.id} fields={viewFields} createdAt={viewing?.created_at} updatedAt={viewing?.updated_at} />
    <ActivityDrawer bind:open={activityOpen} subjectType="api_key" subjectId={activityRow?.id} title={activityRow?.name} />
</AdminLayout>

{#snippet toolbar(inForm)}
    {#if !inForm}
        <div class="flex items-center gap-2">
            <SearchBar placeholder="Search API keys…" value={list.search} onsearch={(v) => list.setSearch(v)} />
            <button class="kt-btn kt-btn-sm kt-btn-ghost" onclick={() => (filtersOpen = true)} aria-label="Filter"><i class="ki-filled ki-filter"></i></button>
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
        {#if row.last_used_at}<DateTime value={row.last_used_at} />{:else}<span class="text-muted-foreground">—</span>{/if}
    {:else if column.key === 'created_at'}
        <DateTime value={row.created_at} />
    {:else}
        {row[column.key] ?? '—'}
    {/if}
{/snippet}

{#snippet rowActions(row)}
    <Dropdown>
        {#snippet trigger()}
            <button class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" aria-label="Actions"><i class="ki-filled ki-dots-vertical"></i></button>
        {/snippet}
        <div class="kt-menu-item"><button class="kt-menu-link" data-dropdown-dismiss onclick={() => view(row)}><span class="kt-menu-icon"><i class="ki-filled ki-eye"></i></span><span class="kt-menu-title">View</span></button></div>
        {#if hasPermission('api-keys.update')}
            <div class="kt-menu-item"><button class="kt-menu-link" data-dropdown-dismiss onclick={() => edit(row)}><span class="kt-menu-icon"><i class="ki-filled ki-pencil"></i></span><span class="kt-menu-title">Edit</span></button></div>
        {/if}
        {#if hasPermission('api-key-permissions.index')}
            <div class="kt-menu-item"><button class="kt-menu-link" data-dropdown-dismiss onclick={() => managePerms(row)}><span class="kt-menu-icon"><i class="ki-filled ki-key"></i></span><span class="kt-menu-title">Manage permissions</span></button></div>
        {/if}
        {#if hasPermission('activities.index')}
            <div class="kt-menu-item"><button class="kt-menu-link" data-dropdown-dismiss onclick={() => showActivity(row)}><span class="kt-menu-icon"><i class="ki-filled ki-time"></i></span><span class="kt-menu-title">Activity</span></button></div>
        {/if}
        {#if hasPermission('api-keys.rotate')}
            <div class="kt-menu-item"><button class="kt-menu-link" data-dropdown-dismiss onclick={() => rotate(row)}><span class="kt-menu-icon"><i class="ki-filled ki-arrows-circle"></i></span><span class="kt-menu-title">Rotate token</span></button></div>
        {/if}
        {#if hasPermission('api-keys.revoke')}
            <div class="kt-menu-item"><button class="kt-menu-link" data-dropdown-dismiss onclick={() => revoke(row)}><span class="kt-menu-icon"><i class="ki-filled ki-shield-cross"></i></span><span class="kt-menu-title">Revoke</span></button></div>
        {/if}
        {#if hasPermission('api-keys.destroy')}
            <div class="kt-menu-item"><button class="kt-menu-link text-destructive" data-dropdown-dismiss onclick={() => remove(row)}><span class="kt-menu-icon"><i class="ki-filled ki-trash"></i></span><span class="kt-menu-title">Delete</span></button></div>
        {/if}
    </Dropdown>
{/snippet}
