<script>
    /**
     * ApexChart — the one place this app talks to apexcharts.
     *
     *   <ApexChart type="bar" {series} {options} height={320} empty={!rows.length} />
     *
     * THE IMPORT IS LOCAL, NOT GLOBAL. Inertia code-splits every page, so
     * apexcharts ships in the chunk of whichever page uses a chart and never in
     * the admin's main bundle.
     *
     * THE CHART IS DESTROYED ON UNMOUNT — and on every data change, because the
     * effect below rebuilds it rather than patching it. An Inertia visit swaps
     * the page component without a reload, so a chart that outlives its node
     * keeps its resize listener and its SVG alive for the rest of the session.
     * Rebuilding costs an animation frame on a filter change, which is when the
     * numbers changed anyway.
     *
     * `empty` renders the empty state INSTEAD of a canvas. A chart handed no
     * points draws an axis of NaN, which looks like a bug rather than like a
     * form nobody has filled in yet.
     */
    import ApexCharts from 'apexcharts';
    import EmptyState from '@/components/ui/EmptyState.svelte';
    import { baseOptions, isDarkTheme, mergeOptions, watchTheme } from '@/lib/charts';

    let {
        type = 'bar',
        series = [],
        options = {},
        height = 300,
        empty = false,
        emptyTitle = 'Nothing to chart yet',
        emptyBody = null,
        emptyIcon = 'ki-filled ki-chart-line-up',
    } = $props();

    let host = $state(null);
    let dark = $state(isDarkTheme());

    // The theme switch is a class on <html>; the palette is picked per mode, so
    // a flip has to rebuild the chart rather than recolour it.
    $effect(() => watchTheme((next) => (dark = next)));

    $effect(() => {
        // Read every dependency BEFORE the early return, so the effect still
        // re-runs when the chart is currently unmounted (empty -> not empty).
        const config = mergeOptions(baseOptions(dark), {
            ...options,
            chart: { ...(options.chart ?? {}), type, height },
            series,
        });
        const node = host;

        if (!node) return;

        // EVERY RUN GETS ITS OWN CHILD NODE, and apex is pointed at that rather
        // than at the bound host. Non-negotiable, because the teardown below is
        // deferred: apex's destroy() empties its container element outright
        // (`while (el.firstChild) el.removeChild(...)`), and Svelte flushes the
        // old effect's cleanup and the new effect's body in ONE synchronous
        // tick. Sharing the host would mean the new chart has already drawn
        // into it by the time the old chart's destroy lands — and the old
        // chart would erase the new chart's SVG, blanking the chart for good on
        // every preset change, filter change, reload and theme flip.
        const el = document.createElement('div');
        node.appendChild(el);

        const chart = new ApexCharts(el, config);

        // render() is async; a rejection here is a chart that never drew, not
        // something the page can recover from — swallow it rather than throwing
        // out of an effect. Swallowed here rather than at the teardown so
        // `drawn` can never reject, which is what makes chaining onto it safe.
        const drawn = chart.render().catch(() => {});

        // Teardown CHAINS onto render instead of racing it. Cleanup runs
        // synchronously — an Inertia visit, a filter change or a theme flip
        // disposes this effect in the same tick — so `destroy()` on a chart that
        // has not finished mounting is a real sequence, and apex then finishes
        // rendering into a detached node it no longer knows how to tear down:
        // the instance, its resize listener and its SVG outlive the page. With
        // the private node above, the wait costs nothing: the only DOM this
        // destroy can touch is DOM this run created.
        return () => {
            drawn
                .then(() => {
                    try {
                        chart.destroy();
                    } catch {
                        // A half-built chart can throw on the way down; the node
                        // is removed below either way, so there is nothing left
                        // to clean up by hand.
                    }
                })
                .finally(() => el.remove());
        };
    });
</script>

{#if empty}
    <EmptyState icon={emptyIcon} title={emptyTitle} body={emptyBody} />
{:else}
    <div bind:this={host} style="min-height: {height}px"></div>
{/if}
