<script>
    /**
     * Menu items index — the entries of every menu, in one flat table.
     *
     * `order` is per (menu, parent), so a flat sort cannot draw a tree: a child is
     * indented and marked with a corner glyph so the hierarchy still reads, and the
     * nesting itself is arranged in the Reorder drawer.
     */
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
    import MenuItemForm from './MenuItemForm.svelte';
    import ReorderDrawer from './ReorderDrawer.svelte';
    import { useIndex } from '@/lib/api/useIndex.svelte';
    import { hasPermission } from '@/lib/permissions';
    import { api } from '@/lib/api/client';
    import { toast } from '@/lib/toast';
    import { confirm } from '@/lib/confirm';
    import { LINKABLE_OPTIONS, linkKind, linkKindLabel, linkTarget } from '@/lib/linkable';

    const list = useIndex('api.v1.admin.menu-items.index', { perPage: 15, sort: 'order' });

    let showForm = $state(false);
    let editing = $state(null);
    let filtersOpen = $state(false);
    let reorderOpen = $state(false);
    let viewOpen = $state(false);
    let viewing = $state(null);
    let activityOpen = $state(false);
    let activityRow = $state(null);

    const menuId = $derived(list.params.filter?.menu_id ?? null);

    // Deep link (/admin/menu-items?filter[menu_id]=<id>): the loaded rows already
    // carry their menu, so the filter drawer and the reorder picker can show its
    // NAME straight away. Select resolves the label itself when no row matched — an
    // empty menu has nothing here to read it from.
    const filteredMenu = $derived(
        menuId ? (list.rows.find((r) => r.menu?.id === menuId)?.menu ?? null) : null,
    );

    const columns = [
        { key: 'id', label: 'ID', sortable: true, width: '90px', truncate: false },
        { key: 'name', label: 'Name', sortable: true, truncate: false },
        { key: 'menu', label: 'Menu', truncate: false },
        { key: 'link', label: 'Link', truncate: false },
        { key: 'order', label: 'Order', sortable: true, width: '100px', truncate: false },
    ];

    // Mirrors the controller's allowedSorts.
    const sortOptions = [
        { value: 'order', label: 'Order' },
        { value: 'name', label: 'Name' },
        { value: 'id', label: 'ID' },
        { value: 'created_at', label: 'Created' },
    ];

    // Mirrors the controller's allowedFilters. Records only for the link: the
    // server matches linkable_type by MorphType alias, and 'url' is a picker kind
    // it would resolve to nothing.
    const filterConfig = [
        {
            key: 'menu_id',
            type: 'resource-select',
            label: 'Menu',
            resource: 'api.v1.admin.menus.index',
            placeholder: 'All menus',
            initialOptions: () => (filteredMenu ? [{ value: filteredMenu.id, label: filteredMenu.name }] : []),
        },
        {
            key: 'linkable_type',
            type: 'select',
            label: 'Link',
            options: LINKABLE_OPTIONS.filter((o) => o.value !== 'url'),
        },
    ];

    /** One line for the drawer: the address, or the type and the record's name. */
    function linkSummary(row) {
        if (!row) return '';
        const kind = linkKind(row);
        if (!kind) return 'No link';
        if (kind === 'url') return row.url;

        return `${linkKindLabel(kind)} — ${linkTarget(row) ?? ''}`;
    }

    /** The parent's name when it is on this page — a row carries only its id. */
    function parentName(row) {
        if (!row) return '';
        if (!row.parent_id) return 'Top level';

        return list.rows.find((r) => r.id === row.parent_id)?.name ?? row.parent_id;
    }

    const create = () => { editing = null; showForm = true; };
    const edit = (i) => { editing = i; showForm = true; };
    const closeForm = () => { showForm = false; editing = null; };
    const saved = () => { closeForm(); list.refresh(); };
    const view = (i) => { viewing = i; viewOpen = true; };
    const showActivity = (i) => { activityRow = i; activityOpen = true; };

    async function remove(i) {
        if (!(await confirm({
            body: `Delete ${i.name}? Anything nested under it goes too, and the remaining items keep their order.`,
            variant: 'destructive',
        }))) return;
        try {
            await api.delete(route('api.v1.admin.menu-items.destroy', i.id));
            toast.success('Deleted successfully.');
            list.refresh();
        } catch (e) {
            toast.error(e?.message ?? 'Something went wrong. Please try again.');
        }
    }
</script>

<svelte:head><title>Saud International Schools — Menu Items</title></svelte:head>

<AdminLayout title="Menu Items">
    <IndexCard {showForm} {toolbar} {form} {table} />

    <Filters
        bind:open={filtersOpen}
        config={filterConfig}
        values={list.params.filter}
        sort={list.sort}
        {sortOptions}
        onapply={(filter, sort) => list.apply({ filter, sort })}
    />
    <ReorderDrawer bind:open={reorderOpen} {menuId} menu={filteredMenu} onsaved={() => list.refresh()} />
    <!-- No title row: the drawer is a record summary, not a translation preview —
         that is what the form's Translations tab is for. -->
    <DetailDrawer
        bind:open={viewOpen}
        title="Menu item"
        id={viewing?.id}
        heading={viewing?.name}
        badge={viewing ? { label: `Position ${viewing.order + 1}`, variant: 'secondary' } : null}
        fields={[
            { label: 'Menu', value: viewing?.menu?.name || '' },
            { label: 'Parent', value: parentName(viewing) },
            { label: 'Link', value: linkSummary(viewing) },
            { label: 'Children', value: String(viewing?.children?.length ?? 0) },
        ]}
        createdAt={viewing?.created_at}
        updatedAt={viewing?.updated_at}
    />
    <ActivityDrawer bind:open={activityOpen} subjectType="menu_item" subjectId={activityRow?.id} title={activityRow?.name} />
</AdminLayout>

{#snippet toolbar(inForm)}
    {#if !inForm}
        <div class="flex items-center gap-2">
            <SearchBar placeholder="Search menu items…" value={list.search} onsearch={(v) => list.setSearch(v)} />
            <FilterButton count={list.activeFilters} onclick={() => (filtersOpen = true)} />
        </div>
        <div class="flex items-center gap-2">
            {#if hasPermission('menu-items.reorder')}
                <button class="kt-btn kt-btn-sm kt-btn-secondary" onclick={() => (reorderOpen = true)}>
                    <i class="ki-filled ki-arrow-up-down"></i>Reorder
                </button>
            {/if}
            {#if hasPermission('menu-items.store')}
                <button class="kt-btn kt-btn-sm kt-btn-primary" onclick={create}>
                    <i class="ki-filled ki-plus"></i>Add item
                </button>
            {/if}
        </div>
    {:else}
        <button class="kt-btn kt-btn-sm kt-btn-secondary" onclick={closeForm}>
            <i class="ki-filled ki-black-left"></i>Cancel
        </button>
    {/if}
{/snippet}

{#snippet form()}
    <MenuItemForm menuItem={editing} {menuId} onsaved={saved} oncancel={closeForm} />
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
        emptyTitle="No menu items yet"
        emptyBody="Add an item to a menu to get started."
        {cells}
        {rowActions}
    />
{/snippet}

{#snippet cells(row, column)}
    {#if column.key === 'id'}
        <IdBadge id={row.id} onclick={() => view(row)} />
    {:else if column.key === 'name'}
        <!-- One step of indent plus a corner glyph: the rows are flat, the menu is not. -->
        <div class="flex items-center gap-2 {row.parent_id ? 'ps-6' : ''}">
            {#if row.parent_id}
                <i class="ki-filled ki-arrow-down-right shrink-0 text-xs text-muted-foreground"></i>
            {/if}
            <span class="min-w-0 text-sm font-medium text-mono">
                <ClampText value={row.name} maxWidth="220px" title={row.name} />
            </span>
        </div>
    {:else if column.key === 'menu'}
        {#if row.menu}
            <Badge variant="primary">
                <ClampText value={row.menu.name} maxWidth="160px" title={row.menu.name} />
            </Badge>
        {:else}
            
        {/if}
    {:else if column.key === 'link'}
        {#if row.url}
            <a href={row.url} target="_blank" rel="noreferrer noopener" class="kt-link text-sm" onclick={(e) => e.stopPropagation()}>
                <ClampText value={row.url} maxWidth="240px" title={row.url} />
            </a>
        {:else if row.linkable_type}
            <div class="flex items-center gap-2">
                <Badge variant="secondary">{linkKindLabel(row.linkable_type)}</Badge>
                <!-- linkable is null once the target is deleted; the id survives. -->
                <span class="min-w-0 text-sm text-mono">
                    <ClampText value={linkTarget(row)} maxWidth="180px" title={linkTarget(row)} />
                </span>
            </div>
        {:else}
            
        {/if}
    {:else if column.key === 'order'}
        <Badge variant="secondary">{row.order + 1}</Badge>
    {:else}
        {row[column.key] ?? ''}
    {/if}
{/snippet}

{#snippet rowActions(row)}
    <RowActions actions={[
        { icon: 'ki-eye', label: 'View', onclick: () => view(row) },
        hasPermission('menu-items.update') && { icon: 'ki-pencil', label: 'Edit', onclick: () => edit(row) },
        hasPermission('activities.index') && { icon: 'ki-time', label: 'Activity', onclick: () => showActivity(row) },
        hasPermission('menu-items.destroy') && { icon: 'ki-trash', label: 'Delete', onclick: () => remove(row), variant: 'destructive' },
    ].filter(Boolean)} />
{/snippet}
