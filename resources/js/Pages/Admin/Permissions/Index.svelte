<script>
    /** Permissions index — read-only Metronic list card. */
    import AdminLayout from '@/layouts/AdminLayout.svelte';
    import IndexCard from '@/components/data/IndexCard.svelte';
    import DataTable from '@/components/data/DataTable.svelte';
    import SearchBar from '@/components/data/SearchBar.svelte';
    import Filters from '@/components/data/Filters.svelte';
    import ExportButton from '@/components/data/ExportButton.svelte';
    import Badge from '@/components/ui/Badge.svelte';
    import DateTime from '@/components/ui/DateTime.svelte';
    import IdBadge from '@/components/data/IdBadge.svelte';
    import DetailDrawer from '@/components/data/DetailDrawer.svelte';
    import { useIndex } from '@/lib/api/useIndex.svelte';

    const list = useIndex('api.v1.admin.permissions.index', { perPage: 20, sort: '-created_at', pollMs: 0 });
    let filtersOpen = $state(false);
    let viewOpen = $state(false);
    let viewing = $state(null);
    const view = (p) => { viewing = p; viewOpen = true; };

    const viewFields = $derived(
        viewing
            ? [
                  { label: 'Code', value: viewing.code },
                  { label: 'Name', value: viewing.name },
                  { label: 'Web', value: viewing.supports_web ? 'Yes' : 'No' },
                  { label: 'API', value: viewing.supports_api ? 'Yes' : 'No' },
              ]
            : [],
    );

    const columns = $derived([
        { key: 'id', label: 'ID', width: '90px', truncate: false },
        { key: 'code', label: 'Code', sortable: true },
        { key: 'name', label: 'Name', sortable: true },
        { key: 'supports_web', label: 'Web', truncate: false },
        { key: 'supports_api', label: 'API', truncate: false },
    ]);
    const filterConfig = $derived([
        { key: 'supports_web', type: 'boolean', label: 'Web' },
        { key: 'supports_api', type: 'boolean', label: 'API' },
    ]);
</script>

<svelte:head><title>Novonordisk — Permissions</title></svelte:head>

<AdminLayout title="Permissions">
    <IndexCard showForm={false} {toolbar} {form} {table} />
    <Filters bind:open={filtersOpen} config={filterConfig} values={list.params.filter} onapply={(v) => list.setFilters(v)} />
    <DetailDrawer bind:open={viewOpen} title="Permissions" id={viewing?.id} fields={viewFields} createdAt={viewing?.created_at} updatedAt={viewing?.updated_at} />
</AdminLayout>

{#snippet toolbar()}
    <div class="flex items-center gap-2">
        <SearchBar placeholder="Search permissions…" value={list.search} onsearch={(v) => list.setSearch(v)} />
        <button class="kt-btn kt-btn-sm kt-btn-ghost" onclick={() => (filtersOpen = true)} aria-label="Filter"><i class="ki-filled ki-filter"></i></button>
        <ExportButton rows={list.rows} columns={[
            { key: 'code', label: 'Code' },
            { key: 'name', label: 'Name' },
        ]} filename="permissions" />
    </div>
{/snippet}

{#snippet form()}{/snippet}

{#snippet table()}
    <DataTable {columns} rows={list.rows} loading={list.loading} meta={list.meta} sort={list.params.sort} onSort={list.toggleSort} onPageChange={list.goToPage} onPerPageChange={list.setPerPage} onRowClick={view} {cells} />
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
