<script>
    /**
     * MediaPicker — form control that binds a media `value` (id). Shows a preview,
     * lets the user upload a new file or pick an existing one from the library,
     * and clears the selection. Forms submit only the id.
     *
     *   <MediaPicker type="images" bind:value={form.data.avatar} previewUrl={user?.avatar_url} />
     */
    import Button from '@/components/ui/Button.svelte';
    import MediaLibraryModal from './MediaLibraryModal.svelte';
    import { t } from '@/lib/i18n';

    let {
        value = $bindable(null),
        type = 'images',
        previewUrl = null, // existing url for edit forms (before a new pick)
    } = $props();

    let open = $state(false);
    let initialTab = $state('library');
    let picked = $state(null); // MediaData of a new pick this session

    const isImage = $derived(type === 'images');
    const shownUrl = $derived(picked?.url ?? (value ? previewUrl : null));

    function onpick(media) {
        picked = media;
        value = media.id;
    }

    function openLibrary() {
        initialTab = 'library';
        open = true;
    }
    function openUpload() {
        initialTab = 'upload';
        open = true;
    }
    function clear() {
        value = null;
        picked = null;
    }
</script>

<div class="flex items-center gap-3">
    {#if isImage}
        <div class="flex size-16 items-center justify-center overflow-hidden rounded-lg border border-border bg-muted">
            {#if shownUrl}
                <img src={shownUrl} alt="" class="size-full object-cover" />
            {:else}
                <i class="ki-filled ki-picture text-xl text-muted-foreground"></i>
            {/if}
        </div>
    {:else if picked}
        <span class="truncate text-sm text-mono">{picked.name}</span>
    {/if}

    <div class="flex flex-wrap gap-2">
        <Button variant="outline" size="sm" onclick={openUpload}>
            <i class="ki-filled ki-cloud-add"></i>
            {$t('common.media.upload')}
        </Button>
        <Button variant="outline" size="sm" onclick={openLibrary}>
            <i class="ki-filled ki-picture"></i>
            {$t('common.media.library')}
        </Button>
        {#if value}
            <Button variant="ghost" size="sm" onclick={clear}>{$t('common.media.remove')}</Button>
        {/if}
    </div>
</div>

<MediaLibraryModal bind:open {type} {initialTab} {onpick} />
