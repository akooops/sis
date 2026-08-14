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
        locale = 'en',
        fallbackLocale = 'en',
        selected = false,
        error = null,
        locked = false,
        canMoveUp = false,
        canMoveDown = false,
        onselect = null,
        onstep = null,
        onduplicate = null,
        onremove = null,
    } = $props();

    /**
     * A one-line summary of whatever the element says.
     *
     * A heading, paragraph or html element has no `label` — its text lives in
     * `content`, as MARKUP. Printed raw, the card read
     * `<p><strong>Note:</strong> this block is…` and ran off the side of the
     * canvas. This flattens it to its text, which is all a summary row wants.
     *
     * DOMParser, not a regex: it decodes entities as well as dropping tags, and
     * it parses into a detached document that runs nothing.
     */
    function plain(value) {
        const text = String(value ?? '');

        if (!text.includes('<') && !text.includes('&')) {
            return text;
        }

        return (new DOMParser().parseFromString(text, 'text/html').body.textContent ?? '')
            .replace(/\s+/g, ' ')
            .trim();
    }

    const label = $derived(
        plain(translate(field.label, locale, fallbackLocale))
            || plain(translate(field.content, locale, fallbackLocale))
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
        <!-- w-full + min-w-0 on BOTH rows, and shrink on what truncates.
             `truncate` alone does nothing here: a flex item defaults to
             min-width:auto, so the row refused to shrink below its content and
             the text ran past the card instead of ellipsing inside it. The
             button already carries min-w-0; every nested flex box needs its
             own. -->
        <span class="flex w-full min-w-0 items-center gap-2">
            <span class="min-w-0 shrink truncate text-sm font-medium text-mono">{label}</span>
            {#if field.is_required}<span class="shrink-0 text-destructive" title="Required">*</span>{/if}
            {#if missingHere}
                <span class="shrink-0"><Badge variant="warning" size="sm">Not translated</Badge></span>
            {/if}
        </span>
        <span class="flex w-full min-w-0 items-center gap-2 text-2sm text-muted-foreground">
            <span class="shrink-0"><Badge variant="secondary" size="sm">{field.type}</Badge></span>
            <code class="min-w-0 shrink truncate">{field.key}</code>
        </span>
        {#if error}
            <span class="w-full min-w-0 break-words text-2sm text-destructive">{Object.values(error)[0]}</span>
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

            <!-- No "Move to…" select here. It cost 110px of every row, on every
                 element, permanently — and the row is where the element's own
                 name has to fit. It moved to the inspector's "Page" field, which
                 has room for it and can show the CURRENT page rather than an
                 empty prompt. Dragging a card still does the same job. -->
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
