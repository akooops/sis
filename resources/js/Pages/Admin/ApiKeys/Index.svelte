<script>
    /** API keys index — Metronic list card with fly-in create/edit, token modal, permissions drawer. */
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
    import ApiKeyForm from './ApiKeyForm.svelte';
    import ApiKeyPermissionsDrawer from './ApiKeyPermissionsDrawer.svelte';
    import TokenModal from './TokenModal.svelte';
    import { useIndex } from '@/lib/api/useIndex.svelte';
    import { hasPermission } from '@/lib/permissions';
    import { api } from '@/lib/api/client';
    import { toast } from '@/lib/toast';
    import { confirm } from '@/lib/confirm';
    import { t } from '@/lib/i18n';

    const list = useIndex('api.v1.admin.api-keys.index', { perPage: 15, sort: '-created_at', pollMs: 20000 });

    let showForm = $state(false);
    let editing = $state(null);
    let filtersOpen = $state(false);
    let permsOpen = $state(false);
    let permsKey = $state(null);
    let tokenOpen = $state(false);
    let token = $state('');
    let viewOpen = $state(false);
    let viewing = $state(null);

    const columns = $derived([
        { key: 'id', label: $t('common.detail.id'), width: '90px', truncate: false },
        { key: 'name', label: $t('api_keys.fields.name'), sortable: true },
        { key: 'prefix', label: $t('api_keys.fields.prefix') },
        { key: 'status', label: $t('api_keys.fields.status'), truncate: false },
        { key: 'last_used_at', label: $t('api_keys.fields.last_used_at'), sortable: true, truncate: false },
    ]);
    const filterConfig = $derived([{ key: 'prefix', type: 'text', label: $t('api_keys.fields.prefix') }]);

    const showToken = (v) => { if (v) { token = v; tokenOpen = true; } };
    const create = () => { editing = null; showForm = true; };
    const edit = (k) => { editing = k; showForm = true; };
    const closeForm = () => { showForm = false; editing = null; };
    const saved = (res) => { closeForm(); list.refresh(); showToken(res?.token); };
    const managePerms = (k) => { permsKey = k; permsOpen = true; };
    const view = (k) => { viewing = k; viewOpen = true; };

    const viewFields = $derived(
        viewing
            ? [
                  { label: $t('api_keys.fields.name'), value: viewing.name },
                  { label: $t('api_keys.fields.prefix'), value: viewing.prefix },
                  { label: $t('api_keys.fields.status'), value: viewing.is_active ? $t('api_keys.status.active') : $t('api_keys.status.inactive') },
                  { label: $t('api_keys.fields.last_used_at'), date: viewing.last_used_at },
              ]
            : [],
    );

    async function rotate(k) {
        if (!(await confirm({ title: $t('api_keys.actions.rotate') }))) return;
        try {
            const res = await api.post(route('api.v1.admin.api-keys.rotate', k.id));
            toast.success($t('common.feedback.updated'));
            list.refresh();
            showToken(res?.token);
        } catch (e) { toast.error(e?.message ?? $t('common.feedback.error')); }
    }
    async function revoke(k) {
        if (!(await confirm({ title: $t('api_keys.actions.revoke'), variant: 'destructive' }))) return;
        try {
            await api.post(route('api.v1.admin.api-keys.revoke', k.id));
            toast.success($t('common.feedback.updated'));
            list.refresh();
        } catch (e) { toast.error(e?.message ?? $t('common.feedback.error')); }
    }
    async function remove(k) {
        if (!(await confirm({ body: $t('common.confirm.delete_body'), variant: 'destructive' }))) return;
        try {
            await api.delete(route('api.v1.admin.api-keys.destroy', k.id));
            toast.success($t('common.feedback.deleted'));
            list.refresh();
        } catch (e) { toast.error(e?.message ?? $t('common.feedback.error')); }
    }
</script>

<svelte:head><title>Novonordisk — {$t('api_keys.title')}</title></svelte:head>

<AdminLayout breadcrumbs={[{ label: $t('api_keys.title') }]}>
    <IndexCard {showForm} {toolbar} {form} {table} />
    <Filters bind:open={filtersOpen} config={filterConfig} values={list.params.filter} onapply={(v) => list.setFilters(v)} />
    <ApiKeyPermissionsDrawer bind:open={permsOpen} apiKey={permsKey} />
    <TokenModal bind:open={tokenOpen} {token} />
    <DetailDrawer bind:open={viewOpen} title={$t('api_keys.title')} id={viewing?.id} fields={viewFields} createdAt={viewing?.created_at} updatedAt={viewing?.updated_at} deletedAt={viewing?.deleted_at} />
</AdminLayout>

{#snippet toolbar(inForm)}
    {#if !inForm}
        <div class="flex items-center gap-2">
            <SearchBar placeholder={$t('api_keys.search')} value={list.search} onsearch={(v) => list.setSearch(v)} />
            <button class="kt-btn kt-btn-sm kt-btn-ghost" onclick={() => (filtersOpen = true)} aria-label={$t('common.actions.filter')}><i class="ki-filled ki-filter"></i></button>
            <ExportButton rows={list.rows} columns={[
                { key: 'name', label: 'Name' },
                { key: 'prefix', label: 'Prefix' },
                { key: 'created_at', label: 'Created' },
            ]} filename="api-keys" />
        </div>
        {#if hasPermission('api-keys.store')}
            <button class="kt-btn kt-btn-sm kt-btn-primary" onclick={create}><i class="ki-filled ki-plus"></i>{$t('api_keys.add')}</button>
        {/if}
    {:else}
        <button class="kt-btn kt-btn-sm kt-btn-secondary" onclick={closeForm}><i class="ki-filled ki-black-left"></i>{$t('common.actions.cancel')}</button>
    {/if}
{/snippet}

{#snippet form()}
    <ApiKeyForm apiKey={editing} onsaved={saved} oncancel={closeForm} />
{/snippet}

{#snippet table()}
    <DataTable {columns} rows={list.rows} loading={list.loading} meta={list.meta} sort={list.params.sort} onSort={list.toggleSort} onPageChange={list.goToPage} onPerPageChange={list.setPerPage} onRowClick={view} {cells} {rowActions} />
{/snippet}

{#snippet cells(row, column)}
    {#if column.key === 'id'}
        <IdBadge id={row.id} onclick={() => view(row)} />
    {:else if column.key === 'prefix'}
        <span class="font-mono text-xs text-mono">{row.prefix}</span>
    {:else if column.key === 'status'}
        <Badge variant={row.is_active ? 'success' : 'secondary'}>{row.is_active ? $t('api_keys.status.active') : $t('api_keys.status.inactive')}</Badge>
    {:else if column.key === 'last_used_at'}
        {#if row.last_used_at}<DateTime value={row.last_used_at} />{:else}<span class="text-muted-foreground">—</span>{/if}
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
        {#if hasPermission('api-keys.update')}
            <div class="kt-menu-item"><button class="kt-menu-link" data-dropdown-dismiss onclick={() => edit(row)}><span class="kt-menu-icon"><i class="ki-filled ki-pencil"></i></span><span class="kt-menu-title">{$t('common.actions.edit')}</span></button></div>
        {/if}
        {#if hasPermission('api-key-permissions.index')}
            <div class="kt-menu-item"><button class="kt-menu-link" data-dropdown-dismiss onclick={() => managePerms(row)}><span class="kt-menu-icon"><i class="ki-filled ki-key"></i></span><span class="kt-menu-title">{$t('api_keys.actions.permissions')}</span></button></div>
        {/if}
        {#if hasPermission('api-keys.rotate')}
            <div class="kt-menu-item"><button class="kt-menu-link" data-dropdown-dismiss onclick={() => rotate(row)}><span class="kt-menu-icon"><i class="ki-filled ki-arrows-circle"></i></span><span class="kt-menu-title">{$t('api_keys.actions.rotate')}</span></button></div>
        {/if}
        {#if hasPermission('api-keys.revoke')}
            <div class="kt-menu-item"><button class="kt-menu-link" data-dropdown-dismiss onclick={() => revoke(row)}><span class="kt-menu-icon"><i class="ki-filled ki-shield-cross"></i></span><span class="kt-menu-title">{$t('api_keys.actions.revoke')}</span></button></div>
        {/if}
        {#if hasPermission('api-keys.destroy')}
            <div class="kt-menu-item"><button class="kt-menu-link text-destructive" data-dropdown-dismiss onclick={() => remove(row)}><span class="kt-menu-icon"><i class="ki-filled ki-trash"></i></span><span class="kt-menu-title">{$t('common.actions.delete')}</span></button></div>
        {/if}
    </Dropdown>
{/snippet}
