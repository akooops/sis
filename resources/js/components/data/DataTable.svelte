<script>
    /**
     * DataTable — the standard table for every index page. Pairs with useIndex.
     *
     *   <DataTable
     *       columns={[{ key:'email', label:'Email', sortable:true }, …]}
     *       rows={list.rows} loading={list.loading} meta={list.meta}
     *       sort={list.params.sort}
     *       onSort={list.toggleSort}
     *       onPageChange={list.goToPage} onPerPageChange={list.setPerPage}
     *       {cells} {rowActions} onRowClick={openView}
     *   />
     *   {#snippet cells(row, column)} … {/snippet}   // optional custom cells
     *   {#snippet rowActions(row)} <Dropdown/> {/snippet}
     *
     * Columns: { key, label, sortable?, align?, width?, headerClass?, cellClass? }.
     * Sorting toggles asc → desc → none and hits the query builder via onSort.
     */
    import Skeleton from '@/components/ui/Skeleton.svelte';
    import EmptyState from '@/components/ui/EmptyState.svelte';
    import ClampText from '@/components/ui/ClampText.svelte';
    import Pagination from './Pagination.svelte';

    let {
        columns = [],
        rows = [],
        loading = false,
        meta = null,
        sort = null,
        onSort,
        onPageChange,
        onPerPageChange,
        onRowClick,
        emptyTitle = null,
        emptyBody = null,
        emptyIcon = 'ki-filled ki-document',
        cells,
        rowActions,
    } = $props();

    const skeletonRows = $derived(meta?.per_page ?? 10);
    const colspan = $derived(columns.length + (rowActions ? 1 : 0));

    function direction(key) {
        if (sort === key) return 'asc';
        if (sort === `-${key}`) return 'desc';
        return null;
    }

    function alignClass(align) {
        return align === 'end' ? 'text-end' : align === 'center' ? 'text-center' : 'text-start';
    }

    // Tooltip = the raw value when it's a plain primitive (so a clipped cell can
    // still be read on hover; the full value is always in the view drawer).
    function cellTitle(row, column) {
        const v = row[column.key];
        return typeof v === 'string' || typeof v === 'number' ? String(v) : undefined;
    }
</script>

<div class="kt-scrollable-x-auto kt-card-table">
    <table class="kt-table kt-table-auto kt-table-border text-sm">
        <thead>
            <tr>
                {#each columns as column}
                    <th class={column.headerClass} style={column.width ? `width:${column.width}` : undefined}>
                        {#if column.sortable}
                            <button
                                type="button"
                                class="kt-table-col group inline-flex items-center gap-1.5 whitespace-nowrap"
                                onclick={() => onSort?.(column.key)}
                            >
                                <span>{column.label}</span>
                                {#if direction(column.key) === 'asc'}
                                    <i class="ki-filled ki-arrow-up text-2xs text-primary"></i>
                                {:else if direction(column.key) === 'desc'}
                                    <i class="ki-filled ki-arrow-down text-2xs text-primary"></i>
                                {:else}
                                    <i class="ki-filled ki-arrow-up-down text-2xs opacity-0 group-hover:opacity-50"></i>
                                {/if}
                            </button>
                        {:else}
                            <span class="kt-table-col whitespace-nowrap">{column.label}</span>
                        {/if}
                    </th>
                {/each}
                {#if rowActions}
                    <th class="w-[80px] text-center">
                        <span class="kt-table-col whitespace-nowrap">Actions</span>
                    </th>
                {/if}
            </tr>
        </thead>
        <tbody>
            {#if loading}
                {#each Array(skeletonRows) as _}
                    <tr>
                        {#each columns as _c}
                            <td class="p-3"><Skeleton /></td>
                        {/each}
                        {#if rowActions}<td class="p-3"><Skeleton class="size-8 rounded" /></td>{/if}
                    </tr>
                {/each}
            {:else if rows.length === 0}
                <tr>
                    <td colspan={colspan}>
                        <EmptyState
                            icon={emptyIcon}
                            title={emptyTitle ?? 'No results found'}
                            body={emptyBody ?? 'No records match your criteria.'}
                        />
                    </td>
                </tr>
            {:else}
                {#each rows as row (row.id)}
                    <tr class={onRowClick ? 'cursor-pointer hover:bg-muted' : 'hover:bg-muted'}>
                        {#each columns as column}
                            <td class="{alignClass(column.align)} {column.cellClass ?? ''}" onclick={() => onRowClick?.(row)}>
                                {#if column.truncate === false}
                                    <!-- Rich cell (badges/avatar/id): bound the width, wrap naturally. -->
                                    <div class="overflow-hidden" style="max-width:{column.maxWidth ?? '260px'}">
                                        {#if cells}{@render cells(row, column)}{:else}{row[column.key] ?? ''}{/if}
                                    </div>
                                {:else}
                                    <!-- Text cell: single-line, fade + primary … (non-toggling). -->
                                    <ClampText maxWidth={column.maxWidth ?? '260px'} title={cellTitle(row, column)}>
                                        {#snippet children()}{#if cells}{@render cells(row, column)}{:else}{row[column.key] ?? ''}{/if}{/snippet}
                                    </ClampText>
                                {/if}
                            </td>
                        {/each}
                        {#if rowActions}
                            <td class="text-center">{@render rowActions(row)}</td>
                        {/if}
                    </tr>
                {/each}
            {/if}
        </tbody>
    </table>
</div>

{#if meta && meta.total > 0}
    <Pagination {meta} {onPageChange} {onPerPageChange} />
{/if}
