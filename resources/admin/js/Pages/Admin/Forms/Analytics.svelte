<script>
    /**
     * The analytics dashboard for ONE form.
     *
     * Its own page rather than a tab on the Forms index, for the same reason the
     * builder is: it needs a form id before it can ask for anything, the URL has
     * to be linkable ("look at the contact form's drop-off"), and a dashboard
     * inside IndexCard's fly-in transform would have every chart measure itself
     * in a transformed box.
     *
     * ONE request feeds the whole page. Not useIndex — there is no list here, no
     * pagination and no sort, and useIndex's 20-second poll would rebuild eight
     * charts three times a minute. Plain `api.get` behind a last-key compare, so
     * the fetch happens when the form or the filters actually change.
     *
     * Every chart degrades to an empty state rather than to an axis of NaN: a
     * form nobody has filled in yet is the normal first state of this page, not
     * an error.
     */
    import AdminLayout from '@/layouts/AdminLayout.svelte';
    import Card from '@/components/ui/Card.svelte';
    import Alert from '@/components/feedback/Alert.svelte';
    import Badge from '@/components/ui/Badge.svelte';
    import Spinner from '@/components/ui/Spinner.svelte';
    import StatTile from '@/components/ui/StatTile.svelte';
    import ClampText from '@/components/ui/ClampText.svelte';
    import DatePicker from '@/components/form/DatePicker.svelte';
    import ApexChart from '@/components/charts/ApexChart.svelte';
    import { api } from '@/lib/api/client';
    import { toast } from '@/lib/toast';
    import { hasPermission } from '@/lib/permissions';
    import { FORM_STATUS_LABELS, FORM_STATUS_VARIANTS, SUBMISSION_STATUS_LABELS } from '@/lib/form';
    import {
        CHART_SERIES,
        CHART_STATUS,
        formatDuration,
        formatMs,
        formatNumber,
        formatPercent,
        isDarkTheme,
        watchTheme,
    } from '@/lib/charts';
    import { router } from '@inertiajs/svelte';
    import { untrack } from 'svelte';

    let { formId = null } = $props();

    let data = $state(null);
    let loading = $state(true);
    let failed = $state(null);

    // The palette is picked per mode, and the page (not just the charts) reads
    // it for the legend swatches and the table bars.
    let dark = $state(isDarkTheme());
    $effect(() => watchTheme((next) => (dark = next)));

    const series = $derived(dark ? CHART_SERIES.dark : CHART_SERIES.light);

    const PRESETS = [
        { id: 'all', label: 'All time', days: null },
        { id: '7', label: '7 days', days: 7 },
        { id: '30', label: '30 days', days: 30 },
        { id: '90', label: '90 days', days: 90 },
        { id: 'custom', label: 'Custom', days: null },
    ];

    /**
     * THE VIEW IS THE QUERY STRING. The page is linkable by form id alone, but
     * the range and the outcome filter are the half of "look at this" that
     * matters — a link that lands on All time is a link to a different chart.
     * So they are read from the URL here and written back on every change: the
     * copy-link button hands over what is on screen, and a refresh keeps it.
     *
     * Anything unrecognised falls back to the default rather than being trusted
     * through to a filter — the endpoint 422s an unknown status, and a hand-
     * edited URL should show the dashboard, not an error.
     */
    function readView() {
        const search = typeof window === 'undefined' ? '' : window.location.search;
        const params = new URLSearchParams(search);
        const wantedPreset = params.get('preset');
        const wantedStatus = params.get('status') ?? '';
        const from = params.get('from') ?? '';
        const to = params.get('to') ?? '';

        return {
            preset: PRESETS.some((p) => p.id === wantedPreset) ? wantedPreset : 'all',
            range: from && to ? [from, to] : [],
            // Object.keys rather than `in`: `in` walks the prototype, so
            // ?status=constructor would sail through and 422 the endpoint.
            status: Object.keys(SUBMISSION_STATUS_LABELS).includes(wantedStatus) ? wantedStatus : '',
        };
    }

    const initial = readView();

    // Filters. `all` is the default because a form-scoped dashboard is usually
    // opened to ask "how is this form doing", not "how was it doing last week",
    // and a 30-day default silently hides every form that ran a campaign.
    let preset = $state(initial.preset);
    let customRange = $state(initial.range);
    let status = $state(initial.status);

    /** Y-m-d, because the endpoint takes a date and applies the day's edges itself. */
    function ymd(date) {
        return [
            date.getFullYear(),
            String(date.getMonth() + 1).padStart(2, '0'),
            String(date.getDate()).padStart(2, '0'),
        ].join('-');
    }

    const range = $derived.by(() => {
        if (preset === 'custom') {
            return { from: customRange?.[0] ?? '', to: customRange?.[1] ?? '' };
        }

        const days = PRESETS.find((p) => p.id === preset)?.days;

        if (!days) return { from: '', to: '' };

        const to = new Date();
        const from = new Date();
        from.setDate(from.getDate() - (days - 1));

        return { from: ymd(from), to: ymd(to) };
    });

    /** Set or drop, so a value left at its default never shows up in the URL. */
    function setParam(params, key, value) {
        if (value) params.set(key, value);
        else params.delete(key);
    }

    /*
     * The other half of readView(). `replaceState` rather than an Inertia visit:
     * this is the same page asking the same endpoint, so pushing a history entry
     * per tab click would turn Back into "undo one filter" for a whole session.
     * Inertia's own history state is handed straight back, or its back/forward
     * handling loses the page it was tracking.
     *
     * `from`/`to` are only written for the custom preset — a preset's range is
     * relative, and freezing today's dates into the link would make a "7 days"
     * bookmark mean last week forever.
     */
    $effect(() => {
        const params = new URLSearchParams(window.location.search);
        const custom = preset === 'custom';

        setParam(params, 'preset', preset === 'all' ? '' : preset);
        setParam(params, 'from', custom ? (customRange?.[0] ?? '') : '');
        setParam(params, 'to', custom ? (customRange?.[1] ?? '') : '');
        setParam(params, 'status', status);

        const query = params.toString();

        window.history.replaceState(
            window.history.state,
            '',
            `${window.location.pathname}${query ? `?${query}` : ''}`,
        );
    });

    /*
     * Plain last-key compare, not the effect's own dependency graph: `range` is
     * a fresh object on every read, so an effect that merely touched it would
     * refetch on any unrelated state change.
     */
    let lastKey = null;

    $effect(() => {
        const key = `${formId}|${range.from}|${range.to}|${status}`;

        if (!formId || key === lastKey) return;
        lastKey = key;

        untrack(() => load());
    });

    /*
     * Plain, not $state: two quick preset clicks are two requests in flight, and
     * whichever answers last must win — not whichever the network happens to
     * finish second. Tracking the token would also re-run the effect that bumps
     * it. Same shape as ReorderDrawer's loadToken.
     */
    let loadToken = 0;

    async function load() {
        const mine = ++loadToken;

        loading = true;
        failed = null;

        try {
            const next = await api.get(route('api.v1.admin.forms.analytics', formId), {
                filter: {
                    created_from: range.from || undefined,
                    created_to: range.to || undefined,
                    status: status || undefined,
                },
            });

            if (mine !== loadToken) return;
            data = next;
        } catch (e) {
            if (mine !== loadToken) return;
            failed = e?.message ?? 'These numbers could not be loaded.';
            data = null;
        } finally {
            // A superseded request leaves the spinner alone: the one that
            // replaced it is still running and owns it.
            if (mine === loadToken) loading = false;
        }
    }

    function refresh() {
        lastKey = null;
        load();
    }

    /* ------------------------------------------------------------------ */
    /* Derived views of the payload                                        */
    /* ------------------------------------------------------------------ */

    const totals = $derived(data?.totals ?? null);
    const form = $derived(data?.form ?? null);
    const hasData = $derived((totals?.starts ?? 0) > 0);
    const fields = $derived(data?.fields ?? []);
    const timeline = $derived(data?.timeline ?? []);
    const devices = $derived(data?.devices ?? []);
    const referrers = $derived(data?.referrers ?? []);
    const utm = $derived(data?.utm_sources ?? []);
    const steps = $derived(data?.steps ?? []);
    const honeypot = $derived(data?.honeypot ?? []);
    const topAbandoned = $derived(data?.top_abandoned_field ?? null);

    const bucketLabel = $derived(
        timeline[0]?.unit === 'month' ? 'month' : timeline[0]?.unit === 'week' ? 'week' : 'day',
    );

    // The stack sums to `starts`, so the four series are the whole population
    // split by outcome — no series is a subset of another.
    const timelineSeries = $derived([
        { name: 'Completed', data: timeline.map((r) => r.completed) },
        {
            name: 'In progress',
            data: timeline.map((r) => Math.max(0, r.starts - r.completed - r.abandoned - r.rejected)),
        },
        { name: 'Abandoned', data: timeline.map((r) => r.abandoned) },
        { name: 'Spam / failed', data: timeline.map((r) => r.rejected) },
    ]);

    const timelineOptions = $derived({
        chart: { stacked: true },
        colors: [CHART_STATUS.good, series.violet, CHART_STATUS.warning, CHART_STATUS.critical],
        plotOptions: {
            bar: {
                columnWidth: timeline.length > 30 ? '90%' : '55%',
                borderRadius: 3,
                borderRadiusApplication: 'end',
                borderRadiusWhenStacked: 'last',
            },
        },
        // A 2px transparent ring is the surface gap between stacked segments —
        // it lets the card show through instead of painting a colour that would
        // have to be kept in step with the theme.
        stroke: { show: true, width: 2, colors: ['transparent'] },
        xaxis: {
            categories: timeline.map((r) => r.date),
            tickAmount: Math.min(10, Math.max(1, timeline.length)),
            labels: { rotate: 0, hideOverlappingLabels: true, formatter: (v) => shortDate(v) },
        },
        yaxis: { min: 0, forceNiceScale: true, labels: { formatter: (v) => String(Math.round(v)) } },
        tooltip: { shared: true, intersect: false, x: { formatter: (_, o) => longDate(timeline[o?.dataPointIndex]?.date) } },
    });

    // Reached and abandoned share one axis (both are visit counts), so they are
    // two series on one chart rather than the two-scale chart that would need.
    const fieldSeries = $derived([
        { name: 'Reached the field', data: fields.map((f) => f.reached) },
        { name: 'Left here', data: fields.map((f) => f.abandoned) },
    ]);

    const errorSeries = $derived([{ name: 'Validation failures', data: fields.map((f) => f.errors) }]);

    const fieldOptions = $derived({
        colors: [series.blue, CHART_STATUS.critical],
        plotOptions: { bar: { horizontal: true, barHeight: '62%', borderRadius: 3, borderRadiusApplication: 'end' } },
        stroke: { show: true, width: 2, colors: ['transparent'] },
        xaxis: { categories: fields.map((f) => f.label), labels: { formatter: (v) => String(Math.round(v)) } },
        yaxis: { labels: { maxWidth: 200 } },
        grid: { xaxis: { lines: { show: true } }, yaxis: { lines: { show: false } } },
    });

    const errorOptions = $derived({ ...fieldOptions, colors: [CHART_STATUS.critical], legend: { show: false } });

    /*
     * The timeline's table view is not decoration: the stack uses the fixed
     * status colours, and one of them (the amber "abandoned") sits under 3:1 on
     * a light surface. A status colour is allowed to, provided it never carries
     * meaning alone — the legend labels it and this table states it outright.
     */
    let timelineView = $state('chart');

    let fieldView = $state('dropoff');

    const fieldChartHeight = $derived(Math.max(220, fields.length * 44 + 60));

    const deviceSeries = $derived([{ name: 'Visits', data: devices.map((d) => d.starts) }]);

    const deviceOptions = $derived({
        colors: [series.blue],
        legend: { show: false },
        plotOptions: { bar: { horizontal: true, barHeight: '55%', borderRadius: 3, borderRadiusApplication: 'end' } },
        xaxis: { categories: devices.map((d) => deviceLabel(d.device_type)), labels: { formatter: (v) => String(Math.round(v)) } },
        grid: { xaxis: { lines: { show: true } }, yaxis: { lines: { show: false } } },
    });

    let trafficView = $state('referrers');

    const trafficRows = $derived(trafficView === 'referrers' ? referrers : utm);

    const trafficSeries = $derived([{ name: 'Visits', data: trafficRows.map((r) => r.starts) }]);

    const trafficOptions = $derived({
        colors: [series.blue],
        legend: { show: false },
        plotOptions: { bar: { horizontal: true, barHeight: '55%', borderRadius: 3, borderRadiusApplication: 'end' } },
        xaxis: {
            categories: trafficRows.map((r) => sourceLabel(r)),
            labels: { formatter: (v) => String(Math.round(v)) },
        },
        yaxis: { labels: { maxWidth: 180 } },
        grid: { xaxis: { lines: { show: true } }, yaxis: { lines: { show: false } } },
    });

    const measuredSteps = $derived(steps.filter((s) => s.hops > 0));

    const stepSeries = $derived([
        { name: 'Average time on page', data: measuredSteps.map((s) => Math.round((s.avg_ms ?? 0) / 100) / 10) },
    ]);

    const stepOptions = $derived({
        colors: [series.blue],
        legend: { show: false },
        plotOptions: { bar: { columnWidth: '45%', borderRadius: 3, borderRadiusApplication: 'end' } },
        xaxis: { categories: measuredSteps.map((s) => s.name) },
        yaxis: { labels: { formatter: (v) => formatDuration(v) } },
        tooltip: { y: { formatter: (v) => formatDuration(v) } },
    });

    // Two bars, one per honeypot outcome. `distributed` gives each its own
    // colour without inventing a second series for a single measure.
    const honeypotRows = $derived(
        [false, true].map((flag) => honeypot.find((h) => h.triggered === flag) ?? null),
    );

    const honeypotSeries = $derived([
        { name: 'Seconds to submit', data: honeypotRows.map((r) => r?.avg_seconds ?? 0) },
    ]);

    const honeypotOptions = $derived({
        colors: [series.blue, CHART_STATUS.critical],
        legend: { show: false },
        plotOptions: {
            bar: { horizontal: true, distributed: true, barHeight: '45%', borderRadius: 3, borderRadiusApplication: 'end' },
        },
        xaxis: { categories: ['Honeypot untouched', 'Honeypot tripped'], labels: { formatter: (v) => formatDuration(v) } },
        tooltip: { y: { formatter: (v) => formatDuration(v) } },
    });

    /* ------------------------------------------------------------------ */

    function shortDate(iso) {
        const date = new Date(`${iso}T00:00:00`);

        return Number.isNaN(date.getTime())
            ? iso
            : date.toLocaleDateString('en', { day: 'numeric', month: 'short' });
    }

    function longDate(iso) {
        if (!iso) return '';
        const date = new Date(`${iso}T00:00:00`);

        return Number.isNaN(date.getTime())
            ? iso
            : date.toLocaleDateString('en', { day: 'numeric', month: 'short', year: 'numeric' });
    }

    const DEVICE_LABELS = { desktop: 'Desktop', mobile: 'Mobile', tablet: 'Tablet', bot: 'Bot', unknown: 'Unknown' };

    function deviceLabel(value) {
        return DEVICE_LABELS[value] ?? value;
    }

    function sourceLabel(row) {
        if (trafficView === 'referrers') return row.source ?? 'Direct / none';

        return row.medium ? `${row.source} · ${row.medium}` : row.source;
    }

    const statusOptions = [
        { value: '', label: 'All outcomes' },
        ...Object.entries(SUBMISSION_STATUS_LABELS).map(([value, label]) => ({ value, label })),
    ];

    const canSeeSubmissions = $derived(hasPermission('form-submissions.index') && route().has('web.admin.form-submissions.index'));

    function openSubmissions() {
        router.visit(route('web.admin.form-submissions.index', { 'filter[form_id]': formId }));
    }

    function copyLink() {
        navigator.clipboard
            ?.writeText(window.location.href)
            .then(() => toast.success('Link copied.'))
            .catch(() => toast.error('Could not copy the link.'));
    }
</script>

<svelte:head><title>Saud International Schools — {form?.name ?? 'Form analytics'}</title></svelte:head>

<AdminLayout
    title={form ? `${form.name} — analytics` : 'Form analytics'}
    breadcrumbs={[
        { label: 'Forms', href: route('web.admin.forms.index') },
        { label: form?.name ?? 'Form' },
        { label: 'Analytics' },
    ]}
>
    <!-- Toolbar -->
    <div class="mb-4 flex flex-wrap items-center gap-3 rounded-xl border border-border p-3">
        <button class="kt-btn kt-btn-sm kt-btn-secondary" onclick={() => router.visit(route('web.admin.forms.index'))}>
            <i class="ki-filled ki-black-left"></i>Forms
        </button>

        {#if form}
            <Badge variant={FORM_STATUS_VARIANTS[form.status] ?? 'secondary'}>
                {FORM_STATUS_LABELS[form.status] ?? form.status}
            </Badge>
        {/if}

        <div class="kt-tabs kt-tabs-line" role="tablist" aria-label="Date range">
            {#each PRESETS as p (p.id)}
                <button
                    type="button"
                    role="tab"
                    data-kt-tab-toggle
                    class="kt-tab-toggle {preset === p.id ? 'active' : ''}"
                    aria-selected={preset === p.id}
                    onclick={() => (preset = p.id)}
                >{p.label}</button>
            {/each}
        </div>

        {#if preset === 'custom'}
            <div class="w-[240px]">
                <DatePicker mode="range" placeholder="Pick two dates" bind:value={customRange} />
            </div>
        {/if}

        <select class="kt-select w-[170px]" value={status} onchange={(e) => (status = e.currentTarget.value)}>
            {#each statusOptions as option (option.value)}
                <option value={option.value}>{option.label}</option>
            {/each}
        </select>

        <div class="ms-auto flex items-center gap-2">
            {#if loading}<Spinner size="sm" class="text-muted-foreground" />{/if}

            {#if hasPermission('form-fields.index')}
                <button class="kt-btn kt-btn-sm kt-btn-secondary" onclick={() => router.visit(route('web.admin.forms.builder', formId))}>
                    <i class="ki-filled ki-element-plus"></i>Build
                </button>
            {/if}
            {#if canSeeSubmissions}
                <button class="kt-btn kt-btn-sm kt-btn-secondary" onclick={openSubmissions}>
                    <i class="ki-filled ki-questionnaire-tablet"></i>Submissions
                </button>
            {/if}
            <button class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" title="Copy a link to this view" onclick={copyLink}>
                <i class="ki-filled ki-copy"></i>
            </button>
            <button class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" title="Reload" onclick={refresh}>
                <i class="ki-filled ki-arrows-circle"></i>
            </button>
        </div>
    </div>

    {#if failed}
        <Alert variant="destructive">{failed}</Alert>
    {/if}

    {#if loading && !data}
        <div class="flex items-center justify-center gap-2 py-20 text-sm text-muted-foreground">
            <Spinner size="sm" /> Reading the numbers…
        </div>
    {:else if data}
        <!-- Tiles -->
        <div class="mb-4 grid grid-cols-2 gap-4 lg:grid-cols-4">
            {@render tile('Starts', formatNumber(totals.starts), 'Visits that reached the form', 'ki-cursor')}
            {@render tile('Completions', formatNumber(totals.completed), `${formatNumber(totals.abandoned)} abandoned`, 'ki-check-circle')}
            {@render tile('Completion rate', formatPercent(totals.completion_rate), `${formatNumber(totals.in_progress)} still open`, 'ki-percentage')}
            {@render tile('Avg fill time', formatDuration(totals.avg_duration_seconds), 'Completed submissions only', 'ki-timer')}
            <!-- Wall clock above, hands on the keyboard here: the gap between the
                 two is how long the form was open but not being filled in. -->
            {@render tile('Active time', formatMs(totals.avg_fill_ms), 'First interaction to last', 'ki-keyboard')}
            {@render tile('Scroll depth', formatPercent(totals.avg_scroll_depth, 0), 'How far down the page they got', 'ki-scroll')}
            {@render tile('Spam', formatNumber(totals.spam), `${formatNumber(totals.honeypot)} tripped the honeypot`, 'ki-shield-cross')}
            {@render tile(
                'Top drop-off',
                topAbandoned?.label ?? '—',
                topAbandoned ? `${formatNumber(topAbandoned.abandoned)} left here (${formatPercent(topAbandoned.drop_off_rate)})` : 'Nobody has stopped on a field',
                'ki-exit-down',
                true,
            )}
        </div>

        {#if !hasData}
            <Card>
                <div class="flex flex-col items-center justify-center gap-3 py-12 text-center">
                    <i class="ki-filled ki-chart-line-up text-4xl text-muted-foreground"></i>
                    <h3 class="text-base font-semibold text-mono">Nothing measured yet</h3>
                    <p class="max-w-md text-sm text-secondary-foreground">
                        No visit matched this range. Once the form is published and somebody opens it, every chart
                        here fills in — the page beacons what it measures while the visitor is still on it.
                    </p>
                </div>
            </Card>
        {:else}
            <div class="flex flex-col gap-4">
                <!-- Outcomes over time -->
                <Card>
                    {#snippet header()}
                        <div class="flex flex-col gap-0.5">
                            <h3 class="kt-card-title">Submissions over time</h3>
                            <span class="text-2sm text-secondary-foreground">Every visit, by outcome, per {bucketLabel}</span>
                        </div>
                        <div class="kt-tabs kt-tabs-line" role="tablist">
                            <button type="button" role="tab" data-kt-tab-toggle class="kt-tab-toggle {timelineView === 'chart' ? 'active' : ''}" onclick={() => (timelineView = 'chart')}>Chart</button>
                            <button type="button" role="tab" data-kt-tab-toggle class="kt-tab-toggle {timelineView === 'table' ? 'active' : ''}" onclick={() => (timelineView = 'table')}>Table</button>
                        </div>
                    {/snippet}

                    {#if timelineView === 'chart'}
                        <ApexChart
                            type="bar"
                            series={timelineSeries}
                            options={timelineOptions}
                            height={320}
                            empty={!timeline.length}
                            emptyTitle="No visits in this range"
                            emptyBody="Widen the date range, or clear the outcome filter."
                        />
                    {:else}
                        <div class="kt-scrollable-x-auto max-h-[360px] overflow-y-auto">
                            <table class="kt-table kt-table-auto kt-table-border text-sm">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th class="text-end">Starts</th>
                                        <th class="text-end">Completed</th>
                                        <th class="text-end">In progress</th>
                                        <th class="text-end">Abandoned</th>
                                        <th class="text-end">Spam / failed</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {#each timeline as row (row.date)}
                                        <tr>
                                            <td class="whitespace-nowrap">{longDate(row.date)}</td>
                                            <td class="text-end tabular-nums">{formatNumber(row.starts)}</td>
                                            <td class="text-end tabular-nums">{formatNumber(row.completed)}</td>
                                            <td class="text-end tabular-nums">{formatNumber(Math.max(0, row.starts - row.completed - row.abandoned - row.rejected))}</td>
                                            <td class="text-end tabular-nums">{formatNumber(row.abandoned)}</td>
                                            <td class="text-end tabular-nums">{formatNumber(row.rejected)}</td>
                                        </tr>
                                    {/each}
                                </tbody>
                            </table>
                        </div>
                    {/if}
                </Card>

                <!-- Drop-off by field: the headline -->
                <Card>
                    {#snippet header()}
                        <div class="flex flex-col gap-0.5">
                            <h3 class="kt-card-title">Where the form loses people</h3>
                            <span class="text-2sm text-secondary-foreground">
                                Per field, across every visit — measured while the visitor was still on the page
                            </span>
                        </div>
                        <div class="kt-tabs kt-tabs-line" role="tablist">
                            <button type="button" role="tab" data-kt-tab-toggle class="kt-tab-toggle {fieldView === 'dropoff' ? 'active' : ''}" onclick={() => (fieldView = 'dropoff')}>Drop-off</button>
                            <button type="button" role="tab" data-kt-tab-toggle class="kt-tab-toggle {fieldView === 'errors' ? 'active' : ''}" onclick={() => (fieldView = 'errors')}>Validation errors</button>
                        </div>
                    {/snippet}

                    {#if fieldView === 'dropoff'}
                        <ApexChart
                            type="bar"
                            series={fieldSeries}
                            options={fieldOptions}
                            height={fieldChartHeight}
                            empty={!fields.length}
                            emptyTitle="No field data yet"
                            emptyBody="Per-field numbers arrive with the page's telemetry beacon; a form with no inputs has none."
                        />
                    {:else}
                        <ApexChart
                            type="bar"
                            series={errorSeries}
                            options={errorOptions}
                            height={fieldChartHeight}
                            empty={!fields.length}
                            emptyTitle="No field data yet"
                            emptyBody="A field appears here once a submission was rejected because of it."
                        />
                    {/if}

                    <div class="kt-scrollable-x-auto mt-4">
                        <table class="kt-table kt-table-auto kt-table-border text-sm">
                            <thead>
                                <tr>
                                    <th>Field</th>
                                    <th>Page</th>
                                    <th class="text-end">Reached</th>
                                    <th class="text-end">Left here</th>
                                    <th class="text-end">Drop-off</th>
                                    <th class="text-end">Errors</th>
                                    <th class="text-end">Error rate</th>
                                    <th class="text-end">Avg time</th>
                                    <th class="text-end">Revisits</th>
                                    <th class="text-end">Corrections</th>
                                </tr>
                            </thead>
                            <tbody>
                                {#each fields as field (field.key)}
                                    <tr>
                                        <td>
                                            <div class="flex items-center gap-2">
                                                <ClampText value={field.label} maxWidth="220px" title={field.label} />
                                                {#if field.is_removed}
                                                    <Badge variant="secondary" size="sm">Removed</Badge>
                                                {/if}
                                            </div>
                                        </td>
                                        <td class="text-secondary-foreground">{field.page ?? '—'}</td>
                                        <td class="text-end tabular-nums">{formatNumber(field.reached)}</td>
                                        <td class="text-end tabular-nums">{formatNumber(field.abandoned)}</td>
                                        <td class="text-end tabular-nums">
                                            {#if field.drop_off_rate !== null && field.drop_off_rate > 0}
                                                <Badge variant={field.drop_off_rate >= 25 ? 'destructive' : field.drop_off_rate >= 10 ? 'warning' : 'secondary'} size="sm">
                                                    {formatPercent(field.drop_off_rate)}
                                                </Badge>
                                            {:else}
                                                <span class="text-muted-foreground">—</span>
                                            {/if}
                                        </td>
                                        <td class="text-end tabular-nums">{formatNumber(field.errors)}</td>
                                        <td class="text-end tabular-nums">{formatPercent(field.error_rate)}</td>
                                        <td class="text-end tabular-nums">{formatMs(field.avg_focus_ms)}</td>
                                        <td class="text-end tabular-nums">{field.avg_revisits ?? '—'}</td>
                                        <td class="text-end tabular-nums">{formatNumber(field.corrections)}</td>
                                    </tr>
                                {/each}
                            </tbody>
                        </table>
                    </div>
                </Card>

                <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                    <!-- Device split + fill duration by device -->
                    <Card>
                        {#snippet header()}
                            <div class="flex flex-col gap-0.5">
                                <h3 class="kt-card-title">Devices</h3>
                                <span class="text-2sm text-secondary-foreground">How they arrived, and how long it took them</span>
                            </div>
                        {/snippet}

                        <ApexChart
                            type="bar"
                            series={deviceSeries}
                            options={deviceOptions}
                            height={Math.max(180, devices.length * 52 + 40)}
                            empty={!devices.length}
                            emptyTitle="No device data yet"
                        />

                        <div class="kt-scrollable-x-auto mt-4">
                            <table class="kt-table kt-table-auto kt-table-border text-sm">
                                <thead>
                                    <tr>
                                        <th>Device</th>
                                        <th class="text-end">Visits</th>
                                        <th class="text-end">Completed</th>
                                        <th class="text-end">Rate</th>
                                        <th class="text-end">Avg fill time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {#each devices as device (device.device_type)}
                                        <tr>
                                            <td>{deviceLabel(device.device_type)}</td>
                                            <td class="text-end tabular-nums">{formatNumber(device.starts)}</td>
                                            <td class="text-end tabular-nums">{formatNumber(device.completed)}</td>
                                            <td class="text-end tabular-nums">{formatPercent(device.completion_rate)}</td>
                                            <td class="text-end tabular-nums">{formatDuration(device.avg_duration_seconds)}</td>
                                        </tr>
                                    {/each}
                                </tbody>
                            </table>
                        </div>
                    </Card>

                    <!-- Traffic sources + referrer x completion rate -->
                    <Card>
                        {#snippet header()}
                            <div class="flex flex-col gap-0.5">
                                <h3 class="kt-card-title">Where they came from</h3>
                                <span class="text-2sm text-secondary-foreground">Referring host, and the campaign tags on the link</span>
                            </div>
                            <div class="kt-tabs kt-tabs-line" role="tablist">
                                <button type="button" role="tab" data-kt-tab-toggle class="kt-tab-toggle {trafficView === 'referrers' ? 'active' : ''}" onclick={() => (trafficView = 'referrers')}>Referrers</button>
                                <button type="button" role="tab" data-kt-tab-toggle class="kt-tab-toggle {trafficView === 'utm' ? 'active' : ''}" onclick={() => (trafficView = 'utm')}>UTM</button>
                            </div>
                        {/snippet}

                        <ApexChart
                            type="bar"
                            series={trafficSeries}
                            options={trafficOptions}
                            height={Math.max(180, trafficRows.length * 44 + 40)}
                            empty={!trafficRows.length}
                            emptyTitle={trafficView === 'referrers' ? 'No referrers recorded' : 'No campaign tags recorded'}
                            emptyBody={trafficView === 'referrers'
                                ? 'Visitors reached this form directly, or their browser sent no referrer.'
                                : 'Add utm_source to the links you share and they show up here.'}
                        />

                        <div class="kt-scrollable-x-auto mt-4">
                            <table class="kt-table kt-table-auto kt-table-border text-sm">
                                <thead>
                                    <tr>
                                        <th>{trafficView === 'referrers' ? 'Source' : 'Campaign'}</th>
                                        <th class="text-end">Visits</th>
                                        <th class="text-end">Completed</th>
                                        <th class="text-end">Rate</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {#each trafficRows as row, index (sourceLabel(row) + index)}
                                        <tr>
                                            <td><ClampText value={sourceLabel(row)} maxWidth="240px" title={sourceLabel(row)} /></td>
                                            <td class="text-end tabular-nums">{formatNumber(row.starts)}</td>
                                            <td class="text-end tabular-nums">{formatNumber(row.completed)}</td>
                                            <td class="text-end tabular-nums">{formatPercent(row.completion_rate)}</td>
                                        </tr>
                                    {/each}
                                </tbody>
                            </table>
                        </div>
                    </Card>

                    <!-- Time per step -->
                    {#if steps.length}
                        <Card>
                            {#snippet header()}
                                <div class="flex flex-col gap-0.5">
                                    <h3 class="kt-card-title">Time per page</h3>
                                    <span class="text-2sm text-secondary-foreground">
                                        Measured between page changes — the page a visitor was on when they left is not counted
                                    </span>
                                </div>
                            {/snippet}

                            <ApexChart
                                type="bar"
                                series={stepSeries}
                                options={stepOptions}
                                height={260}
                                empty={!measuredSteps.length}
                                emptyTitle="No page changes recorded"
                                emptyBody="Nobody has moved between this form's pages yet."
                            />
                        </Card>
                    {/if}

                    <!-- Time to submit x honeypot -->
                    <Card>
                        {#snippet header()}
                            <div class="flex flex-col gap-0.5">
                                <h3 class="kt-card-title">Time to submit vs the spam trap</h3>
                                <span class="text-2sm text-secondary-foreground">
                                    {form?.is_spam_filtered
                                        ? `Anything under ${form.min_submit_seconds}s is filed as spam on this form`
                                        : 'The spam filter is off for this form'}
                                </span>
                            </div>
                        {/snippet}

                        <ApexChart
                            type="bar"
                            series={honeypotSeries}
                            options={honeypotOptions}
                            height={220}
                            empty={!honeypot.length}
                            emptyTitle="Nothing submitted yet"
                            emptyBody="This compares how fast real submissions arrive against the ones that filled the hidden field."
                        />

                        <div class="kt-scrollable-x-auto mt-4">
                            <table class="kt-table kt-table-auto kt-table-border text-sm">
                                <thead>
                                    <tr>
                                        <th>Group</th>
                                        <th class="text-end">Submissions</th>
                                        <th class="text-end">Avg time</th>
                                        <th class="text-end">Fastest</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {#each honeypotRows as row, index (index)}
                                        <tr>
                                            <td>
                                                {#if index === 1}
                                                    <span class="inline-flex items-center gap-1.5">
                                                        <i class="ki-filled ki-shield-cross text-destructive"></i>Honeypot tripped
                                                    </span>
                                                {:else}
                                                    Honeypot untouched
                                                {/if}
                                            </td>
                                            <td class="text-end tabular-nums">{formatNumber(row?.submissions ?? 0)}</td>
                                            <td class="text-end tabular-nums">{formatDuration(row?.avg_seconds)}</td>
                                            <td class="text-end tabular-nums">{formatDuration(row?.min_seconds)}</td>
                                        </tr>
                                    {/each}
                                </tbody>
                            </table>
                        </div>
                    </Card>
                </div>
            </div>
        {/if}
    {/if}
</AdminLayout>

{#snippet tile(label, value, hint, icon, clamp = false)}
    <!-- The markup moved to components/ui/StatTile.svelte when the dashboard
         became its second consumer. Kept as a snippet so the call sites below
         read the same as they always did. -->
    <StatTile {label} {value} {hint} {icon} {clamp} />
{/snippet}

