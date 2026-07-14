<script>
    /** Users index — Metronic list card with fly-in create/edit, live table, drawers. */
    import AdminLayout from '@/layouts/AdminLayout.svelte';
    import IndexCard from '@/components/data/IndexCard.svelte';
    import DataTable from '@/components/data/DataTable.svelte';
    import SearchBar from '@/components/data/SearchBar.svelte';
    import Filters from '@/components/data/Filters.svelte';
    import ExportButton from '@/components/data/ExportButton.svelte';
    import Avatar from '@/components/ui/Avatar.svelte';
    import Badge from '@/components/ui/Badge.svelte';
    import DateTime from '@/components/ui/DateTime.svelte';
    import Dropdown from '@/components/ui/Dropdown.svelte';
    import IdBadge from '@/components/data/IdBadge.svelte';
    import UserForm from './UserForm.svelte';
    import UserViewDrawer from './UserViewDrawer.svelte';
    import RolesDrawer from './RolesDrawer.svelte';
    import { useIndex } from '@/lib/api/useIndex.svelte';
    import { hasPermission } from '@/lib/permissions';
    import { api } from '@/lib/api/client';
    import { toast } from '@/lib/toast';
    import { confirm } from '@/lib/confirm';
    import { t } from '@/lib/i18n';

    const list = useIndex('api.v1.admin.users.index', { perPage: 15, include: 'roles', sort: '-created_at', pollMs: 20000 });

    let showForm = $state(false);
    let editing = $state(null);
    let filtersOpen = $state(false);
    let viewOpen = $state(false);
    let viewing = $state(null);
    let rolesOpen = $state(false);
    let rolesUser = $state(null);

    // $derived so labels re-translate when the locale switches (the page
    // re-renders in place — a plain const would keep stale strings).
    const columns = $derived([
        { key: 'id', label: $t('common.detail.id'), width: '90px', truncate: false },
        { key: 'firstname', label: $t('users.fields.name'), sortable: true, truncate: false },
        { key: 'email', label: $t('users.fields.email'), sortable: true },
        { key: 'roles', label: $t('users.fields.roles'), truncate: false },
        { key: 'status', label: $t('users.fields.status'), truncate: false },
    ]);

    const filterConfig = $derived([
        { key: 'email', type: 'text', label: $t('users.fields.email') },
        {
            key: 'trashed',
            type: 'select',
            label: $t('common.filters.trashed'),
            options: [
                { value: 'with', label: $t('common.filters.include_deleted') },
                { value: 'only', label: $t('common.filters.only_deleted') },
            ],
        },
    ]);

    const create = () => { editing = null; showForm = true; };
    const edit = (u) => { editing = u; showForm = true; };
    const closeForm = () => { showForm = false; editing = null; };
    const saved = () => { closeForm(); list.refresh(); };
    const view = (u) => { viewing = u; viewOpen = true; };
    const manageRoles = (u) => { rolesUser = u; rolesOpen = true; };

    async function setVerified(u, verified) {
        try {
            await api.post(route(verified ? 'api.v1.admin.users.verify' : 'api.v1.admin.users.unverify', u.id));
            toast.success($t('common.feedback.updated'));
            list.refresh();
        } catch (e) {
            toast.error(e?.message ?? $t('common.feedback.error'));
        }
    }

    async function remove(u) {
        if (!(await confirm({ body: $t('common.confirm.delete_body'), variant: 'destructive' }))) return;
        try {
            await api.delete(route('api.v1.admin.users.destroy', u.id));
            toast.success($t('common.feedback.deleted'));
            list.refresh();
        } catch (e) {
            toast.error(e?.message ?? $t('common.feedback.error'));
        }
    }
</script>

<svelte:head><title>Novonordisk — {$t('users.title')}</title></svelte:head>

<AdminLayout breadcrumbs={[{ label: $t('users.title') }]}>
    <IndexCard {showForm} {toolbar} {form} {table} />

    <Filters bind:open={filtersOpen} config={filterConfig} values={list.params.filter} onapply={(v) => list.setFilters(v)} />
    <UserViewDrawer bind:open={viewOpen} user={viewing} />
    <RolesDrawer bind:open={rolesOpen} user={rolesUser} />
</AdminLayout>

{#snippet toolbar(inForm)}
    {#if !inForm}
        <div class="flex items-center gap-2">
            <SearchBar placeholder={$t('users.search')} value={list.search} onsearch={(v) => list.setSearch(v)} />
            <button class="kt-btn kt-btn-sm kt-btn-ghost" onclick={() => (filtersOpen = true)} aria-label={$t('common.actions.filter')}>
                <i class="ki-filled ki-filter"></i>
            </button>
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
                <i class="ki-filled ki-plus"></i>{$t('users.add')}
            </button>
        {/if}
    {:else}
        <button class="kt-btn kt-btn-sm kt-btn-secondary" onclick={closeForm}>
            <i class="ki-filled ki-black-left"></i>{$t('common.actions.cancel')}
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
        <Badge variant={row.verified_at ? 'success' : 'warning'}>
            {row.verified_at ? $t('users.status.verified') : $t('users.status.pending')}
        </Badge>
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
        <div class="kt-menu-item">
            <button class="kt-menu-link" data-dropdown-dismiss onclick={() => view(row)}>
                <span class="kt-menu-icon"><i class="ki-filled ki-eye"></i></span><span class="kt-menu-title">{$t('common.actions.view')}</span>
            </button>
        </div>
        {#if hasPermission('users.update')}
            <div class="kt-menu-item">
                <button class="kt-menu-link" data-dropdown-dismiss onclick={() => edit(row)}>
                    <span class="kt-menu-icon"><i class="ki-filled ki-pencil"></i></span><span class="kt-menu-title">{$t('common.actions.edit')}</span>
                </button>
            </div>
        {/if}
        {#if hasPermission('user-roles.index')}
            <div class="kt-menu-item">
                <button class="kt-menu-link" data-dropdown-dismiss onclick={() => manageRoles(row)}>
                    <span class="kt-menu-icon"><i class="ki-filled ki-shield-tick"></i></span><span class="kt-menu-title">{$t('users.actions.roles')}</span>
                </button>
            </div>
        {/if}
        {#if hasPermission('users.verify')}
            <div class="kt-menu-item">
                <button class="kt-menu-link" data-dropdown-dismiss onclick={() => setVerified(row, !row.verified_at)}>
                    <span class="kt-menu-icon"><i class="ki-filled ki-check-circle"></i></span>
                    <span class="kt-menu-title">{row.verified_at ? $t('users.actions.unverify') : $t('users.actions.verify')}</span>
                </button>
            </div>
        {/if}
        {#if hasPermission('users.destroy')}
            <div class="kt-menu-item">
                <button class="kt-menu-link text-destructive" data-dropdown-dismiss onclick={() => remove(row)}>
                    <span class="kt-menu-icon"><i class="ki-filled ki-trash"></i></span><span class="kt-menu-title">{$t('common.actions.delete')}</span>
                </button>
            </div>
        {/if}
    </Dropdown>
{/snippet}
