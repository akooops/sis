<script>
    /** Users index — Metronic list card with fly-in create/edit, live table, drawers. */
    import AdminLayout from '@/layouts/AdminLayout.svelte';
    import IndexCard from '@/components/data/IndexCard.svelte';
    import DataTable from '@/components/data/DataTable.svelte';
    import SearchBar from '@/components/data/SearchBar.svelte';
    import Filters from '@/components/data/Filters.svelte';
    import FilterButton from '@/components/data/FilterButton.svelte';
    import ExportButton from '@/components/data/ExportButton.svelte';
    import Avatar from '@/components/ui/Avatar.svelte';
    import Badge from '@/components/ui/Badge.svelte';
    import DateTime from '@/components/ui/DateTime.svelte';
    import Dropdown from '@/components/ui/Dropdown.svelte';
    import IdBadge from '@/components/data/IdBadge.svelte';
    import DetailDrawer from '@/components/data/DetailDrawer.svelte';
    import ActivityDrawer from '@/components/activity/ActivityDrawer.svelte';
    import UserForm from './UserForm.svelte';
    import RolesDrawer from './RolesDrawer.svelte';
    import SessionsDrawer from './SessionsDrawer.svelte';
    import { useIndex } from '@/lib/api/useIndex.svelte';
    import { USER_STATUS_LABELS, USER_STATUS_VARIANTS } from '@/lib/user';
    import { authUser, hasPermission } from '@/lib/permissions';
    import { api } from '@/lib/api/client';
    import { toast } from '@/lib/toast';
    import { confirm } from '@/lib/confirm';

    const list = useIndex('api.v1.admin.users.index', { perPage: 15, sort: '-created_at', pollMs: 20000 });

    let showForm = $state(false);
    let editing = $state(null);
    let filtersOpen = $state(false);
    let viewOpen = $state(false);
    let viewing = $state(null);
    let activityOpen = $state(false);
    let activityRow = $state(null);
    let rolesOpen = $state(false);
    let rolesUser = $state(null);
    let sessionsOpen = $state(false);
    let sessionsUser = $state(null);

    const columns = [
        { key: 'id', label: 'ID', sortable: true, width: '90px', truncate: false },
        { key: 'firstname', label: 'Name', sortable: true, truncate: false },
        { key: 'email', label: 'Email', sortable: true },
        { key: 'phone', label: 'Phone', truncate: false },
        { key: 'status', label: 'Status', truncate: false },
    ];

    // Mirrors the controller's allowedSorts — the drawer and the table headers
    // drive the same `sort`, so a column here must be sortable server-side.
    const sortOptions = [
        { value: 'id', label: 'ID' },
        { value: 'firstname', label: 'First name' },
        { value: 'lastname', label: 'Last name' },
        { value: 'username', label: 'Username' },
        { value: 'email', label: 'Email' },
        { value: 'created_at', label: 'Created' },
    ];

    // Mirrors the controller's allowedFilters. Anything not listed there is a
    // 400 from the query builder, and everything else is reachable by search.
    const filterConfig = [
        {
            key: 'status',
            type: 'select',
            label: 'Status',
            options: Object.entries(USER_STATUS_LABELS).map(([value, label]) => ({ value, label })),
        },
    ];

    const viewFields = $derived(
        viewing
            ? [
                  { label: 'Username', value: viewing.username },
                  { label: 'Email', value: viewing.email },
                  { label: 'Phone', value: viewing.phone || '—' },
              ]
            : [],
    );

    /**
     * Which menu groups have anything in them for this row. The first group
     * always does (View), so only the rest need checking — a separator before an
     * empty group is a line to nowhere.
     */
    function menuGroups(row) {
        return {
            status:
                (hasPermission('users.approve') && row.status !== 'approved') ||
                (hasPermission('users.verify') && row.status === 'pending') ||
                (hasPermission('users.reject') && row.status !== 'rejected'),
            related:
                hasPermission('activities.index') ||
                hasPermission('user-roles.index') ||
                hasPermission('sessions.index') ||
                hasPermission('users.logout-devices'),
            danger: hasPermission('users.destroy'),
        };
    }

    async function logoutDevices(u) {
        const self = u.id === authUser()?.id;

        if (
            !(await confirm({
                title: 'Log out all devices',
                body: self
                    ? 'This is your own account — you will be signed out immediately.'
                    : `Sign ${u.firstname} out of every device they are logged in on?`,
                variant: 'destructive',
            }))
        ) {
            return;
        }

        try {
            const data = await api.post(route('api.v1.admin.users.logout-devices', u.id));
            toast.success(`Logged out of ${data?.revoked ?? 0} device(s).`);
        } catch (e) {
            toast.error(e?.message ?? 'Something went wrong. Please try again.');
        }
    }

    const create = () => { editing = null; showForm = true; };
    const edit = (u) => { editing = u; showForm = true; };
    const closeForm = () => { showForm = false; editing = null; };
    const saved = () => { closeForm(); list.refresh(); };
    const view = (u) => { viewing = u; viewOpen = true; };
    const showActivity = (u) => { activityRow = u; activityOpen = true; };
    const manageRoles = (u) => { rolesUser = u; rolesOpen = true; };
    const manageSessions = (u) => { sessionsUser = u; sessionsOpen = true; };

    // approve | reject | verify — the API rejects a transition the status
    // doesn't allow, so surface its message rather than guessing client-side.
    async function setStatus(u, action) {
        try {
            await api.post(route(`api.v1.admin.users.${action}`, u.id));
            toast.success('Updated successfully.');
            list.refresh();
        } catch (e) {
            toast.error(e?.message ?? 'Something went wrong. Please try again.');
        }
    }

    async function remove(u) {
        if (!(await confirm({ body: 'Are you sure you want to delete this record? This action cannot be undone.', variant: 'destructive' }))) return;
        try {
            await api.delete(route('api.v1.admin.users.destroy', u.id));
            toast.success('Deleted successfully.');
            list.refresh();
        } catch (e) {
            toast.error(e?.message ?? 'Something went wrong. Please try again.');
        }
    }
</script>

<svelte:head><title>Saud International Schools — Users</title></svelte:head>

<AdminLayout title="Users">
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
        title="User"
        id={viewing?.id}
        avatar={{ src: viewing?.avatar_url, name: `${viewing?.firstname ?? ''} ${viewing?.lastname ?? ''}` }}
        heading={`${viewing?.firstname ?? ''} ${viewing?.lastname ?? ''}`}
        badge={viewing ? { label: USER_STATUS_LABELS[viewing.status] ?? viewing.status, variant: USER_STATUS_VARIANTS[viewing.status] ?? 'secondary' } : null}
        fields={viewFields}
        createdAt={viewing?.created_at}
        updatedAt={viewing?.updated_at}
    />
    <RolesDrawer bind:open={rolesOpen} user={rolesUser} />
    <SessionsDrawer bind:open={sessionsOpen} user={sessionsUser} />
    <ActivityDrawer bind:open={activityOpen} subjectType="user" subjectId={activityRow?.id} title={activityRow?.username} />
</AdminLayout>

{#snippet toolbar(inForm)}
    {#if !inForm}
        <div class="flex items-center gap-2">
            <SearchBar placeholder="Search users…" value={list.search} onsearch={(v) => list.setSearch(v)} />
            <FilterButton count={list.activeFilters} onclick={() => (filtersOpen = true)} />
            <ExportButton rows={list.rows} columns={[
                { key: 'firstname', label: 'First name' },
                { key: 'lastname', label: 'Last name' },
                { key: 'username', label: 'Username' },
                { key: 'email', label: 'Email' },
                { key: 'created_at', label: 'Created' },
            ]} filename="users" />
        </div>
        {#if hasPermission('users.store')}
            <button class="kt-btn kt-btn-sm kt-btn-primary" onclick={create}>
                <i class="ki-filled ki-plus"></i>Add user
            </button>
        {/if}
    {:else}
        <button class="kt-btn kt-btn-sm kt-btn-secondary" onclick={closeForm}>
            <i class="ki-filled ki-black-left"></i>Cancel
        </button>
    {/if}
{/snippet}

{#snippet form()}
    <UserForm user={editing} onsaved={saved} oncancel={closeForm} />
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
    {:else if column.key === 'firstname'}
        <div class="flex items-center gap-3">
            <Avatar src={row.avatar_url} name={`${row.firstname} ${row.lastname}`} size="sm" />
            <div class="flex flex-col">
                <span class="text-sm font-medium text-mono">{row.firstname} {row.lastname}</span>
                <span class="text-xs text-muted-foreground">{row.username}</span>
            </div>
        </div>
    {:else if column.key === 'roles'}
        <div class="flex flex-wrap gap-1">
            {#each row.roles ?? [] as r}<Badge variant="secondary">{r.name}</Badge>{/each}
        </div>
    {:else if column.key === 'status'}
        <Badge variant={USER_STATUS_VARIANTS[row.status] ?? 'secondary'}>
            {USER_STATUS_LABELS[row.status] ?? row.status}
        </Badge>
    {:else if column.key === 'created_at'}
        <DateTime value={row.created_at} />
    {:else}
        {row[column.key] ?? '—'}
    {/if}
{/snippet}

{#snippet rowActions(row)}
    {@const g = menuGroups(row)}
    <Dropdown>
        {#snippet trigger()}
            <button class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" aria-label="Actions"><i class="ki-filled ki-dots-vertical"></i></button>
        {/snippet}

        <!-- the record itself -->
        <div class="kt-menu-item">
            <button class="kt-menu-link" data-dropdown-dismiss onclick={() => view(row)}>
                <span class="kt-menu-icon"><i class="ki-filled ki-eye"></i></span><span class="kt-menu-title">View</span>
            </button>
        </div>
        {#if hasPermission('users.update')}
            <div class="kt-menu-item">
                <button class="kt-menu-link" data-dropdown-dismiss onclick={() => edit(row)}>
                    <span class="kt-menu-icon"><i class="ki-filled ki-pencil"></i></span><span class="kt-menu-title">Edit</span>
                </button>
            </div>
        {/if}

        <!-- what it can become -->
        {#if g.status}
            <div class="kt-menu-separator"></div>
            {#if hasPermission('users.approve') && row.status !== 'approved'}
                <div class="kt-menu-item">
                    <button class="kt-menu-link" data-dropdown-dismiss onclick={() => setStatus(row, 'approve')}>
                        <span class="kt-menu-icon"><i class="ki-filled ki-check-circle"></i></span>
                        <span class="kt-menu-title">Approve</span>
                    </button>
                </div>
            {/if}
            {#if hasPermission('users.verify') && row.status === 'pending'}
                <div class="kt-menu-item">
                    <button class="kt-menu-link" data-dropdown-dismiss onclick={() => setStatus(row, 'verify')}>
                        <span class="kt-menu-icon"><i class="ki-filled ki-shield-tick"></i></span>
                        <span class="kt-menu-title">Verify</span>
                    </button>
                </div>
            {/if}
            {#if hasPermission('users.reject') && row.status !== 'rejected'}
                <div class="kt-menu-item">
                    <button class="kt-menu-link" data-dropdown-dismiss onclick={() => setStatus(row, 'reject')}>
                        <span class="kt-menu-icon"><i class="ki-filled ki-cross-circle"></i></span>
                        <span class="kt-menu-title">Reject</span>
                    </button>
                </div>
            {/if}
        {/if}

        <!-- what hangs off it -->
        {#if g.related}
            <div class="kt-menu-separator"></div>
            {#if hasPermission('activities.index')}
                <div class="kt-menu-item">
                    <button class="kt-menu-link" data-dropdown-dismiss onclick={() => showActivity(row)}>
                        <span class="kt-menu-icon"><i class="ki-filled ki-time"></i></span><span class="kt-menu-title">Activity</span>
                    </button>
                </div>
            {/if}
            {#if hasPermission('user-roles.index')}
                <div class="kt-menu-item">
                    <button class="kt-menu-link" data-dropdown-dismiss onclick={() => manageRoles(row)}>
                        <span class="kt-menu-icon"><i class="ki-filled ki-shield-tick"></i></span><span class="kt-menu-title">Manage roles</span>
                    </button>
                </div>
            {/if}
            {#if hasPermission('sessions.index')}
                <div class="kt-menu-item">
                    <button class="kt-menu-link" data-dropdown-dismiss onclick={() => manageSessions(row)}>
                        <span class="kt-menu-icon"><i class="ki-filled ki-technology-4"></i></span><span class="kt-menu-title">Sessions</span>
                    </button>
                </div>
            {/if}
            {#if hasPermission('users.logout-devices')}
                <div class="kt-menu-item">
                    <button class="kt-menu-link" data-dropdown-dismiss onclick={() => logoutDevices(row)}>
                        <span class="kt-menu-icon"><i class="ki-filled ki-entrance-right"></i></span><span class="kt-menu-title">Log out all devices</span>
                    </button>
                </div>
            {/if}
        {/if}

        <!-- destroys it: on its own, away from Edit -->
        {#if g.danger}
            <div class="kt-menu-separator"></div>
            <div class="kt-menu-item">
                <button class="kt-menu-link text-destructive" data-dropdown-dismiss onclick={() => remove(row)}>
                    <span class="kt-menu-icon"><i class="ki-filled ki-trash"></i></span><span class="kt-menu-title">Delete</span>
                </button>
            </div>
        {/if}
    </Dropdown>
{/snippet}
