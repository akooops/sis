<script>
    /**
     * Pagination — driven by the Laravel paginator `meta`
     * ({ current_page, last_page, per_page, from, to, total }). Emits page and
     * per-page changes via callbacks.
     */

    let {
        meta = null,
        perPageOptions = [10, 15, 25, 50, 100],
        onPageChange,
        onPerPageChange,
    } = $props();

    const current = $derived(meta?.current_page ?? 1);
    const last = $derived(meta?.last_page ?? 1);

    // Compact page window around the current page.
    const pages = $derived.by(() => {
        const out = [];
        const start = Math.max(1, current - 2);
        const end = Math.min(last, current + 2);
        for (let i = start; i <= end; i++) out.push(i);
        return out;
    });

    function go(page) {
        if (page >= 1 && page <= last && page !== current) onPageChange?.(page);
    }
</script>

{#if meta}
    <div class="flex flex-col items-center justify-between gap-3 border-t border-border px-4 py-3 sm:flex-row">
        <div class="flex items-center gap-2 text-sm text-secondary-foreground">
            <span>Showing {meta.from ?? 0}–{meta.to ?? 0} of {meta.total ?? 0}</span>
            <select
                class="kt-select kt-select-sm w-auto"
                value={meta.per_page}
                onchange={(e) => onPerPageChange?.(Number(e.currentTarget.value))}
            >
                {#each perPageOptions as opt}
                    <option value={opt}>{opt}</option>
                {/each}
            </select>
            <span>per page</span>
        </div>

        <div class="flex items-center gap-1">
            <button class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" disabled={current <= 1} onclick={() => go(current - 1)} aria-label="Previous">
                <i class="ki-filled ki-black-left rtl:rotate-180"></i>
            </button>
            {#each pages as p}
                <button
                    class="kt-btn kt-btn-sm kt-btn-icon {p === current ? 'kt-btn-primary' : 'kt-btn-ghost'}"
                    onclick={() => go(p)}
                >
                    {p}
                </button>
            {/each}
            <button class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" disabled={current >= last} onclick={() => go(current + 1)} aria-label="Next">
                <i class="ki-filled ki-black-right rtl:rotate-180"></i>
            </button>
        </div>
    </div>
{/if}
