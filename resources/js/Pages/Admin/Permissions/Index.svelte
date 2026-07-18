<script>
    /** Permissions index — read-only Metronic list card. */
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
    import DetailDrawer from '@/components/data/DetailDrawer.svelte';
    import ActivityDrawer from '@/components/activity/ActivityDrawer.svelte';
    import Dropdown from '@/components/ui/Dropdown.svelte';
    import { useIndex } from '@/lib/api/useIndex.svelte';
    import { hasPermission } from '@/lib/permissions';

    const list = useIndex('api.v1.admin.permissions.index', { perPage: 15, sort: '-created_at', pollMs: 0 });
    let filtersOpen = $state(false);
    let viewOpen = $state(false);
    let viewing = $state(null);
    let activityOpen = $state(false);
    let activityRow = $state(null);
    const view = (p) => { viewing = p; viewOpen = true; };
    const showActivity = (p) => { activityRow = p; activityOpen = true; };

    const viewFields = $derived(
        viewing
            ? [
                  { label: 'Name', value: viewing.name },
                  { label: 'Code', value: viewing.code },
                  { label: 'Web', value: viewing.supports_web ? 'Yes' : 'No' },
                  { label: 'API', value: viewing.supports_api ? 'Yes' : 'No' },
              ]
            : [],
    );

    const columns = $derived([
        { key: 'id', label: 'ID', sortable: true, width: '90px', truncate: false },
        { key: 'name', label: 'Name', sortable: true },
        { key: 'code', label: 'Code', sortable: true },
        { key: 'supports_web', label: 'Web', truncate: false },
        { key: 'supports_api', label: 'API', truncate: false },
    ]);

    const sortOptions = [
        { value: 'id', label: 'ID' },
        { value: 'name', label: 'Name' },
        { value: 'code', label: 'Code' },
        { value: 'created_at', label: 'Created' },
    ];

    const filterConfig = $derived([
        { key: 'supports_web', type: 'boolean', label: 'Web' },
        { key: 'supports_api', type: 'boolean', label: 'API' },
    ]);
</script>

<svelte:head><title>Saud International Schools — Permissions</title></svelte:head>

<AdminLayout title="Permissions">
    <IndexCard showForm={false} {toolbar} {form} {table} />
    <Filters
        bind:open={filtersOpen}
        config={filterConfig}
        values={list.params.filter}
        sort={list.sort}
        {sortOptions}
        onapply={(filter, sort) => list.apply({ filter, sort })}
    />
    <DetailDrawer bind:open={viewOpen} title="Permissions" id={viewing?.id} fields={viewFields} createdAt={viewing?.created_at} updatedAt={viewing?.updated_at} />
    <ActivityDrawer bind:open={activityOpen} subjectType="permission" subjectId={activityRow?.id} title={activityRow?.name ?? activityRow?.code} />
</AdminLayout>

{#snippet toolbar()}
    <div class="flex items-center gap-2">
        <SearchBar placeholder="Search permissions…" value={list.search} onsearch={(v) => list.setSearch(v)} />
        <FilterButton count={list.activeFilters} onclick={() => (filtersOpen = true)} />
        <ExportButton rows={list.rows} columns={[
            { key: 'code', label: 'Code' },
            { key: 'name', label: 'Name' },
        ]} filename="permissions" />
    </div>
{/snippet}

{#snippet form()}{/snippet}

{#snippet table()}
    <DataTable {columns} rows={list.rows} loading={list.loading} meta={list.meta} sort={list.params.sort} onSort={list.toggleSort} onPageChange={list.goToPage} onPerPageChange={list.setPerPage} onRowClick={view} {cells} {rowActions} />
{/snippet}

{#snippet cells(row, column)}
    {#if column.key === 'id'}
        <IdBadge id={row.id} onclick={() => view(row)} />
    {:else if column.key === 'code'}
        <span class="font-mono text-xs text-mono">{row.code}</span>
    {:else if column.key === 'supports_web'}
        {#if row.supports_web}<Badge variant="success">Yes</Badge>{:else}<span class="text-muted-foreground">—</span>{/if}
    {:else if column.key === 'supports_api'}
        {#if row.supports_api}<Badge variant="success">Yes</Badge>{:else}<span class="text-muted-foreground">—</span>{/if}
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

        <!-- the record itself (read-only module: no edit, no delete) -->
        <div class="kt-menu-item">
            <button class="kt-menu-link" data-dropdown-dismiss onclick={() => view(row)}>
                <span class="kt-menu-icon"><i class="ki-filled ki-eye"></i></span><span class="kt-menu-title">View</span>
            </button>
        </div>

        <!-- what hangs off it -->
        {#if hasPermission('activities.index')}
            <div class="kt-menu-separator"></div>
            <div class="kt-menu-item">
                <button class="kt-menu-link" data-dropdown-dismiss onclick={() => showActivity(row)}>
                    <span class="kt-menu-icon"><i class="ki-filled ki-time"></i></span><span class="kt-menu-title">Activity</span>
                </button>
            </div>
        {/if}
    </Dropdown>
{/snippet}
