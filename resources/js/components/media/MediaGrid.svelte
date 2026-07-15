<script>
    /**
     * MediaGrid — thumbnail grid of media items (MediaData) for the picker. Click
     * to select; `selectedId` highlights the current pick. Shows the media preview
     * (image/video) or a type icon, with the name beneath.
     */
    import MediaThumb from './MediaThumb.svelte';

    let { items = [], selectedId = null, onselect } = $props();
</script>

<div class="grid grid-cols-3 gap-3 sm:grid-cols-4">
    {#each items as item (item.id)}
        <button
            type="button"
            class="group flex flex-col overflow-hidden rounded-lg border text-start transition-colors {selectedId === item.id
                ? 'border-primary ring-2 ring-primary/30'
                : 'border-border hover:border-primary/50'}"
            onclick={() => onselect?.(item)}
            title={item.name}
        >
            <div class="flex aspect-square items-center justify-center bg-muted">
                <MediaThumb {item} />
            </div>
            <div class="p-2">
                <span class="block truncate text-xs font-medium text-mono">{item.name}</span>
            </div>
        </button>
    {/each}
</div>
