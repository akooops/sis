<script>
    /**
     * Reorder drawer — drag one brand's asset groups into the order they appear in.
     *
     * Order is per brand: each numbers its groups from 0, so the drawer works on a
     * single brand at a time and reordering a mixed list would write colliding
     * positions. The picker is seeded from the page's active brand filter, so
     * drilling in from Brands lands ready to drag.
     *
     * Submits the whole list of ids, because the server takes position-in-array as
     * the order and leaves anything omitted alone. It loads its own copy sorted by
     * `order`, walking every page until it has the lot — reordering only the rows
     * that happen to be on screen would write positions the rest already hold. The
     * API caps per_page at 100, hence the loop; in practice one round-trip covers it.
     */
    import { untrack } from 'svelte';
    import Drawer from '@/components/ui/Drawer.svelte';
    import Field from '@/components/form/Field.svelte';
    import Select from '@/components/form/Select.svelte';
    import Button from '@/components/ui/Button.svelte';
    import Spinner from '@/components/ui/Spinner.svelte';
    import EmptyState from '@/components/ui/EmptyState.svelte';
    import { api } from '@/lib/api/client';
    import { toast } from '@/lib/toast';

    let { open = $bindable(false), brandId = null, onsaved } = $props();

    let selected = $state(null);
    let items = $state([]);
    let loading = $state(false);
    let saving = $state(false);
    let dragFrom = $state(null);
    let loadToken = 0;

    /** Every asset group of one brand, in stored order — however many pages that takes. */
    async function loadAll(id) {
        const url = route('api.v1.admin.brand-asset-groups.index');
        const all = [];
        let page = 1;
        let lastPage = 1;

        do {
            const res = await api.get(url, { per_page: 100, sort: 'order', page, filter: { brand_id: id } });
            all.push(...(res?.data ?? []));
            lastPage = res?.meta?.last_page ?? 1;
            page += 1;
        } while (page <= lastPage);

        return all;
    }

    async function load(id) {
        // Token so switching brand mid-fetch can't be clobbered by the older list.
        const my = ++loadToken;
        items = [];
        if (!id) return;

        loading = true;
        try {
            const rows = await loadAll(id);
            if (my === loadToken) items = rows;
        } catch (e) {
            toast.error(e?.message ?? 'Could not load asset groups.');
        } finally {
            if (my === loadToken) loading = false;
        }
    }

    // Reload every time it opens, so it never shows a stale order.
    $effect(() => {
        if (!open) return;
        const seed = untrack(() => brandId);
        selected = seed;
        load(seed);
    });

    function pick(id) {
        selected = id;
        load(id);
    }

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
            await api.post(route('api.v1.admin.brand-asset-groups.reorder'), { ids: items.map((i) => i.id) });
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

<Drawer bind:open title="Reorder asset groups">
    <div class="flex flex-col gap-4">
        <Field label="Brand" hint="Asset groups are ordered within their brand, one brand at a time.">
            <Select
                resource="api.v1.admin.brands.index"
                labelKey="name"
                placeholder="Search brands…"
                clearable={false}
                value={selected}
                onchange={pick}
            />
        </Field>

        {#if !selected}
            <EmptyState icon="ki-filled ki-folder" title="Pick a brand" body="Choose a brand to see the asset groups you can reorder." />
        {:else if loading}
            <div class="flex items-center justify-center gap-2 py-8 text-sm text-muted-foreground">
                <Spinner size="sm" /> Loading…
            </div>
        {:else if items.length === 0}
            <EmptyState icon="ki-filled ki-abstract-26" title="No asset groups" body="This brand has no asset groups yet." />
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
