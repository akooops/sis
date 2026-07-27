<script>
    /** Programs index — the top of the academic tree, in a hand-set order. */
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
    import ProgramForm from './ProgramForm.svelte';
    import ReorderDrawer from './ReorderDrawer.svelte';
    import { useIndex } from '@/lib/api/useIndex.svelte';
    import { hasPermission } from '@/lib/permissions';
    import { router } from '@inertiajs/svelte';
    import { api } from '@/lib/api/client';
    import { toast } from '@/lib/toast';
    import { confirm } from '@/lib/confirm';

    const list = useIndex('api.v1.admin.programs.index', { perPage: 15, sort: 'order' });

    let showForm = $state(false);
    let editing = $state(null);
    let filtersOpen = $state(false);
    let reorderOpen = $state(false);
    let viewOpen = $state(false);
    let viewing = $state(null);
    let activityOpen = $state(false);
    let activityRow = $state(null);

    const columns = [
        { key: 'id', label: 'ID', sortable: true, width: '90px', truncate: false },
        { key: 'name', label: 'Name', sortable: true, truncate: false },
        { key: 'slug', label: 'Slug', sortable: true, truncate: false },
        { key: 'order', label: 'Order', sortable: true, width: '100px', truncate: false },
    ];

    // Mirrors the controller's allowedSorts.
    const sortOptions = [
        { value: 'order', label: 'Order' },
        { value: 'id', label: 'ID' },
        { value: 'name', label: 'Name' },
        { value: 'slug', label: 'Slug' },
        { value: 'created_at', label: 'Created' },
    ];

    // Sort-only drawer: a program is a name, a slug and its copy — there is
    // nothing to filter by that the search box doesn't already cover.
    const filterConfig = [];

    const create = () => { editing = null; showForm = true; };
    const edit = (p) => { editing = p; showForm = true; };
    const closeForm = () => { showForm = false; editing = null; };
    const saved = () => { closeForm(); list.refresh(); };
    const view = (p) => { viewing = p; viewOpen = true; };
    const showActivity = (p) => { activityRow = p; activityOpen = true; };

    /** Hand the child module its own page, pre-filtered to this program. */
    const drillDown = (name, p) => router.visit(`${route(name)}?filter[program_id]=${p.id}`);

    async function remove(p) {
        if (!(await confirm({
            body: `Delete ${p.name}? Its streams and grades are deleted with it. This cannot be undone.`,
            variant: 'destructive',
        }))) return;
        try {
            await api.delete(route('api.v1.admin.programs.destroy', p.id));
            toast.success('Deleted successfully.');
            list.refresh();
        } catch (e) {
            toast.error(e?.message ?? 'Something went wrong. Please try again.');
        }
    }
</script>

<svelte:head><title>Saud International Schools — Programs</title></svelte:head>

<AdminLayout title="Programs">
    <IndexCard {showForm} {toolbar} {form} {table} />

    <Filters
        bind:open={filtersOpen}
        config={filterConfig}
        values={list.params.filter}
        sort={list.sort}
        {sortOptions}
        onapply={(filter, sort) => list.apply({ filter, sort })}
    />
    <ReorderDrawer bind:open={reorderOpen} onsaved={() => list.refresh()} />
    <!-- No title/description/content rows: the drawer is a record summary, not a
         translation preview — that is what the form's Translations tab is for. -->
    <DetailDrawer
        bind:open={viewOpen}
        title="Program"
        id={viewing?.id}
        avatar={{ src: viewing?.thumbnail_url, name: viewing?.name }}
        heading={viewing?.name}
        fields={[
            { label: 'Slug', value: viewing?.slug },
            { label: 'Order', value: viewing ? `Position ${viewing.order + 1}` : null },
        ]}
        createdAt={viewing?.created_at}
        updatedAt={viewing?.updated_at}
    />
    <ActivityDrawer bind:open={activityOpen} subjectType="program" subjectId={activityRow?.id} title={activityRow?.name} />
</AdminLayout>

{#snippet toolbar(inForm)}
    {#if !inForm}
        <div class="flex items-center gap-2">
            <SearchBar placeholder="Search programs…" value={list.search} onsearch={(v) => list.setSearch(v)} />
            <FilterButton count={list.activeFilters} onclick={() => (filtersOpen = true)} />
        </div>
        <div class="flex items-center gap-2">
            {#if hasPermission('programs.reorder')}
                <button class="kt-btn kt-btn-sm kt-btn-secondary" onclick={() => (reorderOpen = true)}>
                    <i class="ki-filled ki-arrow-up-down"></i>Reorder
                </button>
            {/if}
            {#if hasPermission('programs.store')}
                <button class="kt-btn kt-btn-sm kt-btn-primary" onclick={create}>
                    <i class="ki-filled ki-plus"></i>Add program
                </button>
            {/if}
        </div>
    {:else}
        <button class="kt-btn kt-btn-sm kt-btn-secondary" onclick={closeForm}>
            <i class="ki-filled ki-black-left"></i>Cancel
        </button>
    {/if}
{/snippet}

<!-- `ready` comes from IndexCard: false while the fly transition runs, so the
     editor doesn't measure itself inside a transformed box. -->
{#snippet form(ready)}
    <ProgramForm program={editing} {ready} onsaved={saved} oncancel={closeForm} />
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
        emptyTitle="No programs yet"
        emptyBody="Create a program to get started."
        {cells}
        {rowActions}
    />
{/snippet}

{#snippet cells(row, column)}
    {#if column.key === 'id'}
        <IdBadge id={row.id} onclick={() => view(row)} />
    {:else if column.key === 'name'}
        <!-- Thumbnail + name in one cell. Not <Avatar>: that crops to a circle,
             which mangles artwork that isn't square. -->
        <div class="flex items-center gap-3">
            <span class="flex size-10 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-border bg-background">
                <img src={row.thumbnail_url} alt="" class="size-full object-contain p-1" />
            </span>
            <span class="min-w-0 text-sm font-medium text-mono">
                <ClampText value={row.name} maxWidth="240px" title={row.name} />
            </span>
        </div>
    {:else if column.key === 'slug'}
        <Badge variant="secondary">
            <ClampText value={row.slug} maxWidth="160px" title={row.slug} />
        </Badge>
    {:else if column.key === 'order'}
        <Badge variant="secondary">{row.order + 1}</Badge>
    {:else}
        {row[column.key] ?? '—'}
    {/if}
{/snippet}

{#snippet rowActions(row)}
    <RowActions actions={[
        { icon: 'ki-eye', label: 'View', onclick: () => view(row) },
        hasPermission('programs.update') && { icon: 'ki-pencil', label: 'Edit', onclick: () => edit(row) },
        hasPermission('streams.index') && { icon: 'ki-route', label: 'Streams', onclick: () => drillDown('web.admin.streams.index', row) },
        hasPermission('grades.index') && { icon: 'ki-book', label: 'Grades', onclick: () => drillDown('web.admin.grades.index', row) },
        hasPermission('activities.index') && { icon: 'ki-time', label: 'Activity', onclick: () => showActivity(row) },
        hasPermission('programs.destroy') && { icon: 'ki-trash', label: 'Delete', onclick: () => remove(row), variant: 'destructive' },
    ].filter(Boolean)} />
{/snippet}
