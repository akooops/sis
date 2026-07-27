<script>
    /**
     * Reorder drawer — drag the banners into the order they should appear in.
     *
     * Submits the whole list of ids, because the server takes position-in-array
     * as the order and leaves anything omitted alone.
     *
     * Pagination: the drawer ignores the table's page/search/sort entirely and
     * loads its own copy sorted by `order`, walking every page until it has the
     * lot. It has to — position is global, so reordering the 15 rows that happen
     * to be on screen would write positions 0-14 over banners that already hold
     * them elsewhere in the list. The API caps per_page at 100, hence the loop.
     */
    import Drawer from '@/components/ui/Drawer.svelte';
    import Button from '@/components/ui/Button.svelte';
    import Spinner from '@/components/ui/Spinner.svelte';
    import EmptyState from '@/components/ui/EmptyState.svelte';
    import { api } from '@/lib/api/client';
    import { toast } from '@/lib/toast';

    let { open = $bindable(false), onsaved } = $props();

    let items = $state([]);
    let loading = $state(false);
    let saving = $state(false);
    let dragFrom = $state(null);

    /** Every banner, in stored order — however many pages that takes. */
    async function loadAll() {
        const url = route('api.v1.admin.banners.index');
        const all = [];
        let page = 1;
        let lastPage = 1;

        do {
            const res = await api.get(url, { per_page: 100, sort: 'order', page });
            all.push(...(res?.data ?? []));
            lastPage = res?.meta?.last_page ?? 1;
            page += 1;
        } while (page <= lastPage);

        return all;
    }

    // Reload every time it opens, so it never shows a stale order.
    $effect(() => {
        if (!open) return;

        loading = true;
        loadAll()
            .then((rows) => { items = rows; })
            .catch((e) => toast.error(e?.message ?? 'Could not load banners.'))
            .finally(() => { loading = false; });
    });

    function drop(to) {
        const from = dragFrom;
        dragFrom = null;
        if (from === null || from === to) return;
        const next = [...items];
        const [moved] = next.splice(from, 1);
        next.splice(to, 0, moved);
        items = next;
    }

    function move(index, delta) {
        const to = index + delta;
        if (to < 0 || to >= items.length) return;
        const next = [...items];
        [next[index], next[to]] = [next[to], next[index]];
        items = next;
    }

    async function save() {
        saving = true;
        try {
            await api.post(route('api.v1.admin.banners.reorder'), { ids: items.map((i) => i.id) });
            toast.success('Order saved.');
            open = false;
            onsaved?.();
        } catch (e) {
            toast.error(e?.message ?? 'Something went wrong. Please try again.');
        } finally {
            saving = false;
        }
    }
</script>

<Drawer bind:open title="Reorder banners">
    <div class="flex flex-col gap-4">
        {#if loading}
            <div class="flex items-center justify-center gap-2 py-8 text-sm text-muted-foreground">
                <Spinner size="sm" /> Loading…
            </div>
        {:else if items.length === 0}
            <EmptyState icon="ki-filled ki-picture" title="No banners" body="Add a banner first." />
        {:else}
            <p class="text-xs text-muted-foreground">Drag a row, or use the arrows. Nothing is saved until you press Save order.</p>

            <div class="flex flex-col gap-2">
                {#each items as item, index (item.id)}
                    <!-- svelte-ignore a11y_no_static_element_interactions -->
                    <div
                        class="flex cursor-move items-center gap-3 rounded-lg border border-border p-2 hover:border-primary"
                        draggable="true"
                        ondragstart={() => (dragFrom = index)}
                        ondragover={(e) => e.preventDefault()}
                        ondrop={() => drop(index)}
                        ondragend={() => (dragFrom = null)}
                    >
                        <i class="ki-filled ki-menu shrink-0 text-muted-foreground"></i>
                        <span class="w-6 shrink-0 text-center text-xs text-muted-foreground">{index + 1}</span>
                        <img src={item.thumbnail_url} alt="" class="h-8 w-12 shrink-0 rounded object-cover" />
                        <span class="min-w-0 grow truncate text-sm font-medium text-mono" title={item.name}>{item.name}</span>

                        <!-- Arrows as well as drag: a keyboard user has no other way in. -->
                        <div class="flex shrink-0 items-center gap-1">
                            <button
                                type="button"
                                class="kt-btn kt-btn-icon kt-btn-xs kt-btn-secondary"
                                disabled={index === 0}
                                onclick={() => move(index, -1)}
                                aria-label="Move up"
                            >
                                <i class="ki-filled ki-up"></i>
                            </button>
                            <button
                                type="button"
                                class="kt-btn kt-btn-icon kt-btn-xs kt-btn-secondary"
                                disabled={index === items.length - 1}
                                onclick={() => move(index, 1)}
                                aria-label="Move down"
                            >
                                <i class="ki-filled ki-down"></i>
                            </button>
                        </div>
                    </div>
                {/each}
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-border pt-4">
                <Button variant="secondary" onclick={() => (open = false)}>Cancel</Button>
                <Button variant="primary" loading={saving} onclick={save}>Save order</Button>
            </div>
        {/if}
    </div>
</Drawer>
