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
    import { t } from '@/lib/i18n';

    const list = useIndex('api.v1.admin.permissions.index', { perPage: 20, sort: '-created_at', pollMs: 0 });
    let filtersOpen = $state(false);
    let viewOpen = $state(false);
    let viewing = $state(null);
    const view = (p) => { viewing = p; viewOpen = true; };

    const viewFields = $derived(
        viewing
            ? [
                  { label: $t('permissions.fields.code'), value: viewing.code },
                  { label: $t('permissions.fields.name'), value: viewing.name },
                  { label: $t('permissions.fields.supports_web'), value: viewing.supports_web ? $t('common.filters.yes') : $t('common.filters.no') },
                  { label: $t('permissions.fields.supports_api'), value: viewing.supports_api ? $t('common.filters.yes') : $t('common.filters.no') },
              ]
            : [],
    );

    const columns = $derived([
        { key: 'id', label: $t('common.detail.id'), width: '90px', truncate: false },
        { key: 'code', label: $t('permissions.fields.code'), sortable: true },
        { key: 'name', label: $t('permissions.fields.name'), sortable: true },
        { key: 'supports_web', label: $t('permissions.fields.supports_web'), truncate: false },
        { key: 'supports_api', label: $t('permissions.fields.supports_api'), truncate: false },
    ]);
    const filterConfig = $derived([
        { key: 'supports_web', type: 'boolean', label: $t('permissions.fields.supports_web') },
        { key: 'supports_api', type: 'boolean', label: $t('permissions.fields.supports_api') },
    ]);
</script>

<svelte:head><title>Novonordisk — {$t('permissions.title')}</title></svelte:head>

<AdminLayout breadcrumbs={[{ label: $t('permissions.title') }]}>
    <IndexCard showForm={false} {toolbar} {form} {table} />
    <Filters bind:open={filtersOpen} config={filterConfig} values={list.params.filter} onapply={(v) => list.setFilters(v)} />
    <DetailDrawer bind:open={viewOpen} title={$t('permissions.title')} id={viewing?.id} fields={viewFields} createdAt={viewing?.created_at} updatedAt={viewing?.updated_at} />
</AdminLayout>

{#snippet toolbar()}
    <div class="flex items-center gap-2">
        <SearchBar placeholder={$t('permissions.search')} value={list.search} onsearch={(v) => list.setSearch(v)} />
        <button class="kt-btn kt-btn-sm kt-btn-ghost" onclick={() => (filtersOpen = true)} aria-label={$t('common.actions.filter')}><i class="ki-filled ki-filter"></i></button>
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
        {#if row.supports_web}<Badge variant="success">{$t('common.filters.yes')}</Badge>{:else}<span class="text-muted-foreground">—</span>{/if}
    {:else if column.key === 'supports_api'}
        {#if row.supports_api}<Badge variant="success">{$t('common.filters.yes')}</Badge>{:else}<span class="text-muted-foreground">—</span>{/if}
    {:else if column.key === 'created_at'}
        <DateTime value={row.created_at} />
    {:else}
        {row[column.key] ?? '—'}
    {/if}
{/snippet}
