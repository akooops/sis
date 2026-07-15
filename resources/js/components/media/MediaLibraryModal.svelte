<script>
    /**
     * MediaLibraryModal — browse existing (clean) media to reuse, or upload a new
     * file. Returns the chosen media via `onpick(mediaData)`; the form submits
     * only `mediaData.id`.
     *
     * `accept` limits which types are offered (tabs + upload). Omit for all types;
     * pass e.g. ['images'] for an avatar (a single type hides the type tabs).
     *
     *   <MediaLibraryModal bind:open accept={['images']} onpick={(m) => (value = m.id)} />
     */
    import Modal from '@/components/ui/Modal.svelte';
    import Tabs from '@/components/ui/Tabs.svelte';
    import Button from '@/components/ui/Button.svelte';
    import SearchBar from '@/components/data/SearchBar.svelte';
    import Pagination from '@/components/data/Pagination.svelte';
    import MediaGrid from './MediaGrid.svelte';
    import MediaUploader from './MediaUploader.svelte';
    import { untrack } from 'svelte';
    import { useIndex } from '@/lib/api/useIndex.svelte';
    import { MEDIA_TYPES } from '@/lib/upload';
    import { t } from '@/lib/i18n';

    let { open = $bindable(false), accept = null, initialTab = 'library', onpick } = $props();

    const allowedTypes = $derived(accept?.length ? accept.filter((ty) => MEDIA_TYPES.includes(ty)) : MEDIA_TYPES);
    const singleType = $derived(allowedTypes.length === 1 ? allowedTypes[0] : null);

    // Type filter tabs: "All (of allowed)" + one per allowed type. Hidden when a
    // single type is allowed (the filter is then fixed).
    const typeTabs = $derived(
        singleType
            ? []
            : [{ id: '__all', label: $t('media.tabs.all') }, ...allowedTypes.map((ty) => ({ id: ty, label: $t(`media.tabs.${ty}`) }))],
    );

    let tab = $state(initialTab); // library | upload
    let activeType = $state('__all'); // __all | <type>
    let selected = $state(null);

    const list = useIndex('api.v1.admin.media.index', { perPage: 15, immediate: false });

    // The `filter[type]` value for the current tab: a single type, the restricted
    // subset (comma-joined) for "All", or nothing when everything is allowed.
    function typeFilter() {
        if (singleType) return singleType;
        if (activeType === '__all') {
            return allowedTypes.length < MEDIA_TYPES.length ? allowedTypes.join(',') : undefined;
        }
        return activeType;
    }

    function applyFilters({ keepSearch = false } = {}) {
        const filter = { clean: true };
        const type = typeFilter();
        if (type) filter.type = type;
        if (keepSearch && list.search) filter.search = list.search;
        list.setFilters(filter);
    }

    // (Re)load + reset whenever the modal opens. The reset work is wrapped in
    // untrack() so this effect depends ONLY on `open` — never on the state it
    // mutates (list params, activeType, …), which would be an infinite loop.
    $effect(() => {
        if (!open) return;
        untrack(() => {
            selected = null;
            tab = initialTab;
            activeType = singleType ?? '__all';
            const filter = { clean: true };
            const type = singleType ?? (allowedTypes.length < MEDIA_TYPES.length ? allowedTypes.join(',') : undefined);
            if (type) filter.type = type;
            list.setFilters(filter);
        });
    });

    function selectType(id) {
        activeType = id;
        selected = null;
        applyFilters({ keepSearch: true });
    }

    function confirmPick() {
        if (selected) {
            onpick?.(selected);
            open = false;
        }
    }

    function onUploaded(media) {
        onpick?.(media);
        open = false;
    }
</script>

<Modal bind:open size="lg" title={$t('common.media.library')}>
    <Tabs
        tabs={[
            { id: 'library', label: $t('common.media.library'), icon: 'ki-filled ki-picture' },
            { id: 'upload', label: $t('common.media.upload'), icon: 'ki-filled ki-cloud-add' },
        ]}
        bind:active={tab}
    />

    <div class="pt-4">
        {#if tab === 'library'}
            {#if typeTabs.length}
                <div class="kt-tabs kt-tabs-line mb-3 overflow-x-auto" role="tablist">
                    {#each typeTabs as tt (tt.id)}
                        <button
                            type="button"
                            role="tab"
                            data-kt-tab-toggle
                            class="kt-tab-toggle {activeType === tt.id ? 'active' : ''}"
                            aria-selected={activeType === tt.id}
                            onclick={() => selectType(tt.id)}
                        >
                            {tt.label}
                        </button>
                    {/each}
                </div>
            {/if}
            <div class="mb-3">
                <SearchBar value={list.search} onsearch={(v) => list.setSearch(v)} />
            </div>
            {#if list.loading}
                <div class="py-10 text-center text-sm text-muted-foreground">{$t('common.table.loading')}</div>
            {:else if list.rows.length === 0}
                <div class="py-10 text-center text-sm text-muted-foreground">{$t('common.table.no_results_title')}</div>
            {:else}
                <MediaGrid items={list.rows} selectedId={selected?.id} onselect={(i) => (selected = i)} />
                <Pagination meta={list.meta} onPageChange={(p) => list.goToPage(p)} onPerPageChange={(n) => list.setPerPage(n)} />
            {/if}
        {:else}
            <MediaUploader accept={allowedTypes} onuploaded={onUploaded} />
        {/if}
    </div>

    {#snippet footer()}
        <Button variant="secondary" onclick={() => (open = false)}>{$t('common.actions.cancel')}</Button>
        {#if tab === 'library'}
            <Button variant="primary" disabled={!selected} onclick={confirmPick}>{$t('common.media.select')}</Button>
        {/if}
    {/snippet}
</Modal>
