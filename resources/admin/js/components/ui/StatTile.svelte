<script>
    /**
     * StatTile — one number in a `kt-card`, with a label, an icon and a hint.
     *
     * EXTRACTED FROM Pages/Admin/Forms/Analytics.svelte, where it was a
     * page-local snippet, when the dashboard became its second consumer.
     * `components/` is the deduplicated kit; two copies of a KPI card is how the
     * padding and the type scale start to drift between two pages that are
     * meant to read as one system.
     *
     * `ui/` rather than `charts/` on purpose — a stat tile is not a chart, and
     * `charts/` owns the ApexCharts wrapper.
     */
    import ClampText from '@/components/ui/ClampText.svelte';

    let { label, value, hint = null, icon = 'ki-chart-line-up', clamp = false } = $props();
</script>

<div class="kt-card p-4">
    <div class="flex items-start justify-between gap-2">
        <span class="text-2sm font-medium text-secondary-foreground">{label}</span>
        <i class="ki-filled {icon} text-base text-muted-foreground"></i>
    </div>
    <div class="mt-2 text-2xl font-semibold text-mono">
        {#if clamp}
            <span class="block text-lg leading-tight"><ClampText value={value} maxWidth="100%" title={value} /></span>
        {:else}
            {value}
        {/if}
    </div>
    {#if hint}<div class="mt-1 text-2sm text-muted-foreground">{hint}</div>{/if}
</div>
