<script>
    /**
     * Select — searchable combobox that replaces the jQuery Select2 (no jQuery).
     *
     * Two modes:
     *  - Static:  pass `options={[{ value, label }]}`.
     *  - Remote:  pass `resource` (a route name). It loads the FIRST page only,
     *    then the user narrows with search (debounced) — never paginates and
     *    never infinite-scrolls. A picker is for finding one thing, not browsing
     *    the whole table; if it's not on the page, type to find it.
     *
     *   <Select resource="api.v1.admin.permissions.index" bind:value labelKey="name" />
     *   <Select {options} bind:value multiple />
     *
     * The dropdown is portaled to <body> and positioned from the trigger, so it
     * is never clipped inside a Drawer/Modal.
     */
    import { untrack } from 'svelte';
    import { portal } from '@/lib/portal';
    import { api } from '@/lib/api/client';
    import Spinner from '@/components/ui/Spinner.svelte';

    let {
        value = $bindable(null), // single: scalar; multiple: array
        multiple = false,
        options = null, // static options
        resource = null, // route name for remote search
        resourceParams = {}, // extra query params (e.g. filters)
        labelKey = 'name',
        valueKey = 'id',
        perPage = 20,
        placeholder = null,
        clearable = true,
        disabled = false,
        invalid = false,
        initialOptions = [], // seed labels for preselected values (edit forms)
        // Optional per-row rendering for the dropdown list. Receives
        // { value, label, raw } — `raw` is the whole record in remote mode, so a
        // country picker can draw its flag. The chips/trigger keep the plain
        // label either way: a chip strip is not a place for images.
        option,
        onchange,
    } = $props();

    let openState = $state(false);
    let search = $state('');
    let remoteItems = $state([]);
    let loading = $state(false);
    let trigger;
    let menu;
    let pos = $state({ top: 0, left: 0, width: 0 });
    let debounceTimer;

    // Known value->label pairs (seeded, then augmented as options load/select).
    let known = $state(new Map(initialOptions.map((o) => [o.value, o.label])));

    const isRemote = $derived(!!resource);

    // initialOptions often arrive AFTER mount (an edit form fetching its record)
    // — merge them in whenever they change instead of reading them only once.
    $effect(() => {
        if (!initialOptions?.length) return;
        const next = new Map(untrack(() => known));
        let changed = false;
        for (const o of initialOptions) {
            if (next.get(o.value) !== o.label) {
                next.set(o.value, o.label);
                changed = true;
            }
        }
        if (changed) known = next;
    });

    // Selected values we can't label yet (remote mode) — resolved below.
    const unknownSelected = $derived(
        isRemote
            ? (multiple ? (Array.isArray(value) ? value : []) : value != null && value !== '' ? [value] : []).filter(
                  (v) => !known.has(v),
              )
            : [],
    );
    let resolving = $state(false);

    // A preselected value whose label was never loaded (edit form, deep link)
    // resolves itself: every index route supports filter[id], so fetch exactly
    // those records for their labels — opening the dropdown still browses
    // unfiltered. A miss keeps the raw value so this can never loop or retry.
    $effect(() => {
        if (!isRemote || unknownSelected.length === 0) return;
        const targets = [...unknownSelected];
        resolving = true;

        (async () => {
            const next = new Map(untrack(() => known));
            try {
                const { filter: extraFilter, ...extraParams } = resourceParams;
                const data = await api.get(route(resource), {
                    ...extraParams,
                    filter: { ...(extraFilter ?? {}), id: targets.join(',') },
                    per_page: targets.length,
                });
                for (const r of data?.data ?? []) next.set(r[valueKey], r[labelKey]);
            } catch {
                // fall through — misses keep their raw value below
            }
            for (const t of targets) {
                if (!next.has(t)) next.set(t, t);
            }
            known = next;
            resolving = false;
        })();
    });

    // Normalized options currently shown in the list.
    const listItems = $derived.by(() => {
        if (isRemote) {
            return remoteItems.map((r) => ({ value: r[valueKey], label: r[labelKey], raw: r }));
        }
        const q = search.trim().toLowerCase();
        return (options ?? []).filter((o) => !q || String(o.label).toLowerCase().includes(q));
    });

    const selectedValues = $derived(multiple ? (Array.isArray(value) ? value : []) : value != null && value !== '' ? [value] : []);

    function labelFor(val) {
        return known.get(val) ?? (options ?? []).find((o) => o.value === val)?.label ?? val;
    }

    /** Can we show a real label for this value yet (known map OR static options)? */
    function hasLabel(val) {
        return known.has(val) || (options ?? []).some((o) => o.value === val);
    }

    // Labels may be on their way: a remote resolve in flight, or a static
    // options array that hasn't been fetched by the parent yet. While pending,
    // an unlabellable selected value renders as a pulse instead of its raw id.
    const labelsPending = $derived(resolving || (!isRemote && !(options?.length)));

    function isSelected(val) {
        return selectedValues.includes(val);
    }

    // --- remote fetching: page 1 for the current search, and only that ---
    async function fetchOptions() {
        if (!isRemote) return;
        loading = true;
        try {
            // Spread the extras first so their `filter` can't clobber the merged
            // one — search must survive alongside caller-pinned filters.
            const { filter: extraFilter, ...extraParams } = resourceParams;
            const data = await api.get(route(resource), {
                ...extraParams,
                filter: { search: search || undefined, ...(extraFilter ?? {}) },
                per_page: perPage,
            });
            const rows = data?.data ?? [];
            remoteItems = rows;
            // Remember labels for anything we just loaded.
            const next = new Map(known);
            for (const r of rows) next.set(r[valueKey], r[labelKey]);
            known = next;
        } catch {
            // leave list as-is on error
        } finally {
            loading = false;
        }
    }

    function onSearchInput(event) {
        search = event.currentTarget.value;
        if (isRemote) {
            // Show the spinner NOW, through the debounce and the fetch, so the
            // load is visible even when the response is near-instant.
            loading = true;
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => fetchOptions(), 300);
        }
    }

    // --- open/close + positioning ---
    function place() {
        if (!trigger) return;
        const r = trigger.getBoundingClientRect();
        pos = { top: r.bottom + 4, left: r.left, width: r.width };
    }

    function open() {
        if (disabled) return;
        openState = true;
        place();
        if (isRemote && remoteItems.length === 0) fetchOptions();
    }

    function close() {
        openState = false;
        search = '';
    }

    function toggle() {
        openState ? close() : open();
    }

    function choose(item) {
        const next = new Map(known);
        next.set(item.value, item.label);
        known = next;

        if (multiple) {
            const arr = Array.isArray(value) ? value : [];
            value = arr.includes(item.value) ? arr.filter((v) => v !== item.value) : [...arr, item.value];
        } else {
            value = item.value;
            close();
        }
        onchange?.(value);
    }

    function clear(event) {
        event.stopPropagation();
        value = multiple ? [] : null;
        onchange?.(value);
    }

    function removeChip(val, event) {
        event.stopPropagation();
        value = (Array.isArray(value) ? value : []).filter((v) => v !== val);
        onchange?.(value);
    }

    function onWindow(event) {
        if (!openState) return;
        if (trigger?.contains(event.target) || menu?.contains(event.target)) return;
        close();
    }

    function onKeydown(event) {
        if (event.key === 'Escape') close();
    }

    const hasValue = $derived(multiple ? selectedValues.length > 0 : value != null && value !== '');
</script>

<svelte:window onclick={onWindow} onkeydown={openState ? onKeydown : undefined} onscroll={openState ? place : undefined} onresize={openState ? place : undefined} />

<div
    class="kt-input min-w-0 cursor-pointer {invalid ? 'border-destructive' : ''} {disabled ? 'opacity-60' : ''}"
    bind:this={trigger}
    onclick={toggle}
    role="button"
    tabindex="0"
    aria-haspopup="listbox"
    aria-expanded={openState}
>
    {#if multiple && selectedValues.length}
        <!-- Cap the chip strip: it fills the field's height, pads the top so a
             single chip still reads centered, wraps to a few rows, then scrolls
             on Y instead of growing the field down the page. -->
        <div class="flex h-full min-w-0 grow flex-wrap content-start items-center gap-1 max-h-[5.5rem] overflow-x-hidden overflow-y-auto pt-1">
            {#each selectedValues as val}
                <!-- max-w-full + a truncating label: one long option must not be
                     able to widen the field past the form it sits in. -->
                <span class="kt-badge kt-badge-sm kt-badge-outline kt-badge-primary max-w-full">
                    {#if labelsPending && !hasLabel(val)}
                        <span class="inline-block h-3 w-16 animate-pulse rounded bg-muted"></span>
                    {:else}
                        <span class="min-w-0 truncate" title={labelFor(val)}>{labelFor(val)}</span>
                    {/if}
                    <button type="button" class="ms-1 shrink-0" onclick={(e) => removeChip(val, e)} aria-label="Remove">
                        <i class="ki-filled ki-cross text-2xs"></i>
                    </button>
                </span>
            {/each}
        </div>
    {:else if !multiple && hasValue}
        {#if labelsPending && !hasLabel(value)}
            <span class="min-w-0 grow"><span class="block h-4 w-28 animate-pulse rounded bg-muted"></span></span>
        {:else}
            <span class="min-w-0 grow truncate text-mono" title={labelFor(value)}>{labelFor(value)}</span>
        {/if}
    {:else}
        <span class="min-w-0 grow truncate text-muted-foreground">{placeholder ?? 'Search'}</span>
    {/if}

    {#if clearable && hasValue}
        <button type="button" class="text-muted-foreground" onclick={clear} aria-label="Clear">
            <i class="ki-filled ki-cross"></i>
        </button>
    {/if}
    <i class="ki-filled ki-down text-muted-foreground"></i>
</div>

{#if openState}
    <div
        class="fixed z-100 rounded-lg border border-border bg-popover shadow-lg"
        style="top:{pos.top}px; left:{pos.left}px; width:{pos.width}px;"
        use:portal
        bind:this={menu}
        role="listbox"
    >
        <div class="border-b border-border p-2">
            <div class="kt-input kt-input-sm">
                <i class="ki-filled ki-magnifier text-muted-foreground"></i>
                <input type="text" value={search} oninput={onSearchInput} placeholder="Search" />
                {#if loading}<Spinner size="sm" class="text-muted-foreground" />{/if}
            </div>
        </div>

        <div class="max-h-[240px] overflow-y-auto p-1">
            {#if loading}
                <div class="flex items-center justify-center gap-2 px-3 py-4 text-sm text-muted-foreground">
                    <Spinner size="sm" /> Searching…
                </div>
            {:else}
            {#each listItems as item (item.value)}
                <button
                    type="button"
                    class="flex w-full items-center justify-between gap-2 rounded-md px-3 py-2 text-start text-sm hover:bg-muted {isSelected(item.value) ? 'bg-muted' : ''}"
                    onclick={() => choose(item)}
                    role="option"
                    aria-selected={isSelected(item.value)}
                >
                    {#if option}
                        {@render option(item)}
                    {:else}
                        <span class="min-w-0 truncate" title={item.label}>{item.label}</span>
                    {/if}
                    {#if isSelected(item.value)}<i class="ki-filled ki-check text-primary"></i>{/if}
                </button>
            {/each}

            {#if listItems.length === 0}
                <div class="px-3 py-2 text-sm text-muted-foreground">No results found</div>
            {/if}
            {/if}
        </div>
    </div>
{/if}
