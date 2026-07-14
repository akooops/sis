<script>
    /** Roles index — Metronic list card with fly-in create/edit + permissions drawer. */
    import AdminLayout from '@/layouts/AdminLayout.svelte';
    import IndexCard from '@/components/data/IndexCard.svelte';
    import DataTable from '@/components/data/DataTable.svelte';
    import SearchBar from '@/components/data/SearchBar.svelte';
    import Filters from '@/components/data/Filters.svelte';
    import ExportButton from '@/components/data/ExportButton.svelte';
    import Badge from '@/components/ui/Badge.svelte';
    import DateTime from '@/components/ui/DateTime.svelte';
    import Dropdown from '@/components/ui/Dropdown.svelte';
    import IdBadge from '@/components/data/IdBadge.svelte';
    import DetailDrawer from '@/components/data/DetailDrawer.svelte';
    import RoleForm from './RoleForm.svelte';
    import RolePermissionsDrawer from './RolePermissionsDrawer.svelte';
    import { useIndex } from '@/lib/api/useIndex.svelte';
    import { hasPermission } from '@/lib/permissions';
    import { api } from '@/lib/api/client';
    import { toast } from '@/lib/toast';
    import { confirm } from '@/lib/confirm';
    import { t } from '@/lib/i18n';

    const list = useIndex('api.v1.admin.roles.index', { perPage: 15, include: 'permissions', sort: '-created_at', pollMs: 20000 });

    let showForm = $state(false);
    let editing = $state(null);
    let filtersOpen = $state(false);
    let permsOpen = $state(false);
    let permsRole = $state(null);
    let viewOpen = $state(false);
    let viewing = $state(null);

    const columns = $derived([
        { key: 'id', label: $t('common.detail.id'), width: '90px', truncate: false },
        { key: 'name', label: $t('roles.fields.name'), sortable: true },
        { key: 'is_default', label: $t('roles.fields.is_default'), sortable: true, truncate: false },
        { key: 'permissions', label: $t('roles.fields.permissions'), truncate: false },
    ]);
    const filterConfig = $derived([{ key: 'is_default', type: 'boolean', label: $t('roles.fields.is_default') }]);

    const create = () => { editing = null; showForm = true; };
    const edit = (r) => { editing = r; showForm = true; };
    const closeForm = () => { showForm = false; editing = null; };
    const saved = () => { closeForm(); list.refresh(); };
    const managePerms = (r) => { permsRole = r; permsOpen = true; };
    const view = (r) => { viewing = r; viewOpen = true; };

    const viewFields = $derived(
        viewing
            ? [
                  { label: $t('roles.fields.name'), value: viewing.name },
                  { label: $t('roles.fields.is_default'), value: viewing.is_default ? $t('common.filters.yes') : $t('common.filters.no') },
                  { label: $t('roles.fields.permissions'), value: String((viewing.permissions ?? []).length) },
              ]
            : [],
    );

    async function remove(r) {
        if (!(await confirm({ body: $t('common.confirm.delete_body'), variant: 'destructive' }))) return;
        try {
            await api.delete(route('api.v1.admin.roles.destroy', r.id));
            toast.success($t('common.feedback.deleted'));
            list.refresh();
        } catch (e) {
            toast.error(e?.message ?? $t('common.feedback.error'));
        }
    }
</script>

<svelte:head><title>Novonordisk — {$t('roles.title')}</title></svelte:head>

<AdminLayout breadcrumbs={[{ label: $t('roles.title') }]}>
    <IndexCard {showForm} {toolbar} {form} {table} />
    <Filters bind:open={filtersOpen} config={filterConfig} values={list.params.filter} onapply={(v) => list.setFilters(v)} />
    <RolePermissionsDrawer bind:open={permsOpen} role={permsRole} />
    <DetailDrawer bind:open={viewOpen} title={$t('roles.title')} id={viewing?.id} fields={viewFields} createdAt={viewing?.created_at} updatedAt={viewing?.updated_at} deletedAt={viewing?.deleted_at} />
</AdminLayout>

{#snippet toolbar(inForm)}
    {#if !inForm}
        <div class="flex items-center gap-2">
            <SearchBar placeholder={$t('roles.search')} value={list.search} onsearch={(v) => list.setSearch(v)} />
            <button class="kt-btn kt-btn-sm kt-btn-ghost" onclick={() => (filtersOpen = true)} aria-label={$t('common.actions.filter')}>
                <i class="ki-filled ki-filter"></i>
            </button>
            <ExportButton rows={list.rows} columns={[
                { key: 'name', label: 'Name' },
                { key: 'is_default', label: 'Default' },
                { key: 'created_at', label: 'Created' },
            ]} filename="roles" />
        </div>
        {#if hasPermission('roles.store')}
            <button class="kt-btn kt-btn-sm kt-btn-primary" onclick={create}><i class="ki-filled ki-plus"></i>{$t('roles.add')}</button>
        {/if}
    {:else}
        <button class="kt-btn kt-btn-sm kt-btn-secondary" onclick={closeForm}><i class="ki-filled ki-black-left"></i>{$t('common.actions.cancel')}</button>
    {/if}
{/snippet}

{#snippet form()}
    <RoleForm role={editing} onsaved={saved} oncancel={closeForm} />
{/snippet}

{#snippet table()}
    <DataTable {columns} rows={list.rows} loading={list.loading} meta={list.meta} sort={list.params.sort} onSort={list.toggleSort} onPageChange={list.goToPage} onPerPageChange={list.setPerPage} onRowClick={view} {cells} {rowActions} />
{/snippet}

{#snippet cells(row, column)}
    {#if column.key === 'id'}
        <IdBadge id={row.id} onclick={() => view(row)} />
    {:else if column.key === 'is_default'}
        {#if row.is_default}<Badge variant="info">{$t('common.filters.yes')}</Badge>{:else}<span class="text-muted-foreground">—</span>{/if}
    {:else if column.key === 'permissions'}
        <Badge variant="secondary">{(row.permissions ?? []).length}</Badge>
    {:else if column.key === 'created_at'}
        <DateTime value={row.created_at} />
    {:else}
        {row[column.key] ?? '—'}
    {/if}
{/snippet}

{#snippet rowActions(row)}
    <Dropdown>
        {#snippet trigger()}
            <button class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" aria-label="Actions"><i class="ki-filled ki-dots-vertical"></i></button>
        {/snippet}
        <div class="kt-menu-item"><button class="kt-menu-link" data-dropdown-dismiss onclick={() => view(row)}><span class="kt-menu-icon"><i class="ki-filled ki-eye"></i></span><span class="kt-menu-title">{$t('common.actions.view')}</span></button></div>
        {#if hasPermission('roles.update')}
            <div class="kt-menu-item"><button class="kt-menu-link" data-dropdown-dismiss onclick={() => edit(row)}><span class="kt-menu-icon"><i class="ki-filled ki-pencil"></i></span><span class="kt-menu-title">{$t('common.actions.edit')}</span></button></div>
        {/if}
        {#if hasPermission('role-permissions.index')}
            <div class="kt-menu-item"><button class="kt-menu-link" data-dropdown-dismiss onclick={() => managePerms(row)}><span class="kt-menu-icon"><i class="ki-filled ki-key"></i></span><span class="kt-menu-title">{$t('roles.actions.permissions')}</span></button></div>
        {/if}
        {#if hasPermission('roles.destroy')}
            <div class="kt-menu-item"><button class="kt-menu-link text-destructive" data-dropdown-dismiss onclick={() => remove(row)}><span class="kt-menu-icon"><i class="ki-filled ki-trash"></i></span><span class="kt-menu-title">{$t('common.actions.delete')}</span></button></div>
        {/if}
    </Dropdown>
{/snippet}
