<script>
    /**
     * The element palette.
     *
     * Every tile is a BUTTON, not just a drag source. Drag is the nice path;
     * click-to-add is the one that works with a keyboard, on a touch screen, and
     * when a drag goes wrong — the same reasoning that pairs the reorder drawer's
     * drag with arrow buttons.
     */
    let { palette = [], disabled = false, onadd = null } = $props();

    // Filtered, not defaulted: an element whose group is not listed here silently
    // vanishes from the palette, so a new server-side group needs a line here too.
    const GROUPS = [
        { key: 'input', label: 'Fields' },
        { key: 'choice', label: 'Choices' },
        { key: 'layout', label: 'Layout' },
        { key: 'display', label: 'Content' },
        { key: 'action', label: 'Actions' },
    ];

    const grouped = $derived(
        GROUPS.map((g) => ({ ...g, items: palette.filter((p) => p.group === g.key) })).filter((g) => g.items.length),
    );
</script>

<div class="flex flex-col gap-5">
    {#each grouped as group (group.key)}
        <div class="flex flex-col gap-2">
            <h3 class="text-2sm font-medium uppercase tracking-wide text-muted-foreground">{group.label}</h3>
            <div class="grid grid-cols-2 gap-2">
                {#each group.items as item (item.code)}
                    <button
                        type="button"
                        class="flex items-center gap-2 rounded-lg border border-border p-2 text-start text-2sm hover:border-primary hover:bg-muted/40 disabled:cursor-not-allowed disabled:opacity-50"
                        {disabled}
                        title={`Add a ${item.label.toLowerCase()}`}
                        onclick={() => onadd?.(item.code)}
                    >
                        <i class="ki-filled {item.icon} shrink-0 text-muted-foreground"></i>
                        <span class="min-w-0 truncate">{item.label}</span>
                    </button>
                {/each}
            </div>
        </div>
    {/each}
</div>
