<script>
    /**
     * MediaThumb — the visual representation of a media item: the image itself for
     * images, a first-frame preview for videos, and a type icon for everything
     * else (documents, audio, or media without a URL). Fills its container.
     *
     * Give it a sized box (`aspect-square overflow-hidden`) — it fills whatever it
     * is put in. The min-h-0/min-w-0 matter: a flex item refuses to shrink below
     * its intrinsic size, so without them a portrait image stretches its own box
     * taller than its neighbours instead of being cropped by object-cover.
     */
    let { item, iconSize = 'text-3xl' } = $props();

    const isImage = $derived(item?.type === 'images' || (item?.mime ?? '').startsWith('image/'));
    const isVideo = $derived(item?.type === 'videos' || (item?.mime ?? '').startsWith('video/'));

    const icons = {
        documents: 'ki-filled ki-document',
        audio: 'ki-filled ki-speaker',
        videos: 'ki-filled ki-screen',
        images: 'ki-filled ki-picture',
    };
    const icon = $derived(icons[item?.type] ?? 'ki-filled ki-document');
</script>

{#if isImage && item?.url}
    <img src={item.url} alt={item.name} class="size-full min-h-0 min-w-0 object-cover" />
{:else if isVideo && item?.url}
    <!-- svelte-ignore a11y_media_has_caption -->
    <video src={item.url} muted preload="metadata" class="size-full min-h-0 min-w-0 object-cover"></video>
{:else}
    <i class="{icon} {iconSize} text-muted-foreground"></i>
{/if}
