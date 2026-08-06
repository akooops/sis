<script>
    /**
     * Reorder drawer — the one place a menu's tree is arranged.
     *
     * Two invariants hold in every function below. Depth is capped at 2: a
     * top-level item may have children, a child may not. And `order` is per sibling
     * group — position inside (menu, parent) IS the order. So the tree is edited as
     * a FLAT list in display order (each root followed by its children) and
     * submitted the same way, every row carrying the parent it ended up under.
     *
     * Scoped to one menu because each menu's groups start again at 0; the picker
     * starts on whatever the table is filtered to. It loads its own copy of the
     * items, walking every page — reordering only the rows on screen would write
     * positions over items further down. The API caps per_page at 100, hence the
     * loop; in practice one round-trip covers a menu.
     */
    import Drawer from '@/components/ui/Drawer.svelte';
    import Field from '@/components/form/Field.svelte';
    import Select from '@/components/form/Select.svelte';
    import Button from '@/components/ui/Button.svelte';
    import Spinner from '@/components/ui/Spinner.svelte';
    import EmptyState from '@/components/ui/EmptyState.svelte';
    import { api } from '@/lib/api/client';
    import { toast } from '@/lib/toast';

    let { open = $bindable(false), menuId = null, menu = null, onsaved } = $props();

    let selected = $state(null);
    let items = $state([]);
    let loading = $state(false);
    let saving = $state(false);
    let dragFrom = $state(null);

    // Plain, not $state: switching menu mid-fetch must not let the older response
    // land, and tracking this would re-run the effect that bumps it.
    let loadToken = 0;

    // Seed from the table's filter on a fresh open only — a plain flag rather than
    // $state, or the effect would re-run and clobber a menu picked in here.
    let wasOpen = false;
    $effect(() => {
        if (open && !wasOpen) selected = menuId;
        wasOpen = open;
    });

    /** Every item in the menu, in stored order — however many pages that takes. */
    async function loadAll(id) {
        const url = route('api.v1.admin.menu-items.index');
        const all = [];
        let page = 1;
        let lastPage = 1;

        do {
            const res = await api.get(url, { per_page: 100, sort: 'order', page, filter: { menu_id: id } });
            all.push(...(res?.data ?? []));
            lastPage = res?.meta?.last_page ?? 1;
            page += 1;
        } while (page <= lastPage);

        return all;
    }

    /**
     * Flat rows to display order. `order` is per group, so the API's flat sort
     * interleaves the two levels; regroup them here. Copies, because parent_id is
     * edited in place until Save. A child whose parent is missing is shown at the
     * end as a root rather than dropped.
     */
    function toTree(rows) {
        const roots = rows.filter((r) => !r.parent_id);
        const rootIds = new Set(roots.map((r) => r.id));
        const out = [];

        for (const root of roots) {
            out.push({ ...root });
            for (const row of rows) {
                if (row.parent_id === root.id) out.push({ ...row });
            }
        }

        for (const row of rows) {
            if (row.parent_id && !rootIds.has(row.parent_id)) out.push({ ...row, parent_id: null });
        }

        return out;
    }

    // Reload on every open and on every menu change, so it never shows a stale tree.
    $effect(() => {
        const mine = ++loadToken;
        items = [];

        if (!open || !selected) return;

        loading = true;
        loadAll(selected)
            .then((rows) => { if (mine === loadToken) items = toTree(rows); })
            .catch((e) => toast.error(e?.message ?? 'Could not load menu items.'))
            .finally(() => { if (mine === loadToken) loading = false; });
    });

    const isChild = (index) => items[index]?.parent_id != null;

    /** How many rows travel together: a root carries the children behind it. */
    function blockLength(index) {
        if (isChild(index)) return 1;
        let length = 1;
        while (isChild(index + length)) length += 1;

        return length;
    }

    /** Nearest top-level row above this one, or -1. */
    function precedingRoot(index) {
        for (let i = index - 1; i >= 0; i -= 1) {
            if (!isChild(i)) return i;
        }

        return -1;
    }

    /** The list IS the tree: re-home every child on the root above it. */
    function normalize(list) {
        let rootId = null;

        return list.map((row) => {
            // A child dragged above every root becomes top level itself.
            const parentId = row.parent_id == null ? null : rootId;
            if (parentId === null) rootId = row.id;

            return row.parent_id === parentId ? row : { ...row, parent_id: parentId };
        });
    }

    /**
     * Move the row at `from` — with its children when it is a root — to `to`.
     * Downwards it lands after the target's own block, upwards in front of it, and
     * a root always lands on a root boundary so it can never split someone else's
     * children.
     */
    function moveBlock(from, to) {
        const length = blockLength(from);
        if (to >= from && to < from + length) return;

        let target = to;
        if (!isChild(from)) {
            while (isChild(target)) target += 1;
        }

        const insertAt = target > from ? target + blockLength(target) : target;
        const next = [...items];
        const block = next.splice(from, length);
        next.splice(insertAt > from ? insertAt - length : insertAt, 0, ...block);
        items = normalize(next);
    }

    function drop(to) {
        const from = dragFrom;
        dragFrom = null;
        if (from === null) return;
        moveBlock(from, to);
    }

    /** Where up/down land — never over a boundary, so nesting stays put. */
    function stepTarget(index, delta) {
        const row = items[index];
        if (!row) return -1;

        if (row.parent_id == null) {
            const below = index + blockLength(index);

            return delta < 0 ? precedingRoot(index) : (below < items.length ? below : -1);
        }

        // A child steps over a sibling only; leaving the group is what outdent is for.
        return items[index + delta]?.parent_id === row.parent_id ? index + delta : -1;
    }

    function step(index, delta) {
        const to = stepTarget(index, delta);
        if (to >= 0) moveBlock(index, to);
    }

    /** The root this row would nest under, or -1 when it cannot be nested at all. */
    function indentTarget(index) {
        // Already a child, or a parent whose children would land at depth 3.
        if (isChild(index) || blockLength(index) > 1) return -1;

        return precedingRoot(index);
    }

    function indent(index) {
        const parent = indentTarget(index);
        if (parent < 0) return;

        // It already sits right after that root's children, so only the parent changes.
        const next = [...items];
        next[index] = { ...next[index], parent_id: next[parent].id };
        items = next;
    }

    function outdent(index) {
        if (!isChild(index)) return;

        const next = [...items];
        const [row] = next.splice(index, 1);

        // Directly after what is left of its former parent's subtree.
        let at = precedingRoot(index) + 1;
        while (next[at]?.parent_id != null) at += 1;
        next.splice(at, 0, { ...row, parent_id: null });
        items = next;
    }

    async function save() {
        saving = true;
        try {
            await api.post(route('api.v1.admin.menu-items.reorder'), {
                menu_id: selected,
                items: items.map((i) => ({ id: i.id, parent_id: i.parent_id })),
            });
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

<Drawer bind:open title="Reorder menu" width="w-[560px]">
    <div class="flex flex-col gap-4">
        <Field label="Menu" hint="Positions are numbered within a menu, and within each parent.">
            <Select
                resource="api.v1.admin.menus.index"
                bind:value={selected}
                labelKey="name"
                clearable={false}
                placeholder="Search menus…"
                initialOptions={menu ? [{ value: menu.id, label: menu.name }] : []}
            />
        </Field>

        {#if !selected}
            <EmptyState icon="ki-filled ki-burger-menu-2" title="Pick a menu" body="Items are ordered inside their own menu." />
        {:else if loading}
            <div class="flex items-center justify-center gap-2 py-8 text-sm text-muted-foreground">
                <Spinner size="sm" /> Loading…
            </div>
        {:else if items.length === 0}
            <EmptyState icon="ki-filled ki-burger-menu-2" title="No items" body="Add an item to this menu first." />
        {:else}
            <p class="text-xs text-muted-foreground">
                Drag a row, or use the arrows to move and nest it. Nesting is one level deep, so an item
                that already has children of its own cannot become a child. Nothing is saved until you
                press Save order.
            </p>

            <div class="flex flex-col gap-2">
                {#each items as item, index (item.id)}
                    <!-- svelte-ignore a11y_no_static_element_interactions -->
                    <div
                        class="flex cursor-move items-center gap-2 rounded-lg border border-border p-2 hover:border-primary {item.parent_id ? 'ms-8' : ''}"
                        draggable="true"
                        ondragstart={() => (dragFrom = index)}
                        ondragover={(e) => e.preventDefault()}
                        ondrop={() => drop(index)}
                        ondragend={() => (dragFrom = null)}
                    >
                        <i class="ki-filled ki-menu shrink-0 text-muted-foreground"></i>
                        {#if item.parent_id}
                            <i class="ki-filled ki-arrow-down-right shrink-0 text-xs text-muted-foreground"></i>
                        {/if}
                        <span class="min-w-0 grow truncate text-sm font-medium text-mono" title={item.name}>{item.name}</span>

                        <!-- Arrows as well as drag: a keyboard user has no other way in. -->
                        <div class="flex shrink-0 items-center gap-1">
                            <button
                                type="button"
                                class="kt-btn kt-btn-icon kt-btn-xs kt-btn-secondary"
                                disabled={stepTarget(index, -1) < 0}
                                onclick={() => step(index, -1)}
                                aria-label="Move up"
                            >
                                <i class="ki-filled ki-up"></i>
                            </button>
                            <button
                                type="button"
                                class="kt-btn kt-btn-icon kt-btn-xs kt-btn-secondary"
                                disabled={stepTarget(index, 1) < 0}
                                onclick={() => step(index, 1)}
                                aria-label="Move down"
                            >
                                <i class="ki-filled ki-down"></i>
                            </button>
                        </div>

                        <div class="flex shrink-0 items-center gap-1 border-s border-border ps-2">
                            <button
                                type="button"
                                class="kt-btn kt-btn-icon kt-btn-xs kt-btn-secondary"
                                disabled={!item.parent_id}
                                onclick={() => outdent(index)}
                                aria-label="Move to top level"
                            >
                                <i class="ki-filled ki-arrow-left"></i>
                            </button>
                            <button
                                type="button"
                                class="kt-btn kt-btn-icon kt-btn-xs kt-btn-secondary"
                                disabled={indentTarget(index) < 0}
                                onclick={() => indent(index)}
                                aria-label="Nest under the item above"
                            >
                                <i class="ki-filled ki-arrow-right"></i>
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
