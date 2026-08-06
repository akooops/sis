<script>
    /**
     * PivotDrawer — the standard "records related to this one" drawer: a DataTable
     * (same columns/cells/rowActions contract as every module index), a search,
     * a way to add, and a fly-in create/edit form when the pivot carries data.
     * One component behind Users→Roles, Roles→Permissions, ApiKeys→Permissions,
     * Users→Sessions, and any future rich pivot — so the view is identical across
     * models and only the config differs.
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
     *   />
     *
     * The list is paginated and searchable like any index — a pivot is a real
     * endpoint, not a lookup, so a role with 200 permissions never arrives in one
     * unscrollable slab.
     *
     * The parent reaches the list one of two ways: `parentId` for a nested route
     * (…/sessions/{user}), or `parentFilter` for a flat index scoped by a filter
     * (…/integrations?filter[integration_type_id]=…). Give whichever the endpoint
     * expects. A flat resource has nowhere to put the parent on a POST either, so
     * pass it in `storePayload` ({ form_id }) — a pivot edited from both ends has
     * no owner to nest under.
     *
     * COLUMNS are the caller's — a pivot is not always name + code. Pass the exact
     * DataTable `columns` you want (a permission's name/code, or a supplier_product's
     * price and quantity). Rendering: `cells` overrides it; without one, a cell reads
     * `row[relation]?.[key]` then falls back to `row[key]`, so both the related
     * record's own fields and the pivot's columns resolve with no snippet.
     *
     * THREE SHAPES, chosen by props:
     *
     * 0. `addable={false}` — nothing can be attached (a user's sessions are made by
     *    signing in). No select, no form: a searchable table with a revoke per row.
     *
     * 1. No `form` — the link is only two foreign keys (api_key_permissions). A
     *    bulk multi-select attaches several at once; each row has a remove.
     *
     * 2. `form` snippet — the link IS a record with its own data. The bulk select
     *    gives way to an Add button, every row gains an edit action, and the form
     *    flies in over the table exactly like a module index's create/edit card:
     *
     *      {#snippet form({ parentId, row, close, saved })}
     *          <SupplierProductForm {parentId} pivot={row} oncancel={close} onsaved={saved} />
     *      {/snippet}
     *
     *    `row` is the pivot being edited, or null when adding — same shape as an
     *    index create/edit form, so it decides POST vs PUT itself. `close` returns
     *    to the table; `saved` returns AND refetches.
     */
    import Drawer from '@/components/ui/Drawer.svelte';
    import Field from '@/components/form/Field.svelte';
    import Select from '@/components/form/Select.svelte';
    import Button from '@/components/ui/Button.svelte';
    import SearchBar from '@/components/data/SearchBar.svelte';
    import DataTable from '@/components/data/DataTable.svelte';
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
        storeRoute = null, // only the bulk-assign path uses this; a form owns its own submit
        destroyRoute,
        parentFilter = null, // flat index scoped by a filter instead of a route param
        width = 'w-[450px]', // Drawer's default; widen it for a form-shaped child
        resource,
        resourceLabelKey = 'name',
        resourceParams = {}, // extra query params for the assign Select (e.g. exclude what is already linked)
        resourceOption, // optional per-row rendering for the assign Select's dropdown
        payloadKey,
        // Extra body fields for the bulk assign. A flat store route cannot carry
        // its parent in the URL, so it travels in the payload: { form_id }.
        storePayload = null,
        relation,
        columns, // DataTable columns — required; a pivot is not always name/code
        addable = true,
        assignLabel = 'Assign',
        addLabel = 'Add',
        confirmBody = null,
        emptyTitle = null,
        emptyBody = null,
        searchPlaceholder = 'Search…',
        perPage = 10,
        cells, // optional custom cell snippet, same as DataTable
        rowActions, // optional custom per-row actions snippet
        banner, // optional snippet above the add row — a caveat the list cannot show
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
        perPage,
        include: relation,
        sort: '-created_at',
        readUrl: false,
        immediate: false,
        pollMs: 0,
        routeParams: parentFilter ? undefined : () => parentId,
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
            list.apply({ filter: { ...(parentFilter ?? {}) } });
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
            // A flat resource (parentFilter) has no room for the parent in its
            // URL — passing one would only append it as a stray query param, so
            // it goes in the body via storePayload instead.
            const url = parentFilter ? route(storeRoute) : route(storeRoute, parentId);
            await api.post(url, { ...(storePayload ?? {}), [payloadKey]: selected });
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
        // confirmBody may be a function of the row: revoking the session you are
        // sitting at logs you out, and that is worth saying out loud.
        const body = typeof confirmBody === 'function' ? confirmBody(pivot) : confirmBody;

        if (!(await confirm({ body, variant: 'destructive' }))) return;
        try {
            await api.delete(route(destroyRoute, pivot.id));
            toast.success('Deleted successfully.');
            await list.refresh();
        } catch (e) {
            toast.error(e?.message ?? 'Something went wrong. Please try again.');
        }
    }
</script>

<Drawer bind:open {title} {width}>
    {#if showForm && form}
        <div class="flex flex-col gap-5" in:fly={{ x: '100%', duration: 750 }}>
            {@render form({ parentId, row: editing, close: closeForm, saved })}
        </div>
    {:else}
        <div class="flex flex-col gap-4" in:fly={{ x: '-100%', duration: 750 }}>
            <!-- A caveat about the whole list: it belongs above the control that
                 adds to it, not under the table where it would be scrolled past. -->
            {@render banner?.()}

            <!-- add -->
            {#if !addable}
                <!-- Nothing to add: the table is the whole drawer. -->
            {:else if form}
                <!-- A custom form owns the add flow; the bulk select would be a
                     second, weaker way to do the same thing. -->
                <div class="flex justify-end">
                    <Button variant="primary" onclick={add}>
                        <i class="ki-filled ki-plus"></i>{addLabel}
                    </Button>
                </div>
            {:else}
                <Field label={assignLabel}>
                    <div class="flex min-w-0 items-end gap-2">
                        <!-- min-w-0: without it this column cannot shrink below the
                             Select's content, so one long option widens the drawer. -->
                        <div class="min-w-0 grow">
                            <Select {resource} {resourceParams} labelKey={resourceLabelKey} valueKey="id" multiple bind:value={selected} option={resourceOption} />
                        </div>
                        <Button variant="primary" onclick={assign} loading={saving} disabled={!selected.length}>{addLabel}</Button>
                    </div>
                </Field>
            {/if}

            <!-- search -->
            <SearchBar placeholder={searchPlaceholder} value={list.search} onsearch={(v) => list.setSearch(v)} />

            <!-- table -->
            <DataTable
                {columns}
                rows={list.rows}
                loading={list.loading}
                meta={list.meta}
                sort={list.sort}
                onSort={list.toggleSort}
                onPageChange={(p) => list.goToPage(p)}
                onPerPageChange={(n) => list.setPerPage(n)}
                emptyTitle={emptyTitle}
                emptyBody={emptyBody}
                cells={cells ?? defaultCells}
                rowActions={rowActions ?? defaultRowActions}
            />
        </div>
    {/if}
</Drawer>

<!-- A cell reads the related record first, then the pivot's own column — so the
     default Name/Code and a caller's custom columns both resolve. -->
{#snippet defaultCells(row, column)}
    {row[relation]?.[column.key] ?? row[column.key] ?? ''}
{/snippet}

{#snippet defaultRowActions(row)}
    <div class="inline-flex items-center">
        {#if form && addable}
            <!-- Only a pivot with data of its own has anything to edit. -->
            <button class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" onclick={() => edit(row)} aria-label="Edit" title="Edit">
                <i class="ki-filled ki-pencil"></i>
            </button>
        {/if}
        <button class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost text-destructive" onclick={() => detach(row)} aria-label="Remove" title="Remove">
            <i class="ki-filled ki-trash"></i>
        </button>
    </div>
{/snippet}
