<script>
    /**
     * Admin dashboard — the authenticated landing at `/admin`.
     *
     * TWO QUESTIONS, ONE REQUEST. What came in (submissions, applications,
     * bookings — read straight from the tables that already hold them) and how
     * busy the site is (from site_page_views, which this app writes itself).
     * Activity comes FIRST because what arrived matters more to a school
     * administrator than how many people browsed.
     *
     * Not useIndex — there is no list, no pagination and no sort, and its
     * 20-second poll would rebuild every chart three times a minute. Plain
     * `api.get` behind a last-key compare, exactly as Forms/Analytics does.
     *
     * DELIBERATELY SHALLOW. Google Analytics carries the deep reporting; this
     * exists so the numbers a school looks at daily are on the page they land
     * on. The footnote says so, so nobody tries to grow this into GA.
     */
    import AdminLayout from '@/layouts/AdminLayout.svelte';
    import PageHeader from '@/components/layout/PageHeader.svelte';
    import Card from '@/components/ui/Card.svelte';
    import Alert from '@/components/feedback/Alert.svelte';
    import Badge from '@/components/ui/Badge.svelte';
    import Spinner from '@/components/ui/Spinner.svelte';
    import StatTile from '@/components/ui/StatTile.svelte';
    import DatePicker from '@/components/form/DatePicker.svelte';
    import ApexChart from '@/components/charts/ApexChart.svelte';
    import { api } from '@/lib/api/client';
    import { authUser, hasPermission } from '@/lib/permissions';
    import { ACTIVITY_SECTIONS, contentLabel, contentType, entryPointLabel, localeLabel } from '@/lib/analytics';
    import { CHART_SERIES, formatNumber, isDarkTheme, watchTheme } from '@/lib/charts';
    import { untrack } from 'svelte';

    const user = authUser();

    /*
     * analytics.index gates the DATA; dashboards.index gates this page. An
     * editor who may reach their landing page but not read the school's numbers
     * still gets a working dashboard — and, importantly, fires NO request, or
     * they would eat a 403 toast on the first screen after signing in.
     */
    const canSeeMetrics = hasPermission('analytics.index');

    let data = $state(null);
    let loading = $state(canSeeMetrics);
    let failed = $state(null);

    let dark = $state(isDarkTheme());
    $effect(() => watchTheme((next) => (dark = next)));

    const series = $derived(dark ? CHART_SERIES.dark : CHART_SERIES.light);

    const PRESETS = [
        { id: '7', label: '7 days', days: 7 },
        { id: '30', label: '30 days', days: 30 },
        { id: '90', label: '90 days', days: 90 },
        { id: 'all', label: 'All time', days: null },
        { id: 'custom', label: 'Custom', days: null },
    ];

    /**
     * THE VIEW IS THE QUERY STRING, so a link hands over what is on screen.
     * Anything unrecognised falls back to the default rather than being trusted
     * through to a filter.
     */
    function readView() {
        const search = typeof window === 'undefined' ? '' : window.location.search;
        const params = new URLSearchParams(search);
        const wanted = params.get('preset');
        const from = params.get('from') ?? '';
        const to = params.get('to') ?? '';

        return {
            preset: PRESETS.some((p) => p.id === wanted) ? wanted : '30',
            range: from && to ? [from, to] : [],
        };
    }

    const initial = readView();

    /*
     * 30 days by default, NOT all time — deliberately different from the form
     * dashboard. A form page is opened to ask "how is this form doing overall";
     * this page is opened to ask "how are we doing now", and on a table kept for
     * a year "all time" is a week-bucketed chart that says nothing about this
     * week. Do not "fix" the inconsistency.
     */
    let preset = $state(initial.preset);
    let customRange = $state(initial.range);

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

    function setParam(params, key, value) {
        if (value) params.set(key, value);
        else params.delete(key);
    }

    /*
     * replaceState rather than an Inertia visit: this is the same page asking
     * the same endpoint, so a history entry per preset click would turn Back
     * into "undo one filter" for a whole session. from/to are written only for
     * the custom preset — freezing today's dates into a "7 days" link would make
     * that bookmark mean last week forever.
     */
    $effect(() => {
        const params = new URLSearchParams(window.location.search);
        const custom = preset === 'custom';

        setParam(params, 'preset', preset === '30' ? '' : preset);
        setParam(params, 'from', custom ? (customRange?.[0] ?? '') : '');
        setParam(params, 'to', custom ? (customRange?.[1] ?? '') : '');

        const query = params.toString();

        window.history.replaceState(
            window.history.state,
            '',
            `${window.location.pathname}${query ? `?${query}` : ''}`,
        );
    });

    /*
     * Plain last-key compare, not the effect's dependency graph: `range` is a
     * fresh object on every read, so an effect that merely touched it would
     * refetch on any unrelated state change. The permission check sits BEFORE
     * the compare, so an unprivileged admin never issues a request at all.
     */
    let lastKey = null;

    $effect(() => {
        if (!canSeeMetrics) return;

        const key = `${range.from}|${range.to}`;

        if (key === lastKey) return;
        lastKey = key;

        untrack(() => load());
    });

    /*
     * Plain, not $state: two quick preset clicks are two requests in flight and
     * whichever answers last must win, not whichever the network finishes
     * second. Tracking it would also re-run the effect that bumps it.
     */
    let loadToken = 0;

    async function load() {
        const mine = ++loadToken;

        loading = true;
        failed = null;

        try {
            const next = await api.get(route('api.v1.admin.analytics.dashboard'), {
                filter: {
                    created_from: range.from || undefined,
                    created_to: range.to || undefined,
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

    const traffic = $derived(data?.traffic ?? null);
    const totals = $derived(traffic?.totals ?? null);
    const timeline = $derived(traffic?.timeline ?? []);
    const topContent = $derived(traffic?.top_content ?? []);
    const entryPoints = $derived(traffic?.entry_points ?? []);
    const referrers = $derived(traffic?.referrers ?? []);
    const campaigns = $derived(traffic?.campaigns ?? []);
    const devices = $derived(traffic?.devices ?? []);
    const browsers = $derived(traffic?.browsers ?? []);
    const systems = $derived(traffic?.systems ?? []);
    const locales = $derived(traffic?.locales ?? []);
    const countries = $derived(traffic?.countries ?? []);
    const botViews = $derived(traffic?.bot_views ?? 0);
    const hasTraffic = $derived((totals?.page_views ?? 0) > 0);

    /*
     * A section the caller may not read is ABSENT from the payload, not empty —
     * gated server-side in DashboardMetrics — so presence alone drives what
     * renders and no permission is re-checked here.
     */
    const activitySections = $derived(
        ACTIVITY_SECTIONS.map((section) => ({ ...section, stats: data?.activity?.[section.key] ?? null })).filter(
            (section) => section.stats !== null,
        ),
    );

    const bucketLabel = $derived(
        timeline[0]?.unit === 'month' ? 'month' : timeline[0]?.unit === 'week' ? 'week' : 'day',
    );

    /* Chart / table toggles — the table view is an accessibility obligation,
       not decoration: it states in words what the bars encode in length. */
    let timelineView = $state('chart');
    let contentView = $state('table');
    let sourceView = $state('referrers');
    let deviceView = $state('browsers');

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

    const timelineSeries = $derived([
        { name: 'Page views', data: timeline.map((r) => r.page_views) },
        { name: 'Visits', data: timeline.map((r) => r.visits) },
    ]);

    const timelineOptions = $derived({
        colors: [series.blue, series.aqua],
        // Merged, not replaced: mergeOptions deep-merges objects, so this sits
        // alongside baseOptions' `fill.opacity` rather than dropping it.
        fill: { type: 'gradient', gradient: { opacityFrom: 0.35, opacityTo: 0, shadeIntensity: 1 } },
        xaxis: {
            categories: timeline.map((r) => r.date),
            tickAmount: Math.min(10, Math.max(1, timeline.length)),
            labels: { rotate: 0, hideOverlappingLabels: true, formatter: (v) => shortDate(v) },
        },
        yaxis: { min: 0, forceNiceScale: true, labels: { formatter: (v) => String(Math.round(v)) } },
        tooltip: { shared: true, intersect: false, x: { formatter: (_, o) => longDate(timeline[o?.dataPointIndex]?.date) } },
    });

    /*
     * ONE COLOUR PER BREAKDOWN, identity carried by the axis label.
     *
     * lib/charts ships FOUR validated categorical slots and states they are
     * assigned in order and never cycled. Entry points has six buckets and
     * countries ten, so a donut or a multi-colour bar would have to invent
     * colours outside the validated set — a palette change, and out of scope.
     */
    function barOptions(labels) {
        return {
            colors: [series.blue],
            legend: { show: false },
            plotOptions: { bar: { horizontal: true, barHeight: '55%', borderRadius: 3, borderRadiusApplication: 'end' } },
            xaxis: { categories: labels, labels: { formatter: (v) => String(Math.round(v)) } },
            yaxis: { labels: { maxWidth: 180 } },
            // Inverted from baseOptions: on a horizontal bar the value runs
            // along x, so the gridlines have to as well.
            grid: { xaxis: { lines: { show: true } }, yaxis: { lines: { show: false } } },
        };
    }

    function barHeight(rows) {
        return Math.max(180, rows.length * 44 + 40);
    }
</script>

<svelte:head>
    <title>Saud International Schools</title>
</svelte:head>

<AdminLayout breadcrumbs={[{ label: 'Dashboard' }]}>
    <PageHeader
        title={`Welcome${user ? `, ${user.firstname ?? user.username}` : ''}`}
        subtitle={canSeeMetrics ? 'What came in, and how busy the site has been.' : 'Use the sidebar to navigate.'}
    />

    {#if !canSeeMetrics}
        <Card>
            <p class="text-sm text-secondary-foreground">
                Use the sidebar to navigate. Ask an administrator for the “View site analytics” permission
                to see traffic and activity here.
            </p>
        </Card>
    {:else}
        <!-- Toolbar -->
        <div class="mb-4 flex flex-wrap items-center gap-3 rounded-xl border border-border p-3">
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

            <div class="ms-auto flex items-center gap-2">
                {#if loading}<Spinner size="sm" />{/if}
                <button class="kt-btn kt-btn-sm kt-btn-secondary" onclick={refresh} disabled={loading}>
                    <i class="ki-filled ki-arrows-circle"></i>Refresh
                </button>
            </div>
        </div>

        {#if failed}
            <Alert variant="destructive" class="mb-4">{failed}</Alert>
        {/if}

        {#if data}
            <!-- What came in -->
            {#if activitySections.length}
                <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    {#each activitySections as section (section.key)}
                        <Card>
                            {#snippet header()}
                                <h3 class="kt-card-title">
                                    <i class="ki-filled {section.icon} me-2 text-muted-foreground"></i>{section.label}
                                </h3>
                                <span class="text-2xl font-semibold text-mono">{formatNumber(section.stats.total)}</span>
                            {/snippet}

                            {#if section.stats.statuses.length}
                                <ul class="flex flex-col gap-1.5">
                                    {#each section.stats.statuses as row (row.status)}
                                        <li class="flex items-center justify-between gap-2">
                                            <Badge variant={section.variants[row.status] ?? 'secondary'} size="sm">
                                                {section.labels[row.status] ?? row.status}
                                            </Badge>
                                            <span class="text-2sm font-medium tabular-nums text-mono">{formatNumber(row.total)}</span>
                                        </li>
                                    {/each}
                                </ul>
                            {:else}
                                <p class="text-2sm text-muted-foreground">Nothing in this range.</p>
                            {/if}
                        </Card>
                    {/each}
                </div>
            {/if}

            <!-- Traffic tiles -->
            <div class="mb-4 grid grid-cols-2 gap-4 lg:grid-cols-4">
                <StatTile label="Page views" value={formatNumber(totals.page_views)} hint="Every page opened" icon="ki-eye" />
                <StatTile label="Visits" value={formatNumber(totals.visits)} hint="Browsing sessions" icon="ki-cursor" />
                <StatTile
                    label="Visitors"
                    value={formatNumber(totals.visitors)}
                    hint="Approximate — by network and browser"
                    icon="ki-people"
                />
                <StatTile
                    label="Pages per visit"
                    value={totals.pages_per_visit ?? '—'}
                    hint="How far people read"
                    icon="ki-book-open"
                />
            </div>

            {#if !hasTraffic}
                <Card class="mb-4">
                    <div class="py-6 text-center">
                        <i class="ki-filled ki-chart-line-up mb-2 text-2xl text-muted-foreground"></i>
                        <p class="text-sm font-medium text-mono">No visits in this range</p>
                        <p class="text-2sm text-muted-foreground">
                            The site records one row per page view. Open a public page and it appears here.
                        </p>
                    </div>
                </Card>
            {:else}
                <!-- Traffic over time -->
                <Card class="mb-4">
                    {#snippet header()}
                        <div class="flex flex-col gap-0.5">
                            <h3 class="kt-card-title">Traffic over time</h3>
                            <span class="text-2sm text-secondary-foreground">Page views and visits per {bucketLabel}</span>
                        </div>
                        <div class="kt-tabs kt-tabs-line" role="tablist">
                            <button type="button" role="tab" data-kt-tab-toggle class="kt-tab-toggle {timelineView === 'chart' ? 'active' : ''}" onclick={() => (timelineView = 'chart')}>Chart</button>
                            <button type="button" role="tab" data-kt-tab-toggle class="kt-tab-toggle {timelineView === 'table' ? 'active' : ''}" onclick={() => (timelineView = 'table')}>Table</button>
                        </div>
                    {/snippet}

                    {#if timelineView === 'chart'}
                        <ApexChart type="area" series={timelineSeries} options={timelineOptions} height={300} empty={!timeline.length} />
                    {:else}
                        <div class="kt-scrollable-x-auto">
                            <table class="kt-table kt-table-auto kt-table-border text-sm">
                                <thead>
                                    <tr><th>Date</th><th class="text-end">Page views</th><th class="text-end">Visits</th></tr>
                                </thead>
                                <tbody>
                                    {#each timeline as row (row.date)}
                                        <tr>
                                            <td class="whitespace-nowrap">{longDate(row.date)}</td>
                                            <td class="text-end tabular-nums">{formatNumber(row.page_views)}</td>
                                            <td class="text-end tabular-nums">{formatNumber(row.visits)}</td>
                                        </tr>
                                    {/each}
                                </tbody>
                            </table>
                        </div>
                    {/if}
                </Card>

                <!-- Top content -->
                <Card class="mb-4">
                    {#snippet header()}
                        <div class="flex flex-col gap-0.5">
                            <h3 class="kt-card-title">Most read</h3>
                            <span class="text-2sm text-secondary-foreground">Articles, jobs, pages and venues, by page view</span>
                        </div>
                        <div class="kt-tabs kt-tabs-line" role="tablist">
                            <button type="button" role="tab" data-kt-tab-toggle class="kt-tab-toggle {contentView === 'table' ? 'active' : ''}" onclick={() => (contentView = 'table')}>Table</button>
                            <button type="button" role="tab" data-kt-tab-toggle class="kt-tab-toggle {contentView === 'chart' ? 'active' : ''}" onclick={() => (contentView = 'chart')}>Chart</button>
                        </div>
                    {/snippet}

                    {#if contentView === 'table'}
                        <div class="kt-scrollable-x-auto">
                            <table class="kt-table kt-table-auto kt-table-border text-sm">
                                <thead>
                                    <tr><th>Page</th><th>Type</th><th class="text-end">Page views</th><th class="text-end">Visits</th></tr>
                                </thead>
                                <tbody>
                                    {#each topContent as row (row.route_name + '|' + row.slug)}
                                        <tr>
                                            <td>
                                                <div class="flex flex-col gap-0.5">
                                                    <span class="font-medium text-mono">{contentLabel(row)}</span>
                                                    {#if row.url}
                                                        <a class="text-2sm text-muted-foreground hover:text-primary" href={row.url} target="_blank" rel="noopener noreferrer">{row.path}</a>
                                                    {:else}
                                                        <span class="text-2sm text-muted-foreground">{row.path}</span>
                                                    {/if}
                                                </div>
                                            </td>
                                            <td>
                                                {#if contentType(row)}
                                                    <Badge variant="secondary" size="sm">{contentType(row)}</Badge>
                                                {:else}
                                                    <span class="text-2sm text-muted-foreground">—</span>
                                                {/if}
                                            </td>
                                            <td class="text-end tabular-nums">{formatNumber(row.page_views)}</td>
                                            <td class="text-end tabular-nums">{formatNumber(row.visits)}</td>
                                        </tr>
                                    {/each}
                                </tbody>
                            </table>
                        </div>
                    {:else}
                        <ApexChart
                            type="bar"
                            series={[{ name: 'Page views', data: topContent.map((r) => r.page_views) }]}
                            options={barOptions(topContent.map((r) => contentLabel(r)))}
                            height={barHeight(topContent)}
                            empty={!topContent.length}
                        />
                    {/if}
                </Card>

                <!-- Sources -->
                <div class="mb-4 grid grid-cols-1 gap-4 xl:grid-cols-2">
                    <Card title="How people arrive">
                        <ApexChart
                            type="bar"
                            series={[{ name: 'Visits', data: entryPoints.map((r) => r.visits) }]}
                            options={barOptions(entryPoints.map((r) => entryPointLabel(r.label)))}
                            height={barHeight(entryPoints)}
                            empty={!entryPoints.length}
                        />
                    </Card>

                    <Card>
                        {#snippet header()}
                            <h3 class="kt-card-title">Referrers &amp; campaigns</h3>
                            <div class="kt-tabs kt-tabs-line" role="tablist">
                                <button type="button" role="tab" data-kt-tab-toggle class="kt-tab-toggle {sourceView === 'referrers' ? 'active' : ''}" onclick={() => (sourceView = 'referrers')}>Referrers</button>
                                <button type="button" role="tab" data-kt-tab-toggle class="kt-tab-toggle {sourceView === 'campaigns' ? 'active' : ''}" onclick={() => (sourceView = 'campaigns')}>Campaigns</button>
                            </div>
                        {/snippet}

                        {#if sourceView === 'referrers'}
                            <ApexChart
                                type="bar"
                                series={[{ name: 'Visits', data: referrers.map((r) => r.visits) }]}
                                options={barOptions(referrers.map((r) => r.label))}
                                height={barHeight(referrers)}
                                empty={!referrers.length}
                                emptyTitle="No referrers yet"
                                emptyBody="Direct traffic is shown in the chart beside this one."
                            />
                        {:else}
                            <ApexChart
                                type="bar"
                                series={[{ name: 'Visits', data: campaigns.map((r) => r.visits) }]}
                                options={barOptions(campaigns.map((r) => r.label))}
                                height={barHeight(campaigns)}
                                empty={!campaigns.length}
                                emptyTitle="No tagged campaigns"
                                emptyBody="Links carrying utm_campaign appear here."
                            />
                        {/if}
                    </Card>
                </div>

                <!-- Devices and reach -->
                <div class="mb-4 grid grid-cols-1 gap-4 xl:grid-cols-2">
                    <Card title="Devices">
                        <ApexChart
                            type="bar"
                            series={[{ name: 'Visits', data: devices.map((r) => r.visits) }]}
                            options={barOptions(devices.map((r) => r.label))}
                            height={barHeight(devices)}
                            empty={!devices.length}
                        />
                    </Card>

                    <Card>
                        {#snippet header()}
                            <h3 class="kt-card-title">Browsers &amp; systems</h3>
                            <div class="kt-tabs kt-tabs-line" role="tablist">
                                <button type="button" role="tab" data-kt-tab-toggle class="kt-tab-toggle {deviceView === 'browsers' ? 'active' : ''}" onclick={() => (deviceView = 'browsers')}>Browsers</button>
                                <button type="button" role="tab" data-kt-tab-toggle class="kt-tab-toggle {deviceView === 'systems' ? 'active' : ''}" onclick={() => (deviceView = 'systems')}>Systems</button>
                            </div>
                        {/snippet}

                        {#if deviceView === 'browsers'}
                            <ApexChart
                                type="bar"
                                series={[{ name: 'Visits', data: browsers.map((r) => r.visits) }]}
                                options={barOptions(browsers.map((r) => r.label))}
                                height={barHeight(browsers)}
                                empty={!browsers.length}
                            />
                        {:else}
                            <ApexChart
                                type="bar"
                                series={[{ name: 'Visits', data: systems.map((r) => r.visits) }]}
                                options={barOptions(systems.map((r) => r.label))}
                                height={barHeight(systems)}
                                empty={!systems.length}
                            />
                        {/if}
                    </Card>
                </div>

                <div class="mb-4 grid grid-cols-1 gap-4 xl:grid-cols-2">
                    <Card title="Countries">
                        <ApexChart
                            type="bar"
                            series={[{ name: 'Visits', data: countries.map((r) => r.visits) }]}
                            options={barOptions(countries.map((r) => r.name ?? r.code))}
                            height={barHeight(countries)}
                            empty={!countries.length}
                            emptyTitle="No country data"
                            emptyBody="Country needs either a CDN header or the local IP database — and is always empty in development, where every visit comes from localhost."
                        />
                    </Card>

                    <Card title="Languages">
                        <ApexChart
                            type="bar"
                            series={[{ name: 'Visits', data: locales.map((r) => r.visits) }]}
                            options={barOptions(locales.map((r) => localeLabel(r.label)))}
                            height={barHeight(locales)}
                            empty={!locales.length}
                        />
                    </Card>
                </div>
            {/if}

            <!--
                Load-bearing documentation, not a caption: it tells the next
                administrator why this dashboard is deliberately shallow, so
                nobody tries to grow it into Google Analytics.
            -->
            <p class="text-2sm text-muted-foreground">
                {formatNumber(botViews)} bot page view{botViews === 1 ? '' : 's'} excluded · kept for {data.retention_days} days ·
                IP geolocation by <a class="hover:text-primary" href="https://db-ip.com" target="_blank" rel="noopener noreferrer">DB-IP</a> ·
                Google Analytics carries the deeper reporting.
            </p>
        {:else if !loading}
            <Card>
                <p class="text-sm text-secondary-foreground">Nothing to show yet.</p>
            </Card>
        {/if}
    {/if}
</AdminLayout>
