<script>
    /** Countries index — the ISO 3166-1 reference table, seeded from CountriesSeeder. */
    import AdminLayout from '@/layouts/AdminLayout.svelte';
    import IndexCard from '@/components/data/IndexCard.svelte';
    import DataTable from '@/components/data/DataTable.svelte';
    import SearchBar from '@/components/data/SearchBar.svelte';
    import Filters from '@/components/data/Filters.svelte';
    import FilterButton from '@/components/data/FilterButton.svelte';
    import Badge from '@/components/ui/Badge.svelte';
    import ClampText from '@/components/ui/ClampText.svelte';
    import IdBadge from '@/components/data/IdBadge.svelte';
    import RowActions from '@/components/data/RowActions.svelte';
    import DetailDrawer from '@/components/data/DetailDrawer.svelte';
    import ActivityDrawer from '@/components/activity/ActivityDrawer.svelte';
    import CountryForm from './CountryForm.svelte';
    import { useIndex } from '@/lib/api/useIndex.svelte';
    import { hasPermission } from '@/lib/permissions';
    import { api } from '@/lib/api/client';

    // 25 a page and alphabetical, matching the controller: ~249 seeded rows.
    const list = useIndex('api.v1.admin.countries.index', { perPage: 25, sort: 'name' });

    let showForm = $state(false);
    let editing = $state(null);
    let filtersOpen = $state(false);
    let viewOpen = $state(false);
    let viewing = $state(null);
    let activityOpen = $state(false);
    let activityRow = $state(null);
    let defaultLocale = $state(null);

    const columns = [
        { key: 'id', label: 'ID', sortable: true, width: '90px', truncate: false },
        { key: 'name', label: 'Name', sortable: true, truncate: false },
        { key: 'code', label: 'Code', sortable: true, width: '90px', truncate: false },
        { key: 'nationality', label: 'Nationality', truncate: false },
        { key: 'is_enabled', label: 'Status', truncate: false },
    ];

    // Mirrors the controller's allowedSorts.
    const sortOptions = [
        { value: 'id', label: 'ID' },
        { value: 'name', label: 'Name' },
        { value: 'code', label: 'Code' },
        { value: 'created_at', label: 'Created' },
    ];

    // Mirrors the controller's allowedFilters.
    const filterConfig = [{ key: 'is_enabled', type: 'boolean', label: 'Enabled' }];

    // The Nationality column shows one locale; the default is the one always filled in.
    $effect(() => {
        api.get(route('api.v1.admin.languages.index'), { filter: { is_default: 1 }, per_page: 1 })
            .then((d) => (defaultLocale = d?.data?.[0]?.code ?? null))
            .catch(() => {});
    });

    const create = () => { editing = null; showForm = true; };
    const edit = (c) => { editing = c; showForm = true; };
    const closeForm = () => { showForm = false; editing = null; };
    const saved = () => { closeForm(); list.refresh(); };
    const view = (c) => { viewing = c; viewOpen = true; };
    const showActivity = (c) => { activityRow = c; activityOpen = true; };

</script>

<svelte:head><title>Saud International Schools — Countries</title></svelte:head>

<AdminLayout title="Countries">
    <IndexCard {showForm} {toolbar} {form} {table} />

    <Filters
        bind:open={filtersOpen}
        config={filterConfig}
        values={list.params.filter}
        sort={list.sort}
        {sortOptions}
        onapply={(filter, sort) => list.apply({ filter, sort })}
    />
    <DetailDrawer
        bind:open={viewOpen}
        title="Country"
        id={viewing?.id}
        avatar={{ src: viewing?.flag_url, name: viewing?.name }}
        heading={viewing?.name}
        badge={viewing ? { label: viewing.code, variant: 'secondary' } : null}
        fields={[
            { label: 'Code', value: viewing?.code },
            { label: 'Alpha-3', value: viewing?.alpha3 || '' },
            { label: 'Status', value: viewing?.is_enabled ? 'Enabled' : 'Disabled' },
        ]}
        createdAt={viewing?.created_at}
        updatedAt={viewing?.updated_at}
    />
    <ActivityDrawer bind:open={activityOpen} subjectType="country" subjectId={activityRow?.id} title={activityRow?.name} />
</AdminLayout>

{#snippet toolbar(inForm)}
    {#if !inForm}
        <div class="flex items-center gap-2">
            <SearchBar placeholder="Search countries…" value={list.search} onsearch={(v) => list.setSearch(v)} />
            <FilterButton count={list.activeFilters} onclick={() => (filtersOpen = true)} />
        </div>
        {#if hasPermission('countries.store')}
            <button class="kt-btn kt-btn-sm kt-btn-primary" onclick={create}>
                <i class="ki-filled ki-plus"></i>Add country
            </button>
        {/if}
    {:else}
        <button class="kt-btn kt-btn-sm kt-btn-secondary" onclick={closeForm}>
            <i class="ki-filled ki-black-left"></i>Cancel
        </button>
    {/if}
{/snippet}

{#snippet form()}
    <CountryForm country={editing} onsaved={saved} oncancel={closeForm} />
{/snippet}

{#snippet table()}
    <DataTable
        {columns}
        rows={list.rows}
        loading={list.loading}
        meta={list.meta}
        sort={list.params.sort}
        onSort={list.toggleSort}
        onPageChange={list.goToPage}
        onPerPageChange={list.setPerPage}
        onRowClick={view}
        emptyTitle="No countries yet"
        emptyBody="Run the seeder to load the ISO catalogue, or add one by hand."
        {cells}
        {rowActions}
    />
{/snippet}

{#snippet cells(row, column)}
    {#if column.key === 'id'}
        <IdBadge id={row.id} onclick={() => view(row)} />
    {:else if column.key === 'name'}
        <div class="flex items-center gap-3">
            <img src={row.flag_url} alt="" class="w-5 shrink-0 rounded-sm object-contain" />
            <span class="text-sm font-medium text-mono">
                <ClampText value={row.name} maxWidth="220px" title={row.name} />
            </span>
        </div>
    {:else if column.key === 'code'}
        <Badge variant="secondary">{row.code}</Badge>
    {:else if column.key === 'nationality'}
        {#if defaultLocale && row.nationality?.[defaultLocale]}
            <span class="text-sm text-mono">{row.nationality[defaultLocale]}</span>
        {:else}
            
        {/if}
    {:else if column.key === 'is_enabled'}
        <Badge variant={row.is_enabled ? 'success' : 'secondary'}>
            {row.is_enabled ? 'Enabled' : 'Disabled'}
        </Badge>
    {:else}
        {row[column.key] ?? ''}
    {/if}
{/snippet}

{#snippet rowActions(row)}
    <RowActions actions={[
        { icon: 'ki-eye', label: 'View', onclick: () => view(row) },
        hasPermission('countries.update') && { icon: 'ki-pencil', label: 'Edit', onclick: () => edit(row) },
        hasPermission('activities.index') && { icon: 'ki-time', label: 'Activity', onclick: () => showActivity(row) },
    ].filter(Boolean)} />
{/snippet}
