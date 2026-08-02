<script>
    /**
     * Reorder drawer — drag the contact details into the order they appear in.
     *
     * A flat sortable list rather than a tree: contact details have no parent, so
     * every row is in one list and one numbering. Submits the whole list of ids,
     * because the server takes position-in-array as the order and leaves anything
     * omitted alone.
     *
     * Pagination: the drawer ignores the table's page/search/sort entirely and
     * loads its own copy sorted by `order`, walking every page until it has the
     * lot. It has to — position is global, so reordering the 15 rows that happen
     * to be on screen would write positions 0-14 over rows that already hold them
     * elsewhere in the list. The API caps per_page at 100, hence the loop; in
     * practice one round-trip covers it.
     *
     * The type catalogue comes down as a prop rather than being fetched again —
     * the page it opens from has already read the registry.
     */
    import Drawer from '@/components/ui/Drawer.svelte';
    import Button from '@/components/ui/Button.svelte';
    import Spinner from '@/components/ui/Spinner.svelte';
    import EmptyState from '@/components/ui/EmptyState.svelte';
    import { api } from '@/lib/api/client';
    import { toast } from '@/lib/toast';

    let { open = $bindable(false), types = [], onsaved } = $props();

    let items = $state([]);
    let loading = $state(false);
    let saving = $state(false);
    let dragFrom = $state(null);
    let loadToken = 0;

    const typeMap = $derived(new Map(types.map((t) => [t.code, t])));

    const typeIcon = (code) => typeMap.get(code)?.icon ?? null;
    const typeName = (code) => typeMap.get(code)?.name ?? code ?? '';

    /** Every contact detail, in stored order — however many pages that takes. */
    async function loadAll() {
        const url = route('api.v1.admin.contact-details.index');
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

    // Reload every time it opens, so it never shows a stale order. Token so a
    // close-and-reopen mid-fetch can't be clobbered by the older list.
    $effect(() => {
        if (!open) return;

        const my = ++loadToken;
        loading = true;
        loadAll()
            .then((rows) => {
                if (my === loadToken) items = rows;
            })
            .catch((e) => toast.error(e?.message ?? 'Could not load contact details.'))
            .finally(() => {
                if (my === loadToken) loading = false;
            });
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
            await api.post(route('api.v1.admin.contact-details.reorder'), { ids: items.map((i) => i.id) });
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

<Drawer bind:open title="Reorder contact details">
    <div class="flex flex-col gap-4">
        {#if loading}
            <div class="flex items-center justify-center gap-2 py-8 text-sm text-muted-foreground">
                <Spinner size="sm" /> Loading…
            </div>
        {:else if items.length === 0}
            <EmptyState icon="ki-filled ki-abstract-26" title="No contact details" body="Add a contact detail first." />
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
                        <span
                            class="flex size-8 shrink-0 items-center justify-center rounded border border-border text-muted-foreground"
                            title={typeName(item.type)}
                        >
                            {#if typeIcon(item.type)}
                                <i class="ki-filled {typeIcon(item.type)}"></i>
                            {:else}
                                <span class="text-2xs uppercase">{String(item.type ?? '').slice(0, 2)}</span>
                            {/if}
                        </span>
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
