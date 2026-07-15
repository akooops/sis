<script>
    /**
     * MediaPicker — form control that binds a media `value` (id). Shows a preview,
     * lets the user upload a new file or pick an existing one from the library,
     * and clears the selection. Forms submit only the id.
     *
     * `accept` restricts the offered types (e.g. ['images'] for an avatar); omit
     * for any type.
     *
     *   <MediaPicker accept={['images']} bind:value={form.data.avatar} previewUrl={user?.avatar_url} />
     */
    import Button from '@/components/ui/Button.svelte';
    import MediaLibraryModal from './MediaLibraryModal.svelte';
    import { t } from '@/lib/i18n';

    let {
        value = $bindable(null),
        accept = null, // array of allowed type keys, or null = all
        previewUrl = null, // existing url for edit forms (before a new pick)
    } = $props();

    let open = $state(false);
    let picked = $state(null); // MediaData of a new pick this session

    // Show the image preview box for image-only pickers or when the pick is an image.
    const imageOnly = $derived(accept?.length === 1 && accept[0] === 'images');
    const showImageBox = $derived(imageOnly || picked?.type === 'images');
    const shownUrl = $derived(picked?.url ?? (value ? previewUrl : null));

    function onpick(media) {
        picked = media;
        value = media.id;
    }

    // One entry point: the library modal, which has its own Upload tab.
    function openLibrary() {
        open = true;
    }
    function clear() {
        value = null;
        picked = null;
    }
</script>

<div class="flex items-center gap-3">
    {#if showImageBox}
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
        <Button variant="outline" size="sm" onclick={openLibrary}>
            <i class="ki-filled ki-picture"></i>
            {$t('common.media.library')}
        </Button>
        {#if value}
            <Button variant="ghost" size="sm" onclick={clear}>{$t('common.media.remove')}</Button>
        {/if}
    </div>
</div>

<MediaLibraryModal bind:open {accept} {onpick} />
