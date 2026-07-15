<script>
    /**
     * MediaThumb — the visual representation of a media item: the image itself for
     * images, a first-frame preview for videos, and a type icon for everything
     * else (documents, audio, or media without a URL). Fills its container.
     */
    let { item, iconSize = 'text-3xl' } = $props();

    const isImage = $derived(item?.type === 'images' || (item?.mime ?? '').startsWith('image/'));
    const isVideo = $derived(item?.type === 'videos' || (item?.mime ?? '').startsWith('video/'));

    const icons = {
        documents: 'ki-filled ki-file',
        audio: 'ki-filled ki-music',
        videos: 'ki-filled ki-video',
        images: 'ki-filled ki-picture',
    };
    const icon = $derived(icons[item?.type] ?? 'ki-filled ki-file');
</script>

{#if isImage && item?.url}
    <img src={item.url} alt={item.name} class="size-full object-cover" />
{:else if isVideo && item?.url}
    <!-- svelte-ignore a11y_media_has_caption -->
    <video src={item.url} muted preload="metadata" class="size-full object-cover"></video>
{:else}
    <i class="{icon} {iconSize} text-muted-foreground"></i>
{/if}
