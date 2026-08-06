<script>
    /** Notification groups — routing configs (types × members × one optional email integration), with a members pivot drawer. */
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
    import NotificationGroupForm from './NotificationGroupForm.svelte';
    import MembersDrawer from './MembersDrawer.svelte';
    import FormsDrawer from './FormsDrawer.svelte';
    import { useIndex } from '@/lib/api/useIndex.svelte';
    import { hasPermission } from '@/lib/permissions';
    import { api } from '@/lib/api/client';
    import { toast } from '@/lib/toast';
    import { confirm } from '@/lib/confirm';

    const list = useIndex('api.v1.admin.notification-groups.index', { perPage: 15, sort: '-created_at' });

    let showForm = $state(false);
    let editing = $state(null);
    let filtersOpen = $state(false);
    let membersOpen = $state(false);
    let membersGroup = $state(null);
    let formsOpen = $state(false);
    let formsGroup = $state(null);
    let viewOpen = $state(false);
    let viewing = $state(null);
    let activityOpen = $state(false);
    let activityRow = $state(null);

    const columns = [
        { key: 'id', label: 'ID', sortable: true, width: '90px', truncate: false },
        { key: 'name', label: 'Name', sortable: true, truncate: false },
        { key: 'code', label: 'Code', sortable: true, truncate: false },
        { key: 'integration', label: 'Delivery', truncate: false },
        { key: 'types_count', label: 'Types', truncate: false },
        { key: 'members_count', label: 'Members', truncate: false },
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
        {
            key: 'integration_id',
            type: 'resource-select',
            label: 'Delivery',
            resource: 'api.v1.admin.integrations.index',
            placeholder: 'Any integration',
        },
        {
            // Where "Open these groups" from a form lands. Declared here as well
            // as on the controller so the deep link arrives as a VISIBLE,
            // clearable filter rather than a silent narrowing of the list.
            key: 'form_id',
            type: 'resource-select',
            label: 'Notified by form',
            resource: 'api.v1.admin.forms.index',
            placeholder: 'Any form',
        },
    ];

    const create = () => { editing = null; showForm = true; };
    const edit = (g) => { editing = g; showForm = true; };
    const closeForm = () => { showForm = false; editing = null; };
    const saved = () => { closeForm(); list.refresh(); };
    const manageMembers = (g) => { membersGroup = g; membersOpen = true; };
    // The other end of Forms → Notified groups: same pivot, same rows, opened
    // from whichever side the admin happens to be standing on.
    const manageForms = (g) => { formsGroup = g; formsOpen = true; };
    const view = (g) => { viewing = g; viewOpen = true; };
    const showActivity = (g) => { activityRow = g; activityOpen = true; };

    async function remove(g) {
        if (!(await confirm({ body: 'Delete this group? Its memberships are removed. This cannot be undone.', variant: 'destructive' }))) return;
        try {
            await api.delete(route('api.v1.admin.notification-groups.destroy', g.id));
            toast.success('Deleted successfully.');
            list.refresh();
        } catch (e) {
            toast.error(e?.message ?? 'Something went wrong. Please try again.');
        }
    }
</script>

<svelte:head><title>Saud International Schools — Notification Groups</title></svelte:head>

<AdminLayout title="Notification Groups">
    <IndexCard {showForm} {toolbar} {form} {table} />
    <MembersDrawer bind:open={membersOpen} group={membersGroup} />
    <FormsDrawer bind:open={formsOpen} group={formsGroup} />

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
        title="Notification group"
        id={viewing?.id}
        heading={viewing?.name}
        badge={viewing ? { label: viewing.code, variant: 'secondary' } : null}
        fields={[
            { label: 'Delivery', value: viewing?.integration?.name ?? 'In-app only' },
            { label: 'Types', value: viewing?.types?.length ? viewing.types.map((t) => t.name).join(', ') : '' },
            { label: 'Members', value: String(viewing?.members_count ?? 0) },
        ]}
        createdAt={viewing?.created_at}
        updatedAt={viewing?.updated_at}
    />
    <ActivityDrawer bind:open={activityOpen} subjectType="notification_group" subjectId={activityRow?.id} title={activityRow?.name} />
</AdminLayout>

{#snippet toolbar(inForm)}
    {#if !inForm}
        <div class="flex items-center gap-2">
            <SearchBar placeholder="Search groups…" value={list.search} onsearch={(v) => list.setSearch(v)} />
            <FilterButton count={list.activeFilters} onclick={() => (filtersOpen = true)} />
        </div>
        {#if hasPermission('notification-groups.store')}
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
    <NotificationGroupForm group={editing} onsaved={saved} oncancel={closeForm} />
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
        <span class="text-sm font-medium text-mono">{row.name}</span>
    {:else if column.key === 'code'}
        <Badge variant="secondary">{row.code}</Badge>
    {:else if column.key === 'integration'}
        {#if row.integration}
            <Badge variant="secondary"><i class="ki-filled ki-sms me-1"></i>{row.integration.name}</Badge>
        {:else}
            <span class="text-xs text-muted-foreground">In-app only</span>
        {/if}
    {:else if column.key === 'types_count'}
        <span class="text-sm text-mono">{row.types_count}</span>
    {:else if column.key === 'members_count'}
        <span class="text-sm text-mono">{row.members_count}</span>
    {:else}
        {row[column.key] ?? ''}
    {/if}
{/snippet}

{#snippet rowActions(row)}
    <RowActions actions={[
        { icon: 'ki-eye', label: 'View', onclick: () => view(row) },
        hasPermission('notification-groups.update') && { icon: 'ki-pencil', label: 'Edit', onclick: () => edit(row) },
        hasPermission('activities.index') && { icon: 'ki-time', label: 'Activity', onclick: () => showActivity(row) },
        hasPermission('notification-group-users.index') && { icon: 'ki-people', label: 'Manage members', onclick: () => manageMembers(row) },
        hasPermission('form-notification-groups.index') && { icon: 'ki-questionnaire-tablet', label: 'Forms', onclick: () => manageForms(row) },
        hasPermission('notification-groups.destroy') && { icon: 'ki-trash', label: 'Delete', onclick: () => remove(row), variant: 'destructive' },
    ].filter(Boolean)} />
{/snippet}
