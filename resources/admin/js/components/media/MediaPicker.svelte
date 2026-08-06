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

    // A new pick wins; otherwise the record's CURRENT file. Deliberately NOT
    // gated on `value`: an edit form seeds its media field to null (null means
    // "leave the existing file alone" — every controller only attaches a truthy
    // id), so gating on it meant `previewUrl` was unreachable and an edit form
    // always looked empty even when the record had an image.
    const shownUrl = $derived(picked?.url ?? previewUrl);

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
            Media library
        </Button>
        <!--
            Only ever drops the NEW pick: clearing sends null, which every
            controller reads as "leave the current file alone" (there is no
            detach path), so on a record that already has one this is an undo,
            not a delete — say so rather than promising a removal.
        -->
        {#if value}
            <Button variant="ghost" size="sm" onclick={clear}>{previewUrl ? 'Undo' : 'Remove'}</Button>
        {/if}
    </div>
</div>

<MediaLibraryModal bind:open {accept} {onpick} />
