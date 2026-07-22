<script>
    /** Notifications — compose (fly-in) + sent history. */
    import AdminLayout from '@/layouts/AdminLayout.svelte';
    import IndexCard from '@/components/data/IndexCard.svelte';
    import DataTable from '@/components/data/DataTable.svelte';
    import SearchBar from '@/components/data/SearchBar.svelte';
    import Badge from '@/components/ui/Badge.svelte';
    import DateTime from '@/components/ui/DateTime.svelte';
    import Dropdown from '@/components/ui/Dropdown.svelte';
    import IdBadge from '@/components/data/IdBadge.svelte';
    import NotificationForm from './NotificationForm.svelte';
    import { useIndex } from '@/lib/api/useIndex.svelte';
    import { hasPermission } from '@/lib/permissions';
    import { api } from '@/lib/api/client';
    import { toast } from '@/lib/toast';
    import { confirm } from '@/lib/confirm';

    const list = useIndex('api.v1.admin.notifications.index', { perPage: 15, sort: '-created_at', pollMs: 20000 });

    let showForm = $state(false);

    const columns = [
        { key: 'id', label: 'ID', sortable: true, width: '90px', truncate: false },
        { key: 'title', label: 'Title', sortable: true, truncate: false },
        { key: 'type', label: 'Type', sortable: true, truncate: false },
        { key: 'recipients_count', label: 'Recipients', truncate: false },
        { key: 'created_at', label: 'Sent', sortable: true, truncate: false },
    ];

    const create = () => { showForm = true; };
    const closeForm = () => { showForm = false; };
    const saved = () => { closeForm(); list.refresh(); };

    async function remove(row) {
        if (!(await confirm({ body: 'Delete this notification for everyone who received it? This cannot be undone.', variant: 'destructive' }))) return;
        try {
            await api.delete(route('api.v1.admin.notifications.destroy', row.id));
            toast.success('Deleted successfully.');
            list.refresh();
        } catch (e) {
            toast.error(e?.message ?? 'Something went wrong. Please try again.');
        }
    }
</script>

<svelte:head><title>Saud International Schools — Notifications</title></svelte:head>

<AdminLayout title="Notifications">
    <IndexCard {showForm} {toolbar} {form} {table} />
</AdminLayout>

{#snippet toolbar(inForm)}
    {#if !inForm}
        <div class="flex items-center gap-2">
            <SearchBar placeholder="Search notifications…" value={list.search} onsearch={(v) => list.setSearch(v)} />
        </div>
        {#if hasPermission('notifications.store')}
            <button class="kt-btn kt-btn-sm kt-btn-primary" onclick={create}>
                <i class="ki-filled ki-plus"></i>Compose
            </button>
        {/if}
    {:else}
        <button class="kt-btn kt-btn-sm kt-btn-secondary" onclick={closeForm}>
            <i class="ki-filled ki-black-left"></i>Cancel
        </button>
    {/if}
{/snippet}

{#snippet form()}
    <NotificationForm onsaved={saved} oncancel={closeForm} />
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
        {cells}
        {rowActions}
    />
{/snippet}

{#snippet cells(row, column)}
    {#if column.key === 'id'}
        <IdBadge id={row.id} />
    {:else if column.key === 'title'}
        <span class="text-sm font-medium text-mono">{row.title}</span>
    {:else if column.key === 'type'}
        <Badge variant="secondary">{row.type}</Badge>
    {:else if column.key === 'recipients_count'}
        <span class="text-sm text-mono">{row.recipients_count}</span>
    {:else if column.key === 'created_at'}
        <DateTime value={row.created_at} />
    {:else}
        {row[column.key] ?? '—'}
    {/if}
{/snippet}

{#snippet rowActions(row)}
    {#if hasPermission('notifications.destroy')}
        <Dropdown>
            {#snippet trigger()}
                <button class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" aria-label="Actions"><i class="ki-filled ki-dots-vertical"></i></button>
            {/snippet}
            <div class="kt-menu-item">
                <button class="kt-menu-link text-destructive" data-dropdown-dismiss onclick={() => remove(row)}>
                    <span class="kt-menu-icon"><i class="ki-filled ki-trash"></i></span><span class="kt-menu-title">Delete</span>
                </button>
            </div>
        </Dropdown>
    {/if}
{/snippet}
