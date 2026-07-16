<script>
    /**
     * PivotDrawer — "what is attached to this record", plus the attach/detach
     * controls. One component behind Users→Roles, Roles→Permissions and
     * ApiKeys→Permissions, which are the same screen with different nouns.
     *
     *   <PivotDrawer
     *       bind:open={permsOpen}
     *       title="Permissions for {role?.name}"
     *       parentId={role?.id}
     *       indexRoute="api.v1.admin.role-permissions.index"
     *       storeRoute="api.v1.admin.role-permissions.store"
     *       destroyRoute="api.v1.admin.role-permissions.destroy"
     *       resource="api.v1.admin.permissions.index"
     *       payloadKey="permissions"
     *       relation="permission"
     *       assignLabel="Assign permissions"
     *       currentLabel="Assigned permissions"
     *       emptyLabel="No permissions assigned yet."
     *       searchPlaceholder="Search permissions…"
     *       {item}
     *   />
     *
     * The assigned list is paginated and searchable like any other index — a
     * pivot is a real endpoint, not a lookup, and a role with 200 permissions
     * must not arrive in one unscrollable slab.
     *
     * TWO SHAPES OF PIVOT, and the `form` snippet is the switch between them.
     *
     * 1. The link carries no data of its own — api_key_permissions is only its
     *    two foreign keys. There is nothing to fill in and nothing to edit, so
     *    the interaction is a bulk multi-select that attaches several at once,
     *    a paginated table, and a remove button. This is the default: pass no
     *    `form` and that is what you get.
     *
     * 2. The link IS a record — a supplier_product with its own sales data. You
     *    create them one at a time, and you edit them afterwards. Pass a `form`
     *    snippet: the bulk select gives way to an Add button, every row grows an
     *    edit action, and the form flies in over the list the way a module index
     *    swaps in its create/edit card (see IndexCard).
     *
     *   {#snippet form({ parentId, row, close, saved })}
     *       <SupplierProductForm {parentId} pivot={row} oncancel={close} onsaved={saved} />
     *   {/snippet}
     *
     * `row` is the pivot being edited, or null when adding — the same shape as a
     * module index's create/edit form, so the form decides POST vs PUT the way
     * it always has. `close` returns to the list; `saved` returns AND refetches.
     * The form owns its own submit: this component never guesses at columns it
     * cannot know about.
     */
    import Drawer from '@/components/ui/Drawer.svelte';
    import Field from '@/components/form/Field.svelte';
    import Select from '@/components/form/Select.svelte';
    import Button from '@/components/ui/Button.svelte';
    import Spinner from '@/components/ui/Spinner.svelte';
    import SearchBar from '@/components/data/SearchBar.svelte';
    import Pagination from '@/components/data/Pagination.svelte';
    import { useIndex } from '@/lib/api/useIndex.svelte';
    import { api } from '@/lib/api/client';
    import { toast } from '@/lib/toast';
    import { confirm } from '@/lib/confirm';
    import { untrack } from 'svelte';
    import { fly } from 'svelte/transition';

    let {
        open = $bindable(false),
        title = '',
        parentId = null,
        indexRoute,
        storeRoute,
        destroyRoute,
        resource,
        resourceLabelKey = 'name',
        payloadKey,
        relation,
        assignLabel = 'Assign',
        addLabel = 'Add',
        currentLabel = 'Assigned',
        emptyLabel = 'Nothing assigned yet.',
        searchPlaceholder = 'Search…',
        item,
        form,
    } = $props();

    let selected = $state([]);
    let saving = $state(false);
    let showForm = $state(false);
    // The pivot row the form is open for; null while adding a new one.
    let editing = $state(null);

    // routeParams is a function: this drawer is reused across rows, and a plain
    // value would pin every fetch to whichever parent it was first opened for.
    // readUrl:false so it can't fight the page's own query string;
    // immediate:false so a mounted-but-closed drawer doesn't fetch.
    const list = useIndex(indexRoute, {
        perPage: 10,
        include: relation,
        sort: '-created_at',
        readUrl: false,
        immediate: false,
        routeParams: () => parentId,
    });

    // Reopening for a different row must drop the previous row's search, page
    // and half-filled form, or they silently apply to this one.
    $effect(() => {
        if (!open || !parentId) return;
        parentId;
        untrack(() => {
            selected = [];
            showForm = false;
            editing = null;
            list.apply({ filter: {} });
        });
    });

    function add() {
        editing = null;
        showForm = true;
    }

    function edit(row) {
        editing = row;
        showForm = true;
    }

    function closeForm() {
        showForm = false;
        editing = null;
    }

    function saved() {
        closeForm();
        list.refresh();
    }

    async function assign() {
        if (!selected.length) return;
        saving = true;
        try {
            await api.post(route(storeRoute, parentId), { [payloadKey]: selected });
            toast.success('Updated successfully.');
            selected = [];
            await list.refresh();
        } catch (e) {
            toast.error(e?.message ?? 'Something went wrong. Please try again.');
        } finally {
            saving = false;
        }
    }

    async function detach(pivot) {
        if (!(await confirm({ variant: 'destructive' }))) return;
        try {
            await api.delete(route(destroyRoute, pivot.id));
            toast.success('Deleted successfully.');
            await list.refresh();
        } catch (e) {
            toast.error(e?.message ?? 'Something went wrong. Please try again.');
        }
    }
</script>

<Drawer bind:open {title}>
    {#if showForm && form}
        <div class="flex flex-col gap-5" in:fly={{ x: '100%', duration: 750 }}>
            {@render form({ parentId, row: editing, close: closeForm, saved })}
        </div>
    {:else}
        <div class="flex flex-col gap-5" in:fly={{ x: '-100%', duration: 750 }}>
            {#if form}
                <!-- A custom form owns the add flow; the bulk select would be a
                     second, weaker way to do the same thing. -->
                <div class="flex justify-end">
                    <Button variant="primary" onclick={add}>
                        <i class="ki-filled ki-plus"></i>{addLabel}
                    </Button>
                </div>
            {:else}
                <Field label={assignLabel}>
                    <div class="flex items-end gap-2">
                        <div class="grow">
                            <Select {resource} labelKey={resourceLabelKey} valueKey="id" multiple bind:value={selected} />
                        </div>
                        <Button variant="primary" onclick={assign} loading={saving} disabled={!selected.length}>{addLabel}</Button>
                    </div>
                </Field>
            {/if}

            <div class="flex flex-col gap-2">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-sm font-medium text-mono">{currentLabel}</span>
                    <SearchBar placeholder={searchPlaceholder} value={list.search} onsearch={(v) => list.setSearch(v)} />
                </div>

                {#if list.loading}
                    <div class="py-4 text-center"><Spinner /></div>
                {:else if list.rows.length === 0}
                    <p class="text-sm text-muted-foreground">
                        {list.search ? 'No results found.' : emptyLabel}
                    </p>
                {:else}
                    <div class="flex flex-col divide-y divide-border rounded-lg border border-border">
                        {#each list.rows as row (row.id)}
                            <div class="flex items-center justify-between gap-2 px-3 py-2">
                                <div class="min-w-0 grow">
                                    {@render item(row)}
                                </div>
                                <div class="flex shrink-0 items-center">
                                    {#if form}
                                        <!-- Only a pivot with data of its own has anything to edit. -->
                                        <button class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" onclick={() => edit(row)} aria-label="Edit">
                                            <i class="ki-filled ki-pencil"></i>
                                        </button>
                                    {/if}
                                    <button class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost text-destructive" onclick={() => detach(row)} aria-label="Remove">
                                        <i class="ki-filled ki-trash"></i>
                                    </button>
                                </div>
                            </div>
                        {/each}
                    </div>

                    {#if list.meta?.total > list.meta?.per_page}
                        <Pagination meta={list.meta} onPageChange={(p) => list.goToPage(p)} />
                    {/if}
                {/if}
            </div>
        </div>
    {/if}
</Drawer>
