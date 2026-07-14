<script>
    /**
     * MediaLibraryModal — browse existing (clean) media to reuse, or upload a new
     * file. Either way it returns the chosen media via `onpick(mediaData)`; the
     * form then submits only `mediaData.id`.
     *
     *   <MediaLibraryModal bind:open type="images" onpick={(m) => (value = m.id)} />
     */
    import Modal from '@/components/ui/Modal.svelte';
    import Tabs from '@/components/ui/Tabs.svelte';
    import Button from '@/components/ui/Button.svelte';
    import SearchBar from '@/components/data/SearchBar.svelte';
    import Pagination from '@/components/data/Pagination.svelte';
    import MediaGrid from './MediaGrid.svelte';
    import MediaUploader from './MediaUploader.svelte';
    import { useIndex } from '@/lib/api/useIndex.svelte';
    import { t } from '@/lib/i18n';

    let { open = $bindable(false), type = 'images', initialTab = 'library', onpick } = $props();

    let tab = $state(initialTab);
    let selected = $state(null);

    const list = useIndex('api.v1.admin.media.index', {
        perPage: 24,
        filter: { type, clean: true },
        immediate: false,
    });

    // Load (or refresh) when the modal opens.
    $effect(() => {
        if (open) {
            selected = null;
            tab = initialTab;
            list.reload();
        }
    });

    function confirmPick() {
        if (selected) {
            onpick?.(selected);
            open = false;
        }
    }

    function onUploaded(media) {
        // A fresh upload is picked immediately.
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
            <div class="mb-3">
                <SearchBar onsearch={(v) => list.setSearch(v)} />
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
            <MediaUploader {type} onuploaded={onUploaded} />
        {/if}
    </div>

    {#snippet footer()}
        <Button variant="secondary" onclick={() => (open = false)}>{$t('common.actions.cancel')}</Button>
        {#if tab === 'library'}
            <Button variant="primary" disabled={!selected} onclick={confirmPick}>{$t('common.media.select')}</Button>
        {/if}
    {/snippet}
</Modal>
