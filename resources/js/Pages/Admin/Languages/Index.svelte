<script>
    /** Languages index — the locales the app holds translations for. */
    import AdminLayout from '@/layouts/AdminLayout.svelte';
    import IndexCard from '@/components/data/IndexCard.svelte';
    import DataTable from '@/components/data/DataTable.svelte';
    import SearchBar from '@/components/data/SearchBar.svelte';
    import Filters from '@/components/data/Filters.svelte';
    import FilterButton from '@/components/data/FilterButton.svelte';
    import Badge from '@/components/ui/Badge.svelte';
    import IdBadge from '@/components/data/IdBadge.svelte';
    import RowActions from '@/components/data/RowActions.svelte';
    import DetailDrawer from '@/components/data/DetailDrawer.svelte';
    import ActivityDrawer from '@/components/activity/ActivityDrawer.svelte';
    import LanguageForm from './LanguageForm.svelte';
    import { useIndex } from '@/lib/api/useIndex.svelte';
    import { hasPermission } from '@/lib/permissions';
    import { api } from '@/lib/api/client';
    import { toast } from '@/lib/toast';
    import { confirm } from '@/lib/confirm';

    const list = useIndex('api.v1.admin.languages.index', { perPage: 15, sort: '-created_at' });

    let showForm = $state(false);
    let editing = $state(null);
    let filtersOpen = $state(false);
    let viewOpen = $state(false);
    let viewing = $state(null);
    let activityOpen = $state(false);
    let activityRow = $state(null);

    const columns = [
        { key: 'id', label: 'ID', sortable: true, width: '90px', truncate: false },
        { key: 'name', label: 'Name', sortable: true, truncate: false },
        { key: 'code', label: 'Code', sortable: true, truncate: false },
        { key: 'is_default', label: 'Default', truncate: false },
        { key: 'is_rtl', label: 'Direction', truncate: false },
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
    const filterConfig = [
        { key: 'is_enabled', type: 'boolean', label: 'Enabled' },
        { key: 'is_rtl', type: 'boolean', label: 'Right-to-left' },
        { key: 'is_default', type: 'boolean', label: 'Default' },
    ];

    const create = () => { editing = null; showForm = true; };
    const edit = (l) => { editing = l; showForm = true; };
    const closeForm = () => { showForm = false; editing = null; };
    const saved = () => { closeForm(); list.refresh(); };
    const view = (l) => { viewing = l; viewOpen = true; };
    const showActivity = (l) => { activityRow = l; activityOpen = true; };

    async function remove(l) {
        if (!(await confirm({
            body: `Delete ${l.name}? Its lang/${l.code} folder is left on disk, so re-adding the code picks the translations back up.`,
            variant: 'destructive',
        }))) return;
        try {
            await api.delete(route('api.v1.admin.languages.destroy', l.id));
            toast.success('Deleted successfully.');
            list.refresh();
        } catch (e) {
            toast.error(e?.message ?? 'Something went wrong. Please try again.');
        }
    }
</script>

<svelte:head><title>Saud International Schools — Languages</title></svelte:head>

<AdminLayout title="Languages">
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
        title="Language"
        id={viewing?.id}
        avatar={{ src: viewing?.flag_url, name: viewing?.name }}
        heading={viewing?.name}
        badge={viewing ? { label: viewing.code, variant: 'secondary' } : null}
        fields={[
            { label: 'Default', value: viewing?.is_default ? 'Yes' : 'No' },
            { label: 'Direction', value: viewing?.is_rtl ? 'Right-to-left' : 'Left-to-right' },
            { label: 'Status', value: viewing?.is_enabled ? 'Enabled' : 'Disabled' },
            { label: 'Lang folder', value: viewing ? `lang/${viewing.code}` : '' },
        ]}
        createdAt={viewing?.created_at}
        updatedAt={viewing?.updated_at}
    />
    <ActivityDrawer bind:open={activityOpen} subjectType="language" subjectId={activityRow?.id} title={activityRow?.name} />
</AdminLayout>

{#snippet toolbar(inForm)}
    {#if !inForm}
        <div class="flex items-center gap-2">
            <SearchBar placeholder="Search languages…" value={list.search} onsearch={(v) => list.setSearch(v)} />
            <FilterButton count={list.activeFilters} onclick={() => (filtersOpen = true)} />
        </div>
        {#if hasPermission('languages.store')}
            <button class="kt-btn kt-btn-sm kt-btn-primary" onclick={create}>
                <i class="ki-filled ki-plus"></i>Add language
            </button>
        {/if}
    {:else}
        <button class="kt-btn kt-btn-sm kt-btn-secondary" onclick={closeForm}>
            <i class="ki-filled ki-black-left"></i>Cancel
        </button>
    {/if}
{/snippet}

{#snippet form()}
    <LanguageForm language={editing} onsaved={saved} oncancel={closeForm} />
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
        {cells}
        {rowActions}
    />
{/snippet}

{#snippet cells(row, column)}
    {#if column.key === 'id'}
        <IdBadge id={row.id} onclick={() => view(row)} />
    {:else if column.key === 'name'}
        <div class="flex items-center gap-3">
            <img src={row.flag_url} alt="" class="size-5 shrink-0 rounded-sm object-cover" />
            <span class="text-sm font-medium text-mono">{row.name}</span>
        </div>
    {:else if column.key === 'code'}
        <Badge variant="secondary">{row.code}</Badge>
    {:else if column.key === 'is_default'}
        {#if row.is_default}
            <Badge variant="primary">Default</Badge>
        {:else}
            
        {/if}
    {:else if column.key === 'is_rtl'}
        <Badge variant="secondary">{row.is_rtl ? 'RTL' : 'LTR'}</Badge>
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
        hasPermission('languages.update') && { icon: 'ki-pencil', label: 'Edit', onclick: () => edit(row) },
        hasPermission('activities.index') && { icon: 'ki-time', label: 'Activity', onclick: () => showActivity(row) },
        hasPermission('languages.destroy') && !row.is_default && { icon: 'ki-trash', label: 'Delete', onclick: () => remove(row), variant: 'destructive' },
    ].filter(Boolean)} />
{/snippet}
