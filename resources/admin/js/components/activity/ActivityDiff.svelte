<script>
    /**
     * ActivityDiff — the two sides of a logged change.
     *
     * Renders nothing when there is no column diff (attach/detach/login carry
     * only meta), so callers can drop it in unconditionally.
     *
     *   <ActivityDiff properties={row.properties} />
     */
    import { diffRows } from '@/lib/activity';
    import ClampText from '@/components/ui/ClampText.svelte';

    let { properties = {} } = $props();

    const rows = $derived(diffRows(properties));
    const hasOld = $derived(Object.keys(properties?.old ?? {}).length > 0);
</script>

{#if rows.length}
    <div class="overflow-x-auto rounded-lg border border-border">
        <table class="w-full text-xs">
            <thead>
                <tr class="bg-muted/50 text-muted-foreground">
                    <th class="px-3 py-2 text-start font-medium">Field</th>
                    {#if hasOld}
                        <th class="px-3 py-2 text-start font-medium">Before</th>
                    {/if}
                    <th class="px-3 py-2 text-start font-medium">After</th>
                </tr>
            </thead>
            <tbody>
                {#each rows as row (row.key)}
                    <tr class="border-t border-border align-top">
                        <td class="px-3 py-2 font-medium text-mono">{row.key}</td>
                        {#if hasOld}
                            <td class="max-w-[180px] px-3 py-2 text-muted-foreground line-through decoration-destructive/40">
                                <ClampText value={row.from} lines={3} toggle copy />
                            </td>
                        {/if}
                        <td class="max-w-[180px] px-3 py-2 {row.changed ? 'text-mono' : 'text-muted-foreground'}">
                            <ClampText value={row.to} lines={3} toggle copy />
                        </td>
                    </tr>
                {/each}
            </tbody>
        </table>
    </div>
{/if}
