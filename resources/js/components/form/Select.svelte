<script>
    /**
     * Select — searchable combobox that replaces the jQuery Select2 (no jQuery).
     *
     * Two modes:
     *  - Static:  pass `options={[{ value, label }]}`.
     *  - Remote:  pass `resource` (a route name). It fetches the paginated JSON
     *    API with `filter[search]` + `per_page`/`page`, debounced, and loads more
     *    on scroll — the old Select2-AJAX behaviour, on the new envelope.
     *
     *   <Select resource="api.v1.admin.permissions.index" bind:value labelKey="name" />
     *   <Select {options} bind:value multiple />
     *
     * The dropdown is portaled to <body> and positioned from the trigger, so it
     * is never clipped inside a Drawer/Modal.
     */
    import { portal } from '@/lib/portal';
    import { api } from '@/lib/api/client';
    import { t } from '@/lib/i18n';

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
        onchange,
    } = $props();

    let openState = $state(false);
    let search = $state('');
    let remoteItems = $state([]);
    let loading = $state(false);
    let page = $state(1);
    let lastPage = $state(1);
    let trigger;
    let menu;
    let pos = $state({ top: 0, left: 0, width: 0 });
    let debounceTimer;

    // Known value->label pairs (seeded, then augmented as options load/select).
    let known = $state(new Map(initialOptions.map((o) => [o.value, o.label])));

    const isRemote = $derived(!!resource);

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
        return known.get(val) ?? val;
    }

    function isSelected(val) {
        return selectedValues.includes(val);
    }

    // --- remote fetching ---
    async function fetchPage(reset = false) {
        if (!isRemote) return;
        if (reset) {
            page = 1;
            remoteItems = [];
        }
        loading = true;
        try {
            const data = await api.get(route(resource), {
                filter: { search: search || undefined, ...(resourceParams.filter ?? {}) },
                ...resourceParams,
                per_page: perPage,
                page,
            });
            const rows = data?.data ?? [];
            remoteItems = reset ? rows : [...remoteItems, ...rows];
            lastPage = data?.meta?.last_page ?? 1;
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
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => fetchPage(true), 300);
        }
    }

    function onMenuScroll(event) {
        if (!isRemote || loading || page >= lastPage) return;
        const el = event.currentTarget;
        if (el.scrollTop + el.clientHeight >= el.scrollHeight - 40) {
            page += 1;
            fetchPage(false);
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
        if (isRemote && remoteItems.length === 0) fetchPage(true);
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
    class="kt-input cursor-pointer {invalid ? 'border-destructive' : ''} {disabled ? 'opacity-60' : ''}"
    bind:this={trigger}
    onclick={toggle}
    role="button"
    tabindex="0"
    aria-haspopup="listbox"
    aria-expanded={openState}
>
    {#if multiple && selectedValues.length}
        <div class="flex flex-wrap items-center gap-1">
            {#each selectedValues as val}
                <span class="kt-badge kt-badge-sm kt-badge-outline kt-badge-primary">
                    {labelFor(val)}
                    <button type="button" class="ms-1" onclick={(e) => removeChip(val, e)} aria-label="Remove">
                        <i class="ki-filled ki-cross text-2xs"></i>
                    </button>
                </span>
            {/each}
        </div>
    {:else if !multiple && hasValue}
        <span class="grow truncate text-mono">{labelFor(value)}</span>
    {:else}
        <span class="grow truncate text-muted-foreground">{placeholder ?? $t('common.actions.search')}</span>
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
                <input type="text" value={search} oninput={onSearchInput} placeholder={$t('common.actions.search')} />
            </div>
        </div>

        <div class="max-h-[240px] overflow-y-auto p-1" onscroll={onMenuScroll}>
            {#each listItems as item (item.value)}
                <button
                    type="button"
                    class="flex w-full items-center justify-between gap-2 rounded-md px-3 py-2 text-start text-sm hover:bg-muted {isSelected(item.value) ? 'bg-muted' : ''}"
                    onclick={() => choose(item)}
                    role="option"
                    aria-selected={isSelected(item.value)}
                >
                    <span class="truncate">{item.label}</span>
                    {#if isSelected(item.value)}<i class="ki-filled ki-check text-primary"></i>{/if}
                </button>
            {/each}

            {#if loading}
                <div class="px-3 py-2 text-sm text-muted-foreground">{$t('common.table.loading')}</div>
            {:else if listItems.length === 0}
                <div class="px-3 py-2 text-sm text-muted-foreground">{$t('common.table.no_results_title')}</div>
            {/if}
        </div>
    </div>
{/if}
