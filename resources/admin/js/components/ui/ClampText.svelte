<script>
    /**
     * ClampText — clamps content and, when it overflows, fades the trailing chars
     * and appends a brand-primary `…`. Two modes:
     *   - static (default): the `…` is just an indicator (used in table cells).
     *   - toggle: the `…` becomes a button that expands/collapses (view drawer),
     *     and a copy button (full value → clipboard) is offered.
     *
     * Accepts either a `value` string or a `children` snippet (to wrap a cell).
     *
     *   <ClampText value={role.name} lines={4} toggle copy />           // drawer
     *   <ClampText maxWidth="260px">{#snippet children()}…{/snippet}</ClampText>  // table
     */

    let {
        value = '',
        children = null,
        lines = 1,
        toggle = false,
        copy = false,
        maxWidth = null,
        title = null,
    } = $props();

    let el;
    let overflowing = $state(false);
    let expanded = $state(false);
    let copied = $state(false);

    const multiline = $derived(lines > 1);

    // Measure whether the (clamped) content actually overflows.
    $effect(() => {
        void value;
        void expanded;
        void children;
        if (!el) return;
        overflowing = multiline ? el.scrollHeight - el.clientHeight > 1 : el.scrollWidth - el.clientWidth > 1;
    });

    const clampStyle = $derived.by(() => {
        if (expanded) return '';
        return multiline
            ? `display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:${lines};overflow:hidden;`
            : 'white-space:nowrap;overflow:hidden;';
    });

    // Fade the trailing edge of the shown text (vertical for a multi-line clamp,
    // horizontal for a single line).
    const fadeStyle = $derived.by(() => {
        if (expanded || !overflowing) return '';
        const grad = multiline
            ? 'linear-gradient(to bottom, #000 calc(100% - 1.15em), transparent)'
            : 'linear-gradient(to right, #000 calc(100% - 2.2em), transparent)';
        return `-webkit-mask-image:${grad};mask-image:${grad};`;
    });

    async function doCopy() {
        try {
            await navigator.clipboard.writeText(String(value ?? ''));
            copied = true;
            setTimeout(() => (copied = false), 1500);
        } catch {
            /* clipboard unavailable */
        }
    }
</script>

<div class="flex {multiline ? 'items-start' : 'items-center'} gap-1 min-w-0" style={maxWidth ? `max-width:${maxWidth}` : ''} {title}>
    <span bind:this={el} class="min-w-0 flex-1 break-words" style="{clampStyle}{fadeStyle}">
        {#if children}{@render children()}{:else}{value}{/if}
    </span>
    {#if overflowing || (toggle && expanded)}
        <span class="flex shrink-0 items-center gap-1 {multiline ? 'pt-0.5' : ''}">
            {#if toggle}
                <button
                    type="button"
                    class="text-primary font-bold leading-none hover:opacity-70"
                    onclick={() => (expanded = !expanded)}
                    aria-label={expanded ? 'Collapse' : 'Expand'}
                >
                    {#if expanded}<i class="ki-filled ki-arrow-up text-sm"></i>{:else}…{/if}
                </button>
            {:else}
                <span class="text-primary font-bold leading-none">…</span>
            {/if}
            {#if copy}
                <button type="button" class="text-muted-foreground hover:text-primary" title="Copy" onclick={doCopy} aria-label="Copy">
                    <i class="ki-filled {copied ? 'ki-check' : 'ki-copy'} text-sm"></i>
                </button>
            {/if}
        </span>
    {/if}
</div>
