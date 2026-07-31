<script>
    /**
     * Subscribers index — the public signups on every mailing list, in one table.
     *
     * These are not Users: an address here never signs in, and the same person may
     * appear once per list.
     */
    import AdminLayout from '@/layouts/AdminLayout.svelte';
    import IndexCard from '@/components/data/IndexCard.svelte';
    import DataTable from '@/components/data/DataTable.svelte';
    import SearchBar from '@/components/data/SearchBar.svelte';
    import Filters from '@/components/data/Filters.svelte';
    import FilterButton from '@/components/data/FilterButton.svelte';
    import Badge from '@/components/ui/Badge.svelte';
    import ClampText from '@/components/ui/ClampText.svelte';
    import DateTime from '@/components/ui/DateTime.svelte';
    import IdBadge from '@/components/data/IdBadge.svelte';
    import RowActions from '@/components/data/RowActions.svelte';
    import DetailDrawer from '@/components/data/DetailDrawer.svelte';
    import ActivityDrawer from '@/components/activity/ActivityDrawer.svelte';
    import SubscriberForm from './SubscriberForm.svelte';
    import { useIndex } from '@/lib/api/useIndex.svelte';
    import { formatDateTime } from '@/lib/date';
    import { hasPermission } from '@/lib/permissions';
    import { api } from '@/lib/api/client';
    import { toast } from '@/lib/toast';
    import { confirm } from '@/lib/confirm';

    const list = useIndex('api.v1.admin.newsletter-group-subscribers.index', { perPage: 15, sort: '-created_at' });

    let showForm = $state(false);
    let editing = $state(null);
    let filtersOpen = $state(false);
    let viewOpen = $state(false);
    let viewing = $state(null);
    let activityOpen = $state(false);
    let activityRow = $state(null);

    const groupId = $derived(list.params.filter?.newsletter_group_id ?? null);

    // Deep link (/admin/newsletter-group-subscribers?filter[newsletter_group_id]=<id>):
    // the loaded rows already carry their group, so the filter drawer can show its
    // NAME straight away. Select resolves the label itself when no row matched — an
    // empty list has nothing here to read it from.
    const filteredGroup = $derived(
        groupId ? (list.rows.find((r) => r.group?.id === groupId)?.group ?? null) : null,
    );

    const columns = [
        { key: 'id', label: 'ID', sortable: true, width: '90px', truncate: false },
        { key: 'name', label: 'Name', sortable: true, truncate: false },
        { key: 'email', label: 'Email', sortable: true, truncate: false },
        { key: 'group', label: 'Group', truncate: false },
        { key: 'is_active', label: 'Status', width: '110px', truncate: false },
        { key: 'subscribed_at', label: 'Subscribed', truncate: false },
    ];

    // Mirrors the controller's allowedSorts.
    const sortOptions = [
        { value: 'id', label: 'ID' },
        { value: 'name', label: 'Name' },
        { value: 'email', label: 'Email' },
        { value: 'created_at', label: 'Created' },
    ];

    // Mirrors the controller's allowedFilters.
    const filterConfig = [
        {
            key: 'newsletter_group_id',
            type: 'resource-select',
            label: 'Group',
            resource: 'api.v1.admin.newsletter-groups.index',
            labelKey: 'name',
            placeholder: 'All groups',
            initialOptions: () => (filteredGroup ? [{ value: filteredGroup.id, label: filteredGroup.name }] : []),
        },
        { key: 'is_active', type: 'boolean', label: 'Active' },
    ];

    /** A signup may leave the name blank, so the address is what identifies it. */
    const label = (s) => s?.name || s?.email || null;

    const create = () => { editing = null; showForm = true; };
    const edit = (s) => { editing = s; showForm = true; };
    const closeForm = () => { showForm = false; editing = null; };
    const saved = () => { closeForm(); list.refresh(); };
    const view = (s) => { viewing = s; viewOpen = true; };
    const showActivity = (s) => { activityRow = s; activityOpen = true; };

    async function remove(s) {
        if (!(await confirm({
            body: `Delete ${s.email}? They are removed from this list entirely — switch them to inactive instead to keep the record.`,
            variant: 'destructive',
        }))) return;
        try {
            await api.delete(route('api.v1.admin.newsletter-group-subscribers.destroy', s.id));
            toast.success('Deleted successfully.');
            list.refresh();
        } catch (e) {
            toast.error(e?.message ?? 'Something went wrong. Please try again.');
        }
    }
</script>

<svelte:head><title>Saud International Schools — Subscribers</title></svelte:head>

<AdminLayout title="Subscribers">
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
        title="Subscriber"
        id={viewing?.id}
        heading={label(viewing)}
        subheading={viewing?.name ? viewing.email : null}
        badge={viewing ? { label: viewing.is_active ? 'Active' : 'Inactive', variant: viewing.is_active ? 'success' : 'secondary' } : null}
        fields={[
            { label: 'Group', value: viewing?.group?.name || '' },
            { label: 'Email', value: viewing?.email },
            { label: 'Subscribed', value: formatDateTime(viewing?.subscribed_at) },
        ]}
        createdAt={viewing?.created_at}
        updatedAt={viewing?.updated_at}
    />
    <ActivityDrawer bind:open={activityOpen} subjectType="newsletter_group_subscriber" subjectId={activityRow?.id} title={label(activityRow)} />
</AdminLayout>

{#snippet toolbar(inForm)}
    {#if !inForm}
        <div class="flex items-center gap-2">
            <SearchBar placeholder="Search subscribers…" value={list.search} onsearch={(v) => list.setSearch(v)} />
            <FilterButton count={list.activeFilters} onclick={() => (filtersOpen = true)} />
        </div>
        {#if hasPermission('newsletter-group-subscribers.store')}
            <button class="kt-btn kt-btn-sm kt-btn-primary" onclick={create}>
                <i class="ki-filled ki-plus"></i>Add subscriber
            </button>
        {/if}
    {:else}
        <button class="kt-btn kt-btn-sm kt-btn-secondary" onclick={closeForm}>
            <i class="ki-filled ki-black-left"></i>Cancel
        </button>
    {/if}
{/snippet}

{#snippet form()}
    <SubscriberForm subscriber={editing} {groupId} onsaved={saved} oncancel={closeForm} />
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
        emptyTitle="No subscribers yet"
        emptyBody="Signups from the public site land here, or add an address by hand."
        {cells}
        {rowActions}
    />
{/snippet}

{#snippet cells(row, column)}
    {#if column.key === 'id'}
        <IdBadge id={row.id} onclick={() => view(row)} />
    {:else if column.key === 'name'}
        {#if row.name}
            <span class="text-sm font-medium text-mono">
                <ClampText value={row.name} maxWidth="200px" title={row.name} />
            </span>
        {:else}
            
        {/if}
    {:else if column.key === 'email'}
        <span class="text-sm text-mono">
            <ClampText value={row.email} maxWidth="240px" title={row.email} />
        </span>
    {:else if column.key === 'group'}
        {#if row.group}
            <Badge variant="primary">
                <ClampText value={row.group.name} maxWidth="180px" title={row.group.name} />
            </Badge>
        {:else}
            
        {/if}
    {:else if column.key === 'is_active'}
        <Badge variant={row.is_active ? 'success' : 'secondary'}>{row.is_active ? 'Active' : 'Inactive'}</Badge>
    {:else if column.key === 'subscribed_at'}
        {#if row.subscribed_at}<DateTime value={row.subscribed_at} />{:else}{/if}
    {:else}
        {row[column.key] ?? ''}
    {/if}
{/snippet}

{#snippet rowActions(row)}
    <RowActions actions={[
        { icon: 'ki-eye', label: 'View', onclick: () => view(row) },
        hasPermission('newsletter-group-subscribers.update') && { icon: 'ki-pencil', label: 'Edit', onclick: () => edit(row) },
        hasPermission('activities.index') && { icon: 'ki-time', label: 'Activity', onclick: () => showActivity(row) },
        hasPermission('newsletter-group-subscribers.destroy')
            && { icon: 'ki-trash', label: 'Delete', onclick: () => remove(row), variant: 'destructive' },
    ].filter(Boolean)} />
{/snippet}
