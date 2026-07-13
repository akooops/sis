<script>
    import Flatpickr from '../../Shared/Utils/Forms/Flatpickr.svelte';
    import Select2 from '../../Shared/Utils/Forms/Select2.svelte';
    import Pagination from '../../Shared/Utils/Pagination.svelte';
    import { onDestroy, onMount, tick } from 'svelte';

    const segments = [
        { id: 'in_transit', label: 'In Transit', description: 'Sea & air shipments in transit' },
        { id: 'at_customs', label: 'At Customs', description: 'Sea & air shipments at customs' },
        { id: 'local', label: 'Local', description: 'Local shipments' },
    ];

    const chartColors = {
        primary: 'var(--color-primary)',
        green: '#22c55e',
        orange: '#f97316',
        violet: '#8b5cf6',
        yellow: '#eab308',
        red: '#ef4444',
        blue: '#3b82f6',
        slate: '#94a3b8',
    };

    const dateRangePresets = [
        { id: 'last_7_days', label: 'Last 7 days' },
        { id: 'last_28_days', label: 'Last 28 days' },
        { id: 'last_3_months', label: 'Last 3 months' },
        { id: 'last_6_months', label: 'Last 6 months' },
        { id: 'last_12_months', label: 'Last 12 months' },
        { id: 'lifetime', label: 'Lifetime' },
        { id: 'custom', label: 'Custom' },
    ];

    function getDateStr(date) {
        return date.toISOString().split('T')[0];
    }

    function computePresetDates(preset) {
        const end = new Date();
        const start = new Date();

        switch (preset) {
            case 'last_7_days':
                start.setDate(end.getDate() - 6);
                break;
            case 'last_28_days':
                start.setDate(end.getDate() - 27);
                break;
            case 'last_3_months':
                start.setMonth(end.getMonth() - 3);
                break;
            case 'last_6_months':
                start.setMonth(end.getMonth() - 6);
                break;
            case 'last_12_months':
                start.setFullYear(end.getFullYear() - 1);
                break;
            default:
                return null;
        }

        return {
            from: getDateStr(start),
            to: getDateStr(end),
        };
    }

    const defaultPreset = 'last_28_days';
    const defaultDates = computePresetDates(defaultPreset);

    let productionSiteId = '';
    let segment = 'in_transit';
    let timeliness = '';
    let dateRangePreset = defaultPreset;
    let from = defaultDates.from;
    let to = defaultDates.to;

    let stats = {
        quantity_purchased: 0,
        quantity_shipped: 0,
        quantity_received: 0,
        quantity_approved: 0,
        quantity_released: 0,
        total_shipments: 0,
        active_shipments: 0,
        on_time_rate: 0,
    };
    let pipeline = {
        in_transit: { total: 0, on_time: 0, off_time: 0 },
        at_customs: { total: 0, on_time: 0, off_time: 0 },
        local: { total: 0, on_time: 0, off_time: 0 },
    };
    let shippingsOverTime = [];
    let shippingsOverTimeGrouping = 'day';
    let transportModes = [];
    let statusBreakdown = [];
    let topSuppliers = [];
    let topCarriers = [];
    let topProducts = [];

    let shippings = [];
    let pagination = {};
    let overviewLoading = true;
    let tableLoading = true;
    let currentPage = 1;
    let perPage = 10;

    let siteSelectComponent;
    let overTimeChartEl;
    let transportChartEl;
    let statusChartEl;
    let suppliersChartEl;
    let carriersChartEl;
    let productsChartEl;

    let overTimeChart = null;
    let transportChart = null;
    let statusChart = null;
    let suppliersChart = null;
    let carriersChart = null;
    let productsChart = null;

    function buildFilterParams() {
        const params = {};

        if (productionSiteId) {
            params.production_site_id = productionSiteId;
        }

        if (dateRangePreset !== 'lifetime' && from && to) {
            params.from = from;
            params.to = to;
        }

        return params;
    }

    function buildTableParams() {
        return {
            ...buildFilterParams(),
            page: currentPage,
            per_page: perPage,
            segment,
            ...(timeliness ? { timeliness } : {}),
        };
    }

    async function apiFetch(routeName, params = {}) {
        const response = await fetch(route(routeName, params), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });

        return response.json();
    }

    async function fetchOverview() {
        overviewLoading = true;

        try {
            const data = await apiFetch('api.v1.admin.dashboard.shipping', buildFilterParams());
            stats = data.stats;
            pipeline = data.pipeline;
            shippingsOverTime = data.shippings_over_time.data;
            shippingsOverTimeGrouping = data.shippings_over_time.grouping;
            transportModes = data.transport_modes;
            statusBreakdown = data.status_breakdown;
            topSuppliers = data.top_suppliers;
            topCarriers = data.top_carriers;
            topProducts = data.top_products;
        } catch (error) {
            console.error(error);
        }

        overviewLoading = false;
        await tick();
        await renderCharts();
    }

    async function fetchTable() {
        tableLoading = true;

        try {
            const data = await apiFetch('api.v1.admin.dashboard.shipping.shipments', buildTableParams());
            shippings = data.shippings;
            pagination = data.pagination;
        } catch (error) {
            console.error(error);
        }

        tableLoading = false;
    }

    async function refreshDashboard() {
        await Promise.all([fetchOverview(), fetchTable()]);
    }

    function handleSiteChange(event) {
        productionSiteId = event.detail.value || '';
        currentPage = 1;
        refreshDashboard();
    }

    function handleSegmentChange(nextSegment) {
        segment = nextSegment;
        timeliness = '';
        currentPage = 1;
        fetchTable();
    }

    function handleDateRangePresetChange(event) {
        dateRangePreset = event.target.value;

        if (dateRangePreset === 'custom') {
            return;
        }

        if (dateRangePreset === 'lifetime') {
            currentPage = 1;
            refreshDashboard();
            return;
        }

        const dates = computePresetDates(dateRangePreset);
        if (dates) {
            from = dates.from;
            to = dates.to;
        }

        currentPage = 1;
        refreshDashboard();
    }

    function handleFromChange(event) {
        from = event.detail.value;
        dateRangePreset = 'custom';
        currentPage = 1;
        refreshDashboard();
    }

    function handleToChange(event) {
        to = event.detail.value;
        dateRangePreset = 'custom';
        currentPage = 1;
        refreshDashboard();
    }

    function handlePageChange(page) {
        currentPage = page;
        fetchTable();
    }

    function handlePerPageChange(newPerPage) {
        perPage = newPerPage;
        currentPage = 1;
        fetchTable();
    }

    function formatQty(value) {
        return Number(value ?? 0).toLocaleString('en-US', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 3,
        });
    }

    function formatDiffDays(diffDays) {
        if (diffDays === null || diffDays === undefined) {
            return 'N/A';
        }

        const prefix = diffDays > 0 ? '+' : '';
        const label = Math.abs(diffDays) === 1 ? 'day' : 'days';
        return `${prefix}${diffDays} ${label}`;
    }

    function segmentMeta(segmentId) {
        return segments.find((item) => item.id === segmentId);
    }

    function selectPipelineMetric(segmentId, metric) {
        segment = segmentId;
        timeliness = metric;
        currentPage = 1;
        fetchTable();
    }

    function baseChartOptions() {
        return {
            chart: { toolbar: { show: false } },
            dataLabels: { enabled: false },
            grid: {
                borderColor: 'var(--color-border)',
                strokeDashArray: 5,
                yaxis: { lines: { show: true } },
                xaxis: { lines: { show: false } },
            },
            xaxis: {
                axisBorder: { show: false },
                axisTicks: { show: false },
                labels: {
                    style: {
                        colors: 'var(--color-secondary-foreground)',
                        fontSize: '12px',
                    },
                },
            },
            yaxis: {
                min: 0,
                labels: {
                    style: {
                        colors: 'var(--color-secondary-foreground)',
                        fontSize: '12px',
                    },
                },
            },
        };
    }

    function baseDonutOptions() {
        return {
            chart: { type: 'donut', height: 300 },
            stroke: {
                show: true,
                width: 2,
                colors: ['var(--color-background)'],
            },
            dataLabels: { enabled: false },
            plotOptions: {
                pie: { expandOnClick: false, donut: { size: '70%' } },
            },
            legend: {
                position: 'bottom',
                fontSize: '13px',
                fontWeight: 500,
                labels: { colors: 'var(--color-secondary-foreground)' },
                markers: { width: 8, height: 8 },
            },
        };
    }

    async function renderOverTimeChart() {
        await new Promise((resolve) => setTimeout(resolve, 100));
        if (!overTimeChartEl || typeof window.ApexCharts === 'undefined') return;
        if (overTimeChart) overTimeChart.destroy();

        overTimeChart = new window.ApexCharts(overTimeChartEl, {
            ...baseChartOptions(),
            series: [{ name: 'Shipments', data: shippingsOverTime.map((item) => item.count) }],
            chart: { ...baseChartOptions().chart, type: 'bar', height: 300 },
            colors: [chartColors.primary],
            plotOptions: { bar: { borderRadius: 4, columnWidth: '50%' } },
            xaxis: {
                ...baseChartOptions().xaxis,
                categories: shippingsOverTime.map((item) => item.label),
            },
        });
        overTimeChart.render();
    }

    async function renderTransportChart() {
        await new Promise((resolve) => setTimeout(resolve, 100));
        if (!transportChartEl || typeof window.ApexCharts === 'undefined') return;
        if (transportChart) transportChart.destroy();

        const colors = [chartColors.blue, chartColors.primary, chartColors.orange];
        transportChart = new window.ApexCharts(transportChartEl, {
            ...baseDonutOptions(),
            series: transportModes.map((item) => item.count),
            labels: transportModes.map((item) => item.label),
            colors: colors.slice(0, transportModes.length),
        });
        transportChart.render();
    }

    async function renderStatusChart() {
        await new Promise((resolve) => setTimeout(resolve, 100));
        if (!statusChartEl || typeof window.ApexCharts === 'undefined') return;
        if (statusChart) statusChart.destroy();

        const statusColors = {
            in_transit: chartColors.yellow,
            at_customs: chartColors.blue,
            delivered: chartColors.primary,
            received: chartColors.green,
            cancelled: chartColors.red,
        };

        statusChart = new window.ApexCharts(statusChartEl, {
            ...baseDonutOptions(),
            series: statusBreakdown.map((item) => item.count),
            labels: statusBreakdown.map((item) => item.label),
            colors: statusBreakdown.map((item) => statusColors[item.status] || chartColors.slate),
        });
        statusChart.render();
    }

    async function renderHorizontalBarChart(element, chartRef, data, valueKey, labelKey) {
        await new Promise((resolve) => setTimeout(resolve, 100));
        if (!element || typeof window.ApexCharts === 'undefined') return chartRef;
        if (chartRef) chartRef.destroy();

        const chart = new window.ApexCharts(element, {
            ...baseChartOptions(),
            series: [{ name: 'Quantity', data: data.map((item) => parseFloat(item[valueKey])) }],
            chart: { ...baseChartOptions().chart, type: 'bar', height: 300 },
            colors: [chartColors.violet],
            plotOptions: {
                bar: { borderRadius: 4, horizontal: true, barHeight: '60%' },
            },
            xaxis: {
                ...baseChartOptions().xaxis,
                categories: data.map((item) => item[labelKey]),
            },
        });
        chart.render();

        return chart;
    }

    async function renderCharts() {
        await renderOverTimeChart();
        await renderTransportChart();
        await renderStatusChart();
        suppliersChart = await renderHorizontalBarChart(
            suppliersChartEl,
            suppliersChart,
            topSuppliers,
            'total_quantity',
            'name'
        );
        carriersChart = await renderHorizontalBarChart(
            carriersChartEl,
            carriersChart,
            topCarriers,
            'total_quantity',
            'name'
        );
        productsChart = await renderHorizontalBarChart(
            productsChartEl,
            productsChart,
            topProducts,
            'total_quantity',
            'name'
        );
    }

    onMount(() => {
        refreshDashboard();
    });

    onDestroy(() => {
        if (overTimeChart) overTimeChart.destroy();
        if (transportChart) transportChart.destroy();
        if (statusChart) statusChart.destroy();
        if (suppliersChart) suppliersChart.destroy();
        if (carriersChart) carriersChart.destroy();
        if (productsChart) productsChart.destroy();
    });
</script>

<div class="grid gap-5 lg:gap-7.5">
    <!-- OVERVIEW -->
    <div class="kt-card">
        <div class="kt-card-header py-4">
            <h3 class="kt-card-title">Overview</h3>
            <div class="kt-card-toolbar">
                <div class="flex flex-wrap items-center gap-2">
                    <div class="w-[180px]">
                        <Select2
                            bind:this={siteSelectComponent}
                            id="dashboard-site-filter"
                            placeholder="All sites"
                            value={productionSiteId}
                            allowClear={true}
                            on:change={handleSiteChange}
                            ajax={{
                                url: route('api.v1.admin.production-sites.index'),
                                dataType: 'json',
                                delay: 300,
                                data: function (params) {
                                    return {
                                        search: params.term,
                                        per_page: 10,
                                    };
                                },
                                processResults: function (data) {
                                    return {
                                        results: data.production_sites.map((site) => ({
                                            id: site.id,
                                            text: site.name,
                                        })),
                                    };
                                },
                            }}
                        />
                    </div>
                    <div class="flex items-center gap-1">
                        <span class="text-xs font-medium text-muted-foreground">Period:</span>
                        <select
                            class="kt-select w-[150px] h-8 text-xs"
                            value={dateRangePreset}
                            on:change={handleDateRangePresetChange}
                        >
                            {#each dateRangePresets as preset}
                                <option value={preset.id}>{preset.label}</option>
                            {/each}
                        </select>
                    </div>
                    {#if dateRangePreset === 'custom'}
                        <div class="flex items-center gap-1">
                            <span class="text-xs font-medium text-muted-foreground">From:</span>
                            <Flatpickr
                                bind:value={from}
                                on:change={handleFromChange}
                                class="w-[110px]"
                            />
                        </div>
                        <div class="flex items-center gap-1">
                            <span class="text-xs font-medium text-muted-foreground">To:</span>
                            <Flatpickr
                                bind:value={to}
                                on:change={handleToChange}
                                class="w-[110px]"
                            />
                        </div>
                    {/if}
                </div>
            </div>
        </div>
        <div class="kt-card-content p-6">
            {#if overviewLoading}
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    {#each Array(8) as _}
                        <div class="kt-card">
                            <div class="kt-card-content p-4">
                                <div class="flex items-center justify-between">
                                    <div class="space-y-2">
                                        <div class="kt-skeleton w-24 h-4 rounded"></div>
                                        <div class="kt-skeleton w-16 h-8 rounded"></div>
                                    </div>
                                    <div class="kt-skeleton w-12 h-12 rounded-full"></div>
                                </div>
                            </div>
                        </div>
                    {/each}
                </div>
            {:else}
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="kt-card">
                        <div class="kt-card-content p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-muted-foreground">Quantity Purchased</p>
                                    <p class="text-2xl font-bold text-foreground">{formatQty(stats.quantity_purchased)}</p>
                                </div>
                                <div class="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center">
                                    <i class="fa-solid fa-cart-shopping text-primary text-xl"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="kt-card">
                        <div class="kt-card-content p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-muted-foreground">Quantity Shipped</p>
                                    <p class="text-2xl font-bold text-foreground">{formatQty(stats.quantity_shipped)}</p>
                                </div>
                                <div class="w-12 h-12 bg-blue-50 dark:bg-blue-500/10 rounded-full flex items-center justify-center">
                                    <i class="fa-solid fa-truck text-blue-500 text-xl"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="kt-card">
                        <div class="kt-card-content p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-muted-foreground">Quantity Received</p>
                                    <p class="text-2xl font-bold text-foreground">{formatQty(stats.quantity_received)}</p>
                                </div>
                                <div class="w-12 h-12 bg-violet-50 dark:bg-violet-500/10 rounded-full flex items-center justify-center">
                                    <i class="fa-solid fa-warehouse text-violet-500 text-xl"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="kt-card">
                        <div class="kt-card-content p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-muted-foreground">Quantity Approved</p>
                                    <p class="text-2xl font-bold text-foreground">{formatQty(stats.quantity_approved)}</p>
                                </div>
                                <div class="w-12 h-12 bg-green-50 dark:bg-green-500/10 rounded-full flex items-center justify-center">
                                    <i class="fa-solid fa-circle-check text-green-500 text-xl"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="kt-card">
                        <div class="kt-card-content p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-muted-foreground">Quantity Released</p>
                                    <p class="text-2xl font-bold text-foreground">{formatQty(stats.quantity_released)}</p>
                                </div>
                                <div class="w-12 h-12 bg-orange-50 dark:bg-orange-500/10 rounded-full flex items-center justify-center">
                                    <i class="fa-solid fa-box-open text-orange-500 text-xl"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="kt-card">
                        <div class="kt-card-content p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-muted-foreground">Total Shipments</p>
                                    <p class="text-2xl font-bold text-foreground">{stats.total_shipments}</p>
                                </div>
                                <div class="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center">
                                    <i class="fa-solid fa-shipping-fast text-primary text-xl"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="kt-card">
                        <div class="kt-card-content p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-muted-foreground">Active Shipments</p>
                                    <p class="text-2xl font-bold text-foreground">{stats.active_shipments}</p>
                                </div>
                                <div class="w-12 h-12 bg-yellow-50 dark:bg-yellow-500/10 rounded-full flex items-center justify-center">
                                    <i class="fa-solid fa-clock text-yellow-500 text-xl"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="kt-card">
                        <div class="kt-card-content p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-muted-foreground">On-Time Rate</p>
                                    <p class="text-2xl font-bold text-foreground">{stats.on_time_rate}%</p>
                                </div>
                                <div class="w-12 h-12 bg-green-50 dark:bg-green-500/10 rounded-full flex items-center justify-center">
                                    <i class="fa-solid fa-chart-line text-green-500 text-xl"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            {/if}
        </div>
    </div>

    <!-- DELIVERY PERFORMANCE -->
    <div class="kt-card">
        <div class="kt-card-header py-4">
            <h3 class="kt-card-title">Delivery Performance</h3>
        </div>
        <div class="kt-card-content p-6">
            {#if overviewLoading}
                <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
                    {#each Array(3) as _}
                        <div class="kt-card">
                            <div class="kt-card-content p-4">
                                <div class="grid grid-cols-3 gap-3">
                                    {#each Array(3) as __}
                                        <div class="space-y-2">
                                            <div class="kt-skeleton w-16 h-3 rounded"></div>
                                            <div class="kt-skeleton w-10 h-7 rounded"></div>
                                        </div>
                                    {/each}
                                </div>
                            </div>
                        </div>
                    {/each}
                </div>
            {:else}
                <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
                    {#each segments as item}
                        <div class="kt-card">
                            <div class="kt-card-header py-3 px-4">
                                <div>
                                    <h4 class="text-sm font-semibold text-foreground">{item.label}</h4>
                                    <p class="text-xs text-muted-foreground">{item.description}</p>
                                </div>
                            </div>
                            <div class="kt-card-content p-4 pt-0">
                                <div class="grid grid-cols-3 gap-3 mt-4">
                                    <button
                                        type="button"
                                        class="text-left rounded-lg border border-border p-3 hover:bg-accent/40 transition-colors {segment === item.id && !timeliness ? 'ring-2 ring-primary/50' : ''}"
                                        on:click={() => selectPipelineMetric(item.id, '')}
                                    >
                                        <p class="text-xs text-muted-foreground">Total</p>
                                        <p class="text-2xl font-bold text-foreground">{pipeline[item.id]?.total ?? 0}</p>
                                    </button>
                                    <button
                                        type="button"
                                        class="text-left rounded-lg border border-border p-3 hover:bg-accent/40 transition-colors {segment === item.id && timeliness === 'on_time' ? 'ring-2 ring-success/30' : ''}"
                                        on:click={() => selectPipelineMetric(item.id, 'on_time')}
                                    >
                                        <p class="text-xs text-muted-foreground">On Time</p>
                                        <p class="text-2xl font-bold text-success">{pipeline[item.id]?.on_time ?? 0}</p>
                                    </button>
                                    <button
                                        type="button"
                                        class="text-left rounded-lg border border-border p-3 hover:bg-accent/40 transition-colors {segment === item.id && timeliness === 'off_time' ? 'ring-2 ring-destructive/30' : ''}"
                                        on:click={() => selectPipelineMetric(item.id, 'off_time')}
                                    >
                                        <p class="text-xs text-muted-foreground">Off Time</p>
                                        <p class="text-2xl font-bold text-destructive">{pipeline[item.id]?.off_time ?? 0}</p>
                                    </button>
                                </div>
                            </div>
                        </div>
                    {/each}
                </div>
            {/if}
        </div>
    </div>

    <!-- SHIPMENTS OVER TIME + STATUS -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 lg:gap-7.5">
        <div class="kt-card">
            <div class="kt-card-header py-4">
                <h3 class="kt-card-title">Shipments Over Time</h3>
            </div>
            <div class="kt-card-content px-3 py-1">
                {#if overviewLoading}
                    <div class="flex flex-col gap-5 h-72 p-4 pt-10">
                        {#each Array(5) as _}
                            <div class="kt-skeleton w-full h-8 rounded-lg"></div>
                        {/each}
                    </div>
                {:else if shippingsOverTime.length === 0}
                    <div class="flex items-center justify-center h-72 text-muted-foreground">No data available</div>
                {:else}
                    <div bind:this={overTimeChartEl}></div>
                {/if}
            </div>
        </div>

        <div class="kt-card">
            <div class="kt-card-header py-4">
                <h3 class="kt-card-title">Shipment Status</h3>
            </div>
            <div class="kt-card-content flex justify-center items-center px-3 py-4">
                {#if overviewLoading}
                    <div class="flex flex-col items-center gap-4">
                        <div class="kt-skeleton w-40 h-40 rounded-full"></div>
                        <div class="flex gap-4">
                            {#each Array(3) as _}
                                <div class="flex items-center gap-1.5">
                                    <div class="kt-skeleton w-3 h-3 rounded-full"></div>
                                    <div class="kt-skeleton w-14 h-3 rounded"></div>
                                </div>
                            {/each}
                        </div>
                    </div>
                {:else if statusBreakdown.length === 0}
                    <div class="flex items-center justify-center h-64 text-muted-foreground">No data available</div>
                {:else}
                    <div bind:this={statusChartEl}></div>
                {/if}
            </div>
        </div>
    </div>

    <!-- TRANSPORT MODES + TOP SUPPLIERS -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 lg:gap-7.5">
        <div class="kt-card">
            <div class="kt-card-header py-4">
                <h3 class="kt-card-title">Transport Modes</h3>
            </div>
            <div class="kt-card-content flex justify-center items-center px-3 py-4">
                {#if overviewLoading}
                    <div class="flex flex-col items-center gap-4">
                        <div class="kt-skeleton w-40 h-40 rounded-full"></div>
                    </div>
                {:else if transportModes.length === 0}
                    <div class="flex items-center justify-center h-64 text-muted-foreground">No data available</div>
                {:else}
                    <div bind:this={transportChartEl}></div>
                {/if}
            </div>
        </div>

        <div class="kt-card">
            <div class="kt-card-header py-4">
                <h3 class="kt-card-title">Top Suppliers</h3>
            </div>
            <div class="kt-card-content px-3 py-1">
                {#if overviewLoading}
                    <div class="flex flex-col gap-5 h-72 p-4 pt-10">
                        {#each Array(6) as _}
                            <div class="kt-skeleton w-full h-4 rounded-lg"></div>
                        {/each}
                    </div>
                {:else if topSuppliers.length === 0}
                    <div class="flex items-center justify-center h-72 text-muted-foreground">No data available</div>
                {:else}
                    <div bind:this={suppliersChartEl}></div>
                {/if}
            </div>
        </div>
    </div>

    <!-- TOP CARRIERS + TOP PRODUCTS -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 lg:gap-7.5">
        <div class="kt-card">
            <div class="kt-card-header py-4">
                <h3 class="kt-card-title">Top Carriers</h3>
            </div>
            <div class="kt-card-content px-3 py-1">
                {#if overviewLoading}
                    <div class="flex flex-col gap-5 h-72 p-4 pt-10">
                        {#each Array(6) as _}
                            <div class="kt-skeleton w-full h-4 rounded-lg"></div>
                        {/each}
                    </div>
                {:else if topCarriers.length === 0}
                    <div class="flex items-center justify-center h-72 text-muted-foreground">No data available</div>
                {:else}
                    <div bind:this={carriersChartEl}></div>
                {/if}
            </div>
        </div>

        <div class="kt-card">
            <div class="kt-card-header py-4">
                <h3 class="kt-card-title">Top Products Shipped</h3>
            </div>
            <div class="kt-card-content px-3 py-1">
                {#if overviewLoading}
                    <div class="flex flex-col gap-5 h-72 p-4 pt-10">
                        {#each Array(6) as _}
                            <div class="kt-skeleton w-full h-4 rounded-lg"></div>
                        {/each}
                    </div>
                {:else if topProducts.length === 0}
                    <div class="flex items-center justify-center h-72 text-muted-foreground">No data available</div>
                {:else}
                    <div bind:this={productsChartEl}></div>
                {/if}
            </div>
        </div>
    </div>

    <!-- SHIPMENTS TABLE -->
    <div class="kt-card">
        <div class="kt-card-header py-4">
            <div>
                <h3 class="kt-card-title">{segmentMeta(segment)?.label ?? 'Shipments'}</h3>
                <p class="text-xs text-muted-foreground mt-0.5">
                    {#if timeliness === 'on_time'}
                        Showing on-time shipments only
                    {:else if timeliness === 'off_time'}
                        Showing off-time shipments only
                    {:else}
                        Showing all shipments with a planned date
                    {/if}
                </p>
            </div>
            <div class="kt-card-toolbar">
                <div class="flex flex-wrap gap-2">
                    {#each segments as item}
                        <button
                            type="button"
                            class="kt-btn kt-btn-sm {segment === item.id ? 'kt-btn-primary' : 'kt-btn-outline'}"
                            on:click={() => handleSegmentChange(item.id)}
                        >
                            {item.label}
                        </button>
                    {/each}
                </div>
            </div>
        </div>

        <div class="kt-card-table kt-scrollable-x-auto">
            <table class="kt-table kt-table-border-b">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Product</th>
                        <th>Supplier</th>
                        <th>Carrier</th>
                        <th>Time Diff</th>
                    </tr>
                </thead>
                <tbody>
                    {#if tableLoading}
                        {#each Array(5) as _}
                            <tr>
                                <td colspan="5">
                                    <div class="kt-skeleton w-full h-5 rounded"></div>
                                </td>
                            </tr>
                        {/each}
                    {:else if shippings.length === 0}
                        <tr>
                            <td colspan="5" class="text-center text-muted-foreground py-8">
                                No shipments found for the selected filters.
                            </td>
                        </tr>
                    {:else}
                        {#each shippings as shipping}
                            <tr>
                                <td class="font-medium">{shipping.code ?? 'N/A'}</td>
                                <td>{shipping.product?.name ?? 'N/A'}</td>
                                <td>{shipping.supplier?.name ?? 'N/A'}</td>
                                <td>{shipping.carrier ?? 'N/A'}</td>
                                <td>
                                    <span class="{shipping.is_late ? 'text-destructive' : 'text-success'} font-medium">
                                        {formatDiffDays(shipping.diff_days)}
                                    </span>
                                </td>
                            </tr>
                        {/each}
                    {/if}
                </tbody>
            </table>
        </div>

        {#if !tableLoading && pagination.total > 0}
            <Pagination
                {pagination}
                {perPage}
                onPageChange={handlePageChange}
                onPerPageChange={handlePerPageChange}
            />
        {/if}
    </div>
</div>
