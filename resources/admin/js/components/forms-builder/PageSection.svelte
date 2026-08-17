<script>
    /**
     * One page on the canvas, and the drop zone for its fields.
     *
     * The canvas shows what a page IS — its number, its name, and the elements on
     * it. Everything that CHANGES the page (name, title, instruction toggle, CSS
     * handles) lives in the settings panel, reached by clicking the header. Those
     * controls used to sit inline here and buried the shape of the form.
     *
     * DELETE is the exception, and it stays on the header: it removes the thing
     * you are looking at, so it belongs next to it rather than at the bottom of a
     * panel you have to open first. It sits OUTSIDE the select button — nesting
     * one button in another is invalid HTML — and stops the click anyway, so a
     * header that becomes clickable later cannot select a page on its way out.
     *
     * The dndzone element contains NOTHING BUT ITEMS. svelte-dnd-action indexes
     * its children positionally against `items`, so an empty-state message or a
     * heading inside the zone shifts every index by one and the wrong element
     * moves. Anything else is a sibling.
     */
    import { dndzone } from 'svelte-dnd-action';
    import { flip } from 'svelte/animate';
    import CanvasElement from './CanvasElement.svelte';
    import Badge from '@/components/ui/Badge.svelte';

    let {
        page,
        index = 0,
        locale = 'en',
        fallbackLocale = 'en',
        selectedId = null,
        selected = false,
        errors = {},
        locked = false,
        canRemove = false,
        onfields = null,
        onchildren = null,
        onselect = null,
        onselectpage = null,
        onstep = null,
        onduplicate = null,
        onremove = null,
        onremovepage = null,
    } = $props();

    /*
     * Must be the ARRAY INSTANCE rendered in the {#each} below. A $derived that
     * built a new array — .filter(), .sort(), even [...page.fields] — hands the
     * zone a different reference on every read, and it re-measures forever.
     */
    const items = $derived(page.fields);


    // Whatever the server said about the page itself, rather than its fields.
    const pageError = $derived(errors[page.id] ? Object.values(errors[page.id])[0] : null);

    const FLIP = { duration: 160 };
</script>

<section
    class="flex flex-col gap-3 rounded-xl border p-4 {selected ? 'border-primary ring-1 ring-primary' : 'border-border'} {pageError
        ? 'border-destructive'
        : ''}"
>
    <header class="flex flex-wrap items-center gap-2">
        <button
            type="button"
            class="flex min-w-0 grow items-center gap-2 text-start"
            aria-pressed={selected}
            onclick={() => onselectpage?.(page.id)}
        >
            <Badge variant="secondary">Page {index + 1}</Badge>
            <span class="truncate text-sm font-medium text-mono">{page.name}</span>
            {#if page.is_interstitial}
                <Badge variant="secondary" size="sm">Instruction page</Badge>
            {/if}
        </button>

        <!-- The last page cannot go: a form with no page has nothing to render. -->
        {#if !locked && canRemove}
            <button
                type="button"
                class="kt-btn kt-btn-icon kt-btn-xs kt-btn-destructive shrink-0"
                aria-label="Remove page"
                title="Remove page"
                onclick={(e) => {
                    e.stopPropagation();
                    onremovepage?.(page.id);
                }}
            ><i class="ki-filled ki-trash"></i></button>
        {/if}
    </header>

    {#if pageError}
        <p class="text-2sm text-destructive">{pageError}</p>
    {/if}

    <!-- Items only. Everything else lives outside this element. -->
    <div
        class="flex min-h-[56px] flex-col gap-2"
        use:dndzone={{
            items,
            type: 'form-fields',
            dragDisabled: locked,
            dropTargetStyle: { outline: '2px dashed var(--color-primary)', borderRadius: '10px' },
            flipDurationMs: FLIP.duration,
        }}
        onconsider={(e) => onfields?.(page.id, e.detail.items)}
        onfinalize={(e) => onfields?.(page.id, e.detail.items)}
    >
        {#each items as field (field.id)}
            <div animate:flip={FLIP}>
                <CanvasElement
                    {field}
                    {locale}
                    {fallbackLocale}
                    {locked}
                    {errors}
                    {selectedId}
                    selected={selectedId === field.id}
                    error={errors[field.id] ?? null}
                    canMoveUp={items.indexOf(field) > 0}
                    canMoveDown={items.indexOf(field) < items.length - 1}
                    {onselect}
                    {onstep}
                    {onduplicate}
                    {onremove}
                    {onchildren}
                />
            </div>
        {/each}
    </div>

    {#if items.length === 0}
        <p class="rounded-lg border border-dashed border-border p-4 text-center text-2sm text-muted-foreground">
            Empty. Drop an element here, or click one in the palette.
        </p>
    {/if}
</section>
