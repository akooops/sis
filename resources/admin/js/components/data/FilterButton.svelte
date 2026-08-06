<script>
    /**
     * FilterButton — the toolbar's filter toggle, lit up while filters are on.
     *
     * A filter drawer hides what it is doing the moment it closes, so an applied
     * filter is otherwise invisible: the table is short and nobody knows why.
     *
     *   <FilterButton count={list.activeFilters} onclick={() => (filtersOpen = true)} />
     */
    let { count = 0, onclick } = $props();
</script>

<button
    type="button"
    class="kt-btn kt-btn-sm {count ? 'kt-btn-primary' : 'kt-btn-ghost'} relative"
    {onclick}
    aria-label={count ? `Filter (${count} applied)` : 'Filter'}
>
    <i class="ki-filled ki-filter"></i>
    {#if count}
        <span
            class="absolute -top-1 -end-1 flex size-4 items-center justify-center rounded-full bg-destructive text-[10px] font-semibold leading-none text-destructive-foreground"
        >
            {count}
        </span>
    {/if}
</button>
