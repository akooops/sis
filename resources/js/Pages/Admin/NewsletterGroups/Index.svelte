<script>
    /** Newsletter groups index — the mailing lists; each one drills down to its subscribers. */
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
    import NewsletterGroupForm from './NewsletterGroupForm.svelte';
    import { useIndex } from '@/lib/api/useIndex.svelte';
    import { hasPermission } from '@/lib/permissions';
    import { router } from '@inertiajs/svelte';
    import { api } from '@/lib/api/client';
    import { toast } from '@/lib/toast';
    import { confirm } from '@/lib/confirm';

    const list = useIndex('api.v1.admin.newsletter-groups.index', { perPage: 15, sort: 'name' });

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
        { key: 'subscribers_count', label: 'Subscribers', width: '130px', truncate: false },
        { key: 'is_default', label: 'Default', width: '110px', truncate: false },
    ];

    // Mirrors the controller's allowedSorts.
    const sortOptions = [
        { value: 'name', label: 'Name' },
        { value: 'id', label: 'ID' },
        { value: 'code', label: 'Code' },
        { value: 'created_at', label: 'Created' },
    ];

    // Mirrors the controller's allowedFilters.
    const filterConfig = [
        { key: 'is_default', type: 'boolean', label: 'Default' },
    ];

    const create = () => { editing = null; showForm = true; };
    const edit = (g) => { editing = g; showForm = true; };
    const closeForm = () => { showForm = false; editing = null; };
    const saved = () => { closeForm(); list.refresh(); };
    const view = (g) => { viewing = g; viewOpen = true; };
    const showActivity = (g) => { activityRow = g; activityOpen = true; };

    /** Hand the subscribers module its own page, pre-filtered to this list. */
    const drillDown = (g) =>
        router.visit(`${route('web.admin.newsletter-group-subscribers.index')}?filter[newsletter_group_id]=${g.id}`);

    async function remove(g) {
        if (!(await confirm({
            body: `Delete ${g.name}? Its ${g.subscribers_count ?? 0} subscribers are deleted with it. This cannot be undone.`,
            variant: 'destructive',
        }))) return;
        try {
            await api.delete(route('api.v1.admin.newsletter-groups.destroy', g.id));
            toast.success('Deleted successfully.');
            list.refresh();
        } catch (e) {
            toast.error(e?.message ?? 'Something went wrong. Please try again.');
        }
    }
</script>

<svelte:head><title>Saud International Schools — Newsletter Groups</title></svelte:head>

<AdminLayout title="Newsletter Groups">
    <IndexCard {showForm} {toolbar} {form} {table} />

    <Filters
        bind:open={filtersOpen}
        config={filterConfig}
        values={list.params.filter}
        sort={list.sort}
        {sortOptions}
        onapply={(filter, sort) => list.apply({ filter, sort })}
    />
    <!-- No title/description rows: the drawer is a record summary, not a
         translation preview — that is what the form's Translations tab is for. -->
    <DetailDrawer
        bind:open={viewOpen}
        title="Newsletter group"
        id={viewing?.id}
        heading={viewing?.name}
        badge={viewing?.is_default ? { label: 'Default', variant: 'primary' } : null}
        fields={[
            { label: 'Code', value: viewing?.code },
            { label: 'Subscribers', value: String(viewing?.subscribers_count ?? 0) },
        ]}
        createdAt={viewing?.created_at}
        updatedAt={viewing?.updated_at}
    />
    <ActivityDrawer bind:open={activityOpen} subjectType="newsletter_group" subjectId={activityRow?.id} title={activityRow?.name} />
</AdminLayout>

{#snippet toolbar(inForm)}
    {#if !inForm}
        <div class="flex items-center gap-2">
            <SearchBar placeholder="Search newsletter groups…" value={list.search} onsearch={(v) => list.setSearch(v)} />
            <FilterButton count={list.activeFilters} onclick={() => (filtersOpen = true)} />
        </div>
        {#if hasPermission('newsletter-groups.store')}
            <button class="kt-btn kt-btn-sm kt-btn-primary" onclick={create}>
                <i class="ki-filled ki-plus"></i>Add group
            </button>
        {/if}
    {:else}
        <button class="kt-btn kt-btn-sm kt-btn-secondary" onclick={closeForm}>
            <i class="ki-filled ki-black-left"></i>Cancel
        </button>
    {/if}
{/snippet}

{#snippet form()}
    <NewsletterGroupForm group={editing} onsaved={saved} oncancel={closeForm} />
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
        emptyTitle="No newsletter groups yet"
        emptyBody="Create a mailing list so people have something to sign up to."
        {cells}
        {rowActions}
    />
{/snippet}

{#snippet cells(row, column)}
    {#if column.key === 'id'}
        <IdBadge id={row.id} onclick={() => view(row)} />
    {:else if column.key === 'name'}
        <span class="text-sm font-medium text-mono">
            <ClampText value={row.name} maxWidth="220px" title={row.name} />
        </span>
    {:else if column.key === 'code'}
        <Badge variant="secondary">
            <ClampText value={row.code} maxWidth="160px" title={row.code} />
        </Badge>
    {:else if column.key === 'subscribers_count'}
        <Badge variant={row.subscribers_count ? 'primary' : 'secondary'}>
            <i class="ki-filled ki-people me-1"></i>{row.subscribers_count ?? 0}
        </Badge>
    {:else if column.key === 'is_default'}
        {#if row.is_default}
            <Badge variant="primary">Default</Badge>
        {:else}
            <span class="text-xs text-muted-foreground">—</span>
        {/if}
    {:else}
        {row[column.key] ?? '—'}
    {/if}
{/snippet}

{#snippet rowActions(row)}
    <RowActions actions={[
        { icon: 'ki-eye', label: 'View', onclick: () => view(row) },
        hasPermission('newsletter-groups.update') && { icon: 'ki-pencil', label: 'Edit', onclick: () => edit(row) },
        hasPermission('newsletter-group-subscribers.index')
            && { icon: 'ki-people', label: 'Subscribers', onclick: () => drillDown(row) },
        hasPermission('activities.index') && { icon: 'ki-time', label: 'Activity', onclick: () => showActivity(row) },
        // The default is where a signup lands when no list is named — the server
        // 422s the delete, so it has no Delete at all rather than one that fails.
        hasPermission('newsletter-groups.destroy') && !row.is_default
            && { icon: 'ki-trash', label: 'Delete', onclick: () => remove(row), variant: 'destructive' },
    ].filter(Boolean)} />
{/snippet}
