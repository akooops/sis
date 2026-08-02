<script>
    /**
     * One card on the canvas.
     *
     * Stateless by design: everything it shows comes from props, and every
     * action is a callback. Per-row $state inside an {#each} swaps between rows
     * when the list reorders — which is precisely what a drag does — so a card
     * that remembered anything itself would remember the wrong thing.
     */
    import Badge from '@/components/ui/Badge.svelte';
    import { translate } from '@/lib/forms/i18n';

    let {
        field,
        pages = [],
        locale = 'en',
        fallbackLocale = 'en',
        selected = false,
        error = null,
        locked = false,
        canMoveUp = false,
        canMoveDown = false,
        onselect = null,
        onstep = null,
        onmove = null,
        onduplicate = null,
        onremove = null,
    } = $props();

    const label = $derived(
        translate(field.label, locale, fallbackLocale)
            || translate(field.content, locale, fallbackLocale)
            || field.key,
    );

    const missingHere = $derived(
        // Only the DEFAULT locale being blank is a 422; elsewhere it is just
        // unfinished, which is worth showing but not worth alarming about.
        !translate(field.label, locale, null) && !translate(field.content, locale, null),
    );
</script>

<div
    class="group relative flex items-center gap-3 rounded-lg border bg-background p-3 {selected
        ? 'border-primary ring-1 ring-primary'
        : 'border-border'} {error ? 'border-destructive' : ''}"
>
    <i
        class="ki-filled ki-dots-square-vertical shrink-0 text-muted-foreground {locked ? 'opacity-30' : 'cursor-grab'}"
        aria-hidden="true"
    ></i>

    <button type="button" class="flex min-w-0 grow flex-col items-start gap-1 text-start" onclick={() => onselect?.(field.id)}>
        <span class="flex items-center gap-2">
            <span class="truncate text-sm font-medium text-mono">{label}</span>
            {#if field.is_required}<span class="text-destructive" title="Required">*</span>{/if}
            {#if missingHere}
                <Badge variant="warning" size="sm">Not translated</Badge>
            {/if}
        </span>
        <span class="flex items-center gap-2 text-2sm text-muted-foreground">
            <Badge variant="secondary" size="sm">{field.type}</Badge>
            <code class="truncate">{field.key}</code>
        </span>
        {#if error}
            <span class="text-2sm text-destructive">{Object.values(error)[0]}</span>
        {/if}
    </button>

    {#if !locked}
        <!-- Revealed on hover AND focus-within: hover-only would hide these
             from anyone driving the page with a keyboard. -->
        <div class="flex shrink-0 items-center gap-1 opacity-0 transition-opacity group-hover:opacity-100 group-focus-within:opacity-100">
            <button
                type="button"
                class="kt-btn kt-btn-icon kt-btn-xs kt-btn-secondary"
                disabled={!canMoveUp}
                aria-label="Move up"
                onclick={() => onstep?.(field.id, -1)}
            ><i class="ki-filled ki-up"></i></button>
            <button
                type="button"
                class="kt-btn kt-btn-icon kt-btn-xs kt-btn-secondary"
                disabled={!canMoveDown}
                aria-label="Move down"
                onclick={() => onstep?.(field.id, 1)}
            ><i class="ki-filled ki-down"></i></button>

            {#if pages.length > 1}
                <select
                    class="kt-input h-7 w-[110px] text-2sm"
                    aria-label="Move to page"
                    onchange={(e) => { onmove?.(field.id, e.currentTarget.value); e.currentTarget.selectedIndex = 0; }}
                >
                    <option value="">Move to…</option>
                    {#each pages as page (page.id)}
                        <option value={page.id}>{page.name}</option>
                    {/each}
                </select>
            {/if}

            <button
                type="button"
                class="kt-btn kt-btn-icon kt-btn-xs kt-btn-secondary"
                aria-label="Duplicate"
                onclick={() => onduplicate?.(field.id)}
            ><i class="ki-filled ki-copy"></i></button>
            <button
                type="button"
                class="kt-btn kt-btn-icon kt-btn-xs kt-btn-destructive"
                aria-label="Remove"
                onclick={() => onremove?.(field.id)}
            ><i class="ki-filled ki-trash"></i></button>
        </div>
    {/if}
</div>
