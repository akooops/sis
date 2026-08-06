<script>
    /**
     * RowActions — inline icon buttons for a table row: every action sits
     * directly on the row and explains itself in a hover pill (no dots menu).
     * Pass ONLY the actions this row/user may take — permission and row-state
     * checks belong to the caller:
     *
     *   <RowActions actions={[
     *       { icon: 'ki-eye', label: 'View', onclick: () => view(row) },
     *       { icon: 'ki-trash', label: 'Delete', onclick: () => remove(row), variant: 'destructive' },
     *   ].filter(Boolean)} />
     *
     * Clicks stop propagating so an action never also triggers the row click.
     */
    import Tooltip from '@/components/ui/Tooltip.svelte';

    let { actions = [] } = $props();
</script>

<div class="inline-flex items-center gap-0.5">
    {#each actions as action (action.label)}
        <Tooltip text={action.label}>
            <button
                type="button"
                class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost {action.variant === 'destructive' ? 'text-destructive' : ''}"
                onclick={(e) => {
                    e.stopPropagation();
                    action.onclick();
                }}
                disabled={action.disabled ?? false}
                aria-label={action.label}
            >
                <i class="ki-filled {action.icon}"></i>
            </button>
        </Tooltip>
    {/each}
</div>
