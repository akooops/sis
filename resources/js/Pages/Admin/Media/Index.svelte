<script>
    /** Media library index — Metronic list card; browse + detach (free) attached media. */
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
    import { useIndex } from '@/lib/api/useIndex.svelte';
    import { hasPermission } from '@/lib/permissions';
    import { formatFileSize } from '@/lib/format';
    import { api } from '@/lib/api/client';
    import { toast } from '@/lib/toast';
    import { confirm } from '@/lib/confirm';
    import { t } from '@/lib/i18n';

    const list = useIndex('api.v1.admin.media.index', { perPage: 20, sort: '-created_at', pollMs: 0 });
    let filtersOpen = $state(false);
    let viewOpen = $state(false);
    let viewing = $state(null);
    const view = (m) => { viewing = m; viewOpen = true; };

    const viewFields = $derived(
        viewing
            ? [
                  { label: $t('media.fields.name'), value: viewing.name },
                  { label: $t('media.fields.type'), value: $t(`media.types.${viewing.type}`) },
                  { label: $t('media.fields.size'), value: formatFileSize(viewing.size) },
                  { label: $t('media.fields.status'), value: viewing.scan_status },
                  { label: $t('media.fields.attached'), value: viewing.attached ? $t('media.attached.yes') : $t('media.attached.no') },
              ]
            : [],
    );

    const columns = $derived([
        { key: 'id', label: $t('common.detail.id'), width: '90px', truncate: false },
        { key: 'name', label: $t('media.fields.name'), sortable: true, truncate: false },
        { key: 'type', label: $t('media.fields.type'), truncate: false },
        { key: 'size', label: $t('media.fields.size'), sortable: true },
        { key: 'status', label: $t('media.fields.status'), truncate: false },
        { key: 'attached', label: $t('media.fields.attached'), truncate: false },
    ]);
    const filterConfig = $derived([
        {
            key: 'type',
            type: 'select',
            label: $t('media.fields.type'),
            options: [
                { value: 'images', label: $t('media.types.images') },
                { value: 'documents', label: $t('media.types.documents') },
                { value: 'videos', label: $t('media.types.videos') },
                { value: 'audio', label: $t('media.types.audio') },
            ],
        },
    ]);

    async function detach(m) {
        if (!(await confirm({ variant: 'destructive' }))) return;
        try {
            await api.patch(route('api.v1.admin.media.detach', m.id));
            toast.success($t('common.feedback.updated'));
            list.refresh();
        } catch (e) { toast.error(e?.message ?? $t('common.feedback.error')); }
    }
</script>

<svelte:head><title>Novonordisk — {$t('media.title')}</title></svelte:head>

<AdminLayout breadcrumbs={[{ label: $t('media.title') }]}>
    <IndexCard showForm={false} {toolbar} {form} {table} />
    <Filters bind:open={filtersOpen} config={filterConfig} values={list.params.filter} onapply={(v) => list.setFilters(v)} />
    <DetailDrawer bind:open={viewOpen} title={$t('media.title')} id={viewing?.id} fields={viewFields} createdAt={viewing?.created_at} updatedAt={viewing?.updated_at} />
</AdminLayout>

{#snippet toolbar()}
    <div class="flex items-center gap-2">
        <SearchBar placeholder={$t('media.search')} value={list.search} onsearch={(v) => list.setSearch(v)} />
        <button class="kt-btn kt-btn-sm kt-btn-ghost" onclick={() => (filtersOpen = true)} aria-label={$t('common.actions.filter')}><i class="ki-filled ki-filter"></i></button>
        <ExportButton rows={list.rows} columns={[
            { key: 'name', label: 'Name' },
            { key: 'type', label: 'Type' },
            { key: 'size', label: 'Size' },
            { key: 'created_at', label: 'Uploaded' },
        ]} filename="media" />
    </div>
{/snippet}

{#snippet form()}{/snippet}

{#snippet table()}
    <DataTable {columns} rows={list.rows} loading={list.loading} meta={list.meta} sort={list.params.sort} onSort={list.toggleSort} onPageChange={list.goToPage} onPerPageChange={list.setPerPage} onRowClick={view} {cells} {rowActions} />
{/snippet}

{#snippet cells(row, column)}
    {#if column.key === 'id'}
        <IdBadge id={row.id} onclick={() => view(row)} />
    {:else if column.key === 'name'}
        <div class="flex items-center gap-3">
            <div class="flex size-9 items-center justify-center overflow-hidden rounded bg-muted">
                {#if row.type === 'images' && row.url}<img src={row.url} alt="" class="size-full object-cover" />{:else}<i class="ki-filled ki-file text-muted-foreground"></i>{/if}
            </div>
            <span class="truncate text-sm font-medium text-mono">{row.name}</span>
        </div>
    {:else if column.key === 'type'}
        <Badge variant="secondary">{$t(`media.types.${row.type}`)}</Badge>
    {:else if column.key === 'size'}
        {formatFileSize(row.size)}
    {:else if column.key === 'status'}
        <Badge variant={row.scan_status === 'clean' ? 'success' : row.scan_status === 'infected' ? 'destructive' : 'warning'}>{row.scan_status}</Badge>
    {:else if column.key === 'attached'}
        <Badge variant={row.attached ? 'info' : 'secondary'}>{row.attached ? $t('media.attached.yes') : $t('media.attached.no')}</Badge>
    {:else if column.key === 'created_at'}
        <DateTime value={row.created_at} />
    {:else}
        {row[column.key] ?? '—'}
    {/if}
{/snippet}

{#snippet rowActions(row)}
    {#if row.attached && hasPermission('media.detach')}
        <Dropdown>
            {#snippet trigger()}
                <button class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" aria-label="Actions"><i class="ki-filled ki-dots-vertical"></i></button>
            {/snippet}
            <div class="kt-menu-item"><button class="kt-menu-link text-destructive" data-dropdown-dismiss onclick={() => detach(row)}><span class="kt-menu-icon"><i class="ki-filled ki-cross-circle"></i></span><span class="kt-menu-title">{$t('media.actions.detach')}</span></button></div>
        </Dropdown>
    {/if}
{/snippet}
