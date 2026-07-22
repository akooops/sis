<script>
    /** Notification groups — routing configs (types × members × one optional email integration), with a members pivot drawer. */
    import AdminLayout from '@/layouts/AdminLayout.svelte';
    import IndexCard from '@/components/data/IndexCard.svelte';
    import DataTable from '@/components/data/DataTable.svelte';
    import SearchBar from '@/components/data/SearchBar.svelte';
    import Badge from '@/components/ui/Badge.svelte';
    import Dropdown from '@/components/ui/Dropdown.svelte';
    import IdBadge from '@/components/data/IdBadge.svelte';
    import DetailDrawer from '@/components/data/DetailDrawer.svelte';
    import NotificationGroupForm from './NotificationGroupForm.svelte';
    import MembersDrawer from './MembersDrawer.svelte';
    import { useIndex } from '@/lib/api/useIndex.svelte';
    import { hasPermission } from '@/lib/permissions';
    import { api } from '@/lib/api/client';
    import { toast } from '@/lib/toast';
    import { confirm } from '@/lib/confirm';

    const list = useIndex('api.v1.admin.notification-groups.index', { perPage: 15, sort: '-created_at' });

    let showForm = $state(false);
    let editing = $state(null);
    let membersOpen = $state(false);
    let membersGroup = $state(null);
    let viewOpen = $state(false);
    let viewing = $state(null);

    const columns = [
        { key: 'id', label: 'ID', sortable: true, width: '90px', truncate: false },
        { key: 'name', label: 'Name', sortable: true, truncate: false },
        { key: 'code', label: 'Code', sortable: true, truncate: false },
        { key: 'integration', label: 'Delivery', truncate: false },
        { key: 'types_count', label: 'Types', truncate: false },
        { key: 'members_count', label: 'Members', truncate: false },
    ];

    const create = () => { editing = null; showForm = true; };
    const edit = (g) => { editing = g; showForm = true; };
    const closeForm = () => { showForm = false; editing = null; };
    const saved = () => { closeForm(); list.refresh(); };
    const manageMembers = (g) => { membersGroup = g; membersOpen = true; };
    const view = (g) => { viewing = g; viewOpen = true; };

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

    <DetailDrawer
        bind:open={viewOpen}
        title="Notification group"
        id={viewing?.id}
        heading={viewing?.name}
        badge={viewing ? { label: viewing.code, variant: 'secondary' } : null}
        fields={[
            { label: 'Delivery', value: viewing?.integration?.name ?? 'In-app only' },
            { label: 'Types', value: viewing?.types?.length ? viewing.types.map((t) => t.name).join(', ') : '—' },
            { label: 'Members', value: String(viewing?.members_count ?? 0) },
        ]}
        createdAt={viewing?.created_at}
        updatedAt={viewing?.updated_at}
    />
</AdminLayout>

{#snippet toolbar(inForm)}
    {#if !inForm}
        <div class="flex items-center gap-2">
            <SearchBar placeholder="Search groups…" value={list.search} onsearch={(v) => list.setSearch(v)} />
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
        {#if hasPermission('notification-groups.update')}
            <div class="kt-menu-item">
                <button class="kt-menu-link" data-dropdown-dismiss onclick={() => edit(row)}>
                    <span class="kt-menu-icon"><i class="ki-filled ki-pencil"></i></span><span class="kt-menu-title">Edit</span>
                </button>
            </div>
        {/if}

        <!-- what hangs off it -->
        {#if hasPermission('notification-group-users.index')}
            <div class="kt-menu-separator"></div>
            <div class="kt-menu-item">
                <button class="kt-menu-link" data-dropdown-dismiss onclick={() => manageMembers(row)}>
                    <span class="kt-menu-icon"><i class="ki-filled ki-people"></i></span><span class="kt-menu-title">Manage members</span>
                </button>
            </div>
        {/if}
        {#if hasPermission('notification-groups.destroy')}
            <div class="kt-menu-separator"></div>
            <div class="kt-menu-item">
                <button class="kt-menu-link text-destructive" data-dropdown-dismiss onclick={() => remove(row)}>
                    <span class="kt-menu-icon"><i class="ki-filled ki-trash"></i></span><span class="kt-menu-title">Delete</span>
                </button>
            </div>
        {/if}
    </Dropdown>
{/snippet}
