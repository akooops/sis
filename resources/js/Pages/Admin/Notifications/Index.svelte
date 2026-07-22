<script>
    /**
     * Notifications — the signed-in admin's personal inbox. Read-only and
     * per-user: there is no global list and no compose — notifications are
     * emitted by the system (observers) and land here and in the topbar bell.
     * Row click opens the view drawer (and marks read); deleting removes only
     * this user's copy.
     */
    import AdminLayout from '@/layouts/AdminLayout.svelte';
    import IndexCard from '@/components/data/IndexCard.svelte';
    import DataTable from '@/components/data/DataTable.svelte';
    import SearchBar from '@/components/data/SearchBar.svelte';
    import Filters from '@/components/data/Filters.svelte';
    import FilterButton from '@/components/data/FilterButton.svelte';
    import IdBadge from '@/components/data/IdBadge.svelte';
    import DetailDrawer from '@/components/data/DetailDrawer.svelte';
    import Badge from '@/components/ui/Badge.svelte';
    import Dropdown from '@/components/ui/Dropdown.svelte';
    import { router } from '@inertiajs/svelte';
    import { useIndex } from '@/lib/api/useIndex.svelte';
    import { api } from '@/lib/api/client';
    import { toast } from '@/lib/toast';
    import { confirm } from '@/lib/confirm';
    import { unread, notificationIcon } from '@/lib/notifications.svelte';

    const list = useIndex('api.v1.admin.notifications.index', { perPage: 15, sort: '-created_at', pollMs: 20000 });

    let filtersOpen = $state(false);
    let viewOpen = $state(false);
    let viewing = $state(null);

    const columns = [
        { key: 'id', label: 'ID', width: '90px', truncate: false },
        { key: 'title', label: 'Notification', truncate: false },
        { key: 'is_read', label: 'Status', truncate: false, width: '110px' },
    ];

    const sortOptions = [
        { value: 'created_at', label: 'Received' },
        { value: 'read_at', label: 'Read at' },
    ];

    const filterConfig = [
        { key: 'read', type: 'boolean', label: 'Read' },
    ];

    async function markRead(row, { refresh = true } = {}) {
        if (row.is_read) return;
        try {
            await api.patch(route('api.v1.admin.notifications.read', row.id));
            row.is_read = true;
            row.read_at = new Date().toISOString();
            unread.dec();
            if (refresh) list.refresh();
        } catch {
            // ignore — the bell poll reconciles the count
        }
    }

    function hasRoute(name) {
        try {
            return !!name && route().has(name);
        } catch {
            return false;
        }
    }

    /** Row click: open the view drawer and mark the row read in the background. */
    function view(row) {
        viewing = row;
        viewOpen = true;
        markRead(row, { refresh: false });
    }

    function openLink(row) {
        if (!hasRoute(row?.route_name)) return;
        markRead(row, { refresh: false });
        router.visit(route(row.route_name, row.route_params ?? undefined));
    }

    async function markAll() {
        try {
            await api.post(route('api.v1.admin.notifications.read-all'));
            unread.reset();
            list.refresh();
            toast.success('All notifications marked as read.');
        } catch (e) {
            toast.error(e?.message ?? 'Something went wrong. Please try again.');
        }
    }

    async function remove(row) {
        if (!(await confirm({ body: 'Remove this notification from your inbox? This cannot be undone.', variant: 'destructive' }))) return;
        try {
            await api.delete(route('api.v1.admin.notifications.destroy', row.id));
            if (!row.is_read) unread.dec();
            if (viewing?.id === row.id) viewOpen = false;
            toast.success('Notification removed.');
            list.refresh();
        } catch (e) {
            toast.error(e?.message ?? 'Something went wrong. Please try again.');
        }
    }
</script>

<svelte:head><title>Saud International Schools — Notifications</title></svelte:head>

<AdminLayout title="Notifications">
    <IndexCard {toolbar} {table} />

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
        title="Notification"
        id={viewing?.id}
        heading={viewing?.title}
        badge={viewing ? { label: viewing.is_read ? 'Read' : 'Unread', variant: viewing.is_read ? 'secondary' : 'primary' } : null}
        fields={[
            { label: 'Type', value: viewing?.type_name ?? '—' },
            { label: 'Message', value: viewing?.body ?? '—' },
            { label: 'Received', date: viewing?.created_at },
            { label: 'Read', date: viewing?.read_at },
        ]}
    >
        {#if hasRoute(viewing?.route_name)}
            <button class="kt-btn kt-btn-sm kt-btn-primary self-start" onclick={() => openLink(viewing)}>
                <i class="ki-filled ki-exit-right-corner"></i>Open related page
            </button>
        {/if}
    </DetailDrawer>
</AdminLayout>

{#snippet toolbar()}
    <div class="flex items-center gap-2">
        <SearchBar placeholder="Search notifications…" value={list.search} onsearch={(v) => list.setSearch(v)} />
        <FilterButton count={list.activeFilters} onclick={() => (filtersOpen = true)} />
    </div>
    {#if unread.count > 0}
        <button class="kt-btn kt-btn-sm kt-btn-primary" onclick={markAll}>
            <i class="ki-filled ki-check"></i>Mark all read
        </button>
    {/if}
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
        emptyTitle="No notifications"
        emptyBody="You're all caught up."
        {cells}
        {rowActions}
    />
{/snippet}

{#snippet cells(row, column)}
    {#if column.key === 'id'}
        <IdBadge id={row.id} onclick={() => view(row)} />
    {:else if column.key === 'title'}
        <div class="flex items-center gap-3">
            <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-muted text-muted-foreground">
                <i class="ki-filled {notificationIcon(row)}"></i>
            </span>
            <div class="flex min-w-0 flex-col">
                <span class="truncate text-sm text-mono {row.is_read ? 'font-medium' : 'font-semibold'}">{row.title}</span>
                {#if row.body}<span class="line-clamp-1 text-xs text-muted-foreground">{row.body}</span>{/if}
            </div>
        </div>
    {:else if column.key === 'is_read'}
        <Badge variant={row.is_read ? 'secondary' : 'primary'}>{row.is_read ? 'Read' : 'Unread'}</Badge>
    {:else}
        {row[column.key] ?? '—'}
    {/if}
{/snippet}

{#snippet rowActions(row)}
    <Dropdown>
        {#snippet trigger()}
            <button class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" aria-label="Actions"><i class="ki-filled ki-dots-vertical"></i></button>
        {/snippet}
        <div class="kt-menu-item">
            <button class="kt-menu-link" data-dropdown-dismiss onclick={() => view(row)}>
                <span class="kt-menu-icon"><i class="ki-filled ki-eye"></i></span><span class="kt-menu-title">View</span>
            </button>
        </div>
        {#if hasRoute(row.route_name)}
            <div class="kt-menu-item">
                <button class="kt-menu-link" data-dropdown-dismiss onclick={() => openLink(row)}>
                    <span class="kt-menu-icon"><i class="ki-filled ki-exit-right-corner"></i></span><span class="kt-menu-title">Open related page</span>
                </button>
            </div>
        {/if}
        {#if !row.is_read}
            <div class="kt-menu-item">
                <button class="kt-menu-link" data-dropdown-dismiss onclick={() => markRead(row)}>
                    <span class="kt-menu-icon"><i class="ki-filled ki-check"></i></span><span class="kt-menu-title">Mark as read</span>
                </button>
            </div>
        {/if}
        <div class="kt-menu-separator"></div>
        <div class="kt-menu-item">
            <button class="kt-menu-link text-destructive" data-dropdown-dismiss onclick={() => remove(row)}>
                <span class="kt-menu-icon"><i class="ki-filled ki-trash"></i></span><span class="kt-menu-title">Remove</span>
            </button>
        </div>
    </Dropdown>
{/snippet}
