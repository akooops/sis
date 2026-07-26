<script>
    /**
     * MediaPickerMulti — form control for a MANY-file collection. Binds `value` to
     * an ordered array of media ids; the form submits only ids.
     *
     *   <MediaPickerMulti bind:value={form.data.files}
     *                     initial={album?.files ?? []}
     *                     accept={['images', 'videos', 'audio']} />
     *
     * `initial` seeds the thumbnails on an edit form — the ids alone can't render,
     * so the record hands over the media it already holds. Anything picked after
     * that is remembered from the library modal's own payload.
     *
     * Order is meaningful: the array order IS the display order, and the server
     * writes it to media.order_column, which HasMedia::getMedia() already sorts by.
     */
    import Button from '@/components/ui/Button.svelte';
    import MediaThumb from './MediaThumb.svelte';
    import MediaLibraryModal from './MediaLibraryModal.svelte';

    let {
        value = $bindable([]),
        initial = [],
        accept = null,
        disabled = false,
    } = $props();

    let open = $state(false);

    // Everything we can render a thumbnail for, keyed by id. Seeded from the
    // record and grown by each pick — `value` holds ids only, so this is what
    // turns an id back into something visible.
    let known = $state(new Map((initial ?? []).map((m) => [m.id, m])));

    // initial often arrives after mount (an edit form whose record is still
    // loading), so merge rather than reading it once.
    $effect(() => {
        if (!initial?.length) return;
        let changed = false;
        const next = new Map(known);
        for (const m of initial) {
            if (!next.has(m.id)) {
                next.set(m.id, m);
                changed = true;
            }
        }
        if (changed) known = next;
    });

    const items = $derived((value ?? []).map((id) => known.get(id) ?? { id, name: id, type: null, url: null }));

    let dragFrom = $state(null);

    function onpick(media) {
        known = new Map(known).set(media.id, media);
        if (!(value ?? []).includes(media.id)) value = [...(value ?? []), media.id];
    }

    function remove(id) {
        value = (value ?? []).filter((v) => v !== id);
    }

    function drop(to) {
        const from = dragFrom;
        dragFrom = null;
        if (from === null || from === to) return;
        const next = [...(value ?? [])];
        const [moved] = next.splice(from, 1);
        next.splice(to, 0, moved);
        value = next;
    }
</script>

<div class="flex flex-col gap-3">
    {#if items.length}
        <div class="grid grid-cols-3 gap-2 sm:grid-cols-5 lg:grid-cols-6">
            {#each items as item, index (item.id)}
                <!-- svelte-ignore a11y_no_static_element_interactions -->
                <div
                    class="group relative flex flex-col gap-1 rounded-lg border border-border p-1.5 {disabled ? '' : 'cursor-move hover:border-primary'}"
                    draggable={!disabled}
                    ondragstart={() => (dragFrom = index)}
                    ondragover={(e) => e.preventDefault()}
                    ondrop={() => drop(index)}
                    ondragend={() => (dragFrom = null)}
                >
                    <div class="flex aspect-square items-center justify-center overflow-hidden rounded bg-muted">
                        <MediaThumb {item} iconSize="text-xl" />
                    </div>
                    <span class="truncate text-[10px] text-muted-foreground" title={item.name}>{item.name}</span>

                    {#if !disabled}
                        <button
                            type="button"
                            class="kt-btn kt-btn-icon kt-btn-xs kt-btn-destructive absolute -end-1.5 -top-1.5 opacity-0 transition-opacity group-hover:opacity-100"
                            onclick={() => remove(item.id)}
                            aria-label="Remove {item.name}"
                            title="Remove"
                        >
                            <i class="ki-filled ki-cross"></i>
                        </button>
                    {/if}
                </div>
            {/each}
        </div>
    {/if}

    <div class="flex flex-wrap items-center gap-2">
        <Button variant="outline" size="sm" onclick={() => (open = true)} {disabled}>
            <i class="ki-filled ki-plus"></i>
            Add files
        </Button>
        {#if items.length}
            <span class="text-xs text-muted-foreground">
                {items.length} file{items.length === 1 ? '' : 's'} — drag to reorder
            </span>
        {/if}
    </div>
</div>

<MediaLibraryModal bind:open {accept} {onpick} />
