<script>
    /**
     * MediaGrid — thumbnail grid of media items (MediaData). Click to select;
     * `selectedId` highlights the current pick.
     */
    import { formatFileSize } from '@/lib/format';

    let { items = [], selectedId = null, onselect } = $props();

    function isImage(item) {
        return item.type === 'images' || (item.mime ?? '').startsWith('image/');
    }

    const icons = {
        documents: 'ki-filled ki-file',
        videos: 'ki-filled ki-video',
        audio: 'ki-filled ki-music',
    };
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
                {#if isImage(item) && item.url}
                    <img src={item.url} alt={item.name} class="size-full object-cover" />
                {:else}
                    <i class="{icons[item.type] ?? 'ki-filled ki-file'} text-3xl text-muted-foreground"></i>
                {/if}
            </div>
            <div class="flex flex-col gap-0.5 p-2">
                <span class="truncate text-xs font-medium text-mono">{item.name}</span>
                <span class="text-2xs text-muted-foreground">{formatFileSize(item.size)}</span>
            </div>
        </button>
    {/each}
</div>
