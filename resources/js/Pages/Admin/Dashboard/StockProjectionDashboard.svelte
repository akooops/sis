<script>
    import Flatpickr from '../../Shared/Utils/Forms/Flatpickr.svelte';
    import Select2 from '../../Shared/Utils/Forms/Select2.svelte';
    import { onDestroy, onMount, tick } from 'svelte';
    import {
        computePresetDates,
        dateRangePresets,
        defaultDateRangePreset,
        formatQuantity,
    } from './stockProjectionDashboardUtils.js';

    const chartColors = {
        primary: 'var(--color-primary)',
        green: '#22c55e',
        orange: '#f97316',
        red: '#ef4444',
        blue: '#3b82f6',
    };

    const defaultDates = computePresetDates(defaultDateRangePreset);

    let productId = '';
    let dateRangePreset = defaultDateRangePreset;
    let from = defaultDates.from;
    let to = defaultDates.to;
    let loading = false;
    let hasFetched = false;

    let product = null;
    let stats = {
        current_stock: 0,
        min_projected: null,
        max_projected: null,
        end_projected: null,
        total_inbound: 0,
        total_outbound: 0,
    };
    let projections = [];
    let computedAt = null;

    let productSelectComponent;
    let stockChartEl;
    let stockChart = null;

    function buildFilterParams() {
        const params = { from, to };

        if (productId) {
            params.product_id = productId;
        }

        return params;
    }

    async function fetchDashboard() {
        if (!productId) {
            product = null;
            projections = [];
            hasFetched = false;
            if (stockChart) {
                stockChart.destroy();
                stockChart = null;
            }
            return;
        }

        loading = true;

        try {
            const response = await fetch(route('api.v1.admin.dashboard.stock-projection', buildFilterParams()), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            const data = await response.json();

            product = data.product;
            stats = data.stats;
            projections = data.projections ?? [];
            computedAt = data.computed_at;
            hasFetched = true;
        } catch (error) {
            console.error(error);
        }

        loading = false;
        await tick();
        await renderStockChart();
    }

    function handleProductChange(event) {
        productId = event.detail.value || '';
        fetchDashboard();
    }

    function handleDateRangePresetChange(event) {
        dateRangePreset = event.target.value;

        if (dateRangePreset === 'custom') {
            return;
        }

        const dates = computePresetDates(dateRangePreset);

        if (dates) {
            from = dates.from;
            to = dates.to;
        }

        fetchDashboard();
    }

    function handleFromChange(event) {
        from = event.detail.value;
        dateRangePreset = 'custom';
        fetchDashboard();
    }

    function handleToChange(event) {
        to = event.detail.value;
        dateRangePreset = 'custom';
        fetchDashboard();
    }

    function formatComputedAt(value) {
        if (!value) {
            return '—';
        }

        return new Date(value).toLocaleString();
    }

    function baseChartOptions() {
        return {
            chart: { toolbar: { show: false }, zoom: { enabled: false } },
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: 2 },
            grid: {
                borderColor: 'var(--color-border)',
                strokeDashArray: 5,
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
                labels: {
                    style: {
                        colors: 'var(--color-secondary-foreground)',
                        fontSize: '12px',
                    },
                },
            },
            legend: {
                labels: { colors: 'var(--color-secondary-foreground)' },
            },
            tooltip: {
                theme: 'dark',
            },
        };
    }

    function buildAnnotations() {
        const annotations = [];

        if (product?.min_stock !== null && product?.min_stock !== undefined) {
            annotations.push({
                y: product.min_stock,
                borderColor: chartColors.orange,
                strokeDashArray: 4,
                label: {
                    text: 'Min stock',
                    style: { color: chartColors.orange, background: 'transparent' },
                },
            });
        }

        if (product?.max_stock !== null && product?.max_stock !== undefined) {
            annotations.push({
                y: product.max_stock,
                borderColor: chartColors.red,
                strokeDashArray: 4,
                label: {
                    text: 'Max stock',
                    style: { color: chartColors.red, background: 'transparent' },
                },
            });
        }

        return annotations;
    }

    async function renderStockChart() {
        await new Promise((resolve) => setTimeout(resolve, 100));

        if (!stockChartEl || typeof window.ApexCharts === 'undefined' || projections.length === 0) {
            if (stockChart) {
                stockChart.destroy();
                stockChart = null;
            }
            return;
        }

        if (stockChart) {
            stockChart.destroy();
        }

        stockChart = new window.ApexCharts(stockChartEl, {
            ...baseChartOptions(),
            series: [
                {
                    name: 'Projected stock',
                    data: projections.map((item) => item.projected_stock),
                },
                {
                    name: 'Inbound',
                    data: projections.map((item) => item.inbound_quantity),
                },
                {
                    name: 'Outbound',
                    data: projections.map((item) => item.outbound_quantity),
                },
            ],
            chart: { ...baseChartOptions().chart, type: 'line', height: 320 },
            colors: [chartColors.primary, chartColors.green, chartColors.red],
            xaxis: {
                ...baseChartOptions().xaxis,
                categories: projections.map((item) => item.date),
            },
            annotations: {
                yaxis: buildAnnotations(),
            },
            tooltip: {
                ...baseChartOptions().tooltip,
                y: {
                    formatter: (value) => formatQuantity(product, value),
                },
            },
        });

        stockChart.render();
    }

    onDestroy(() => {
        if (stockChart) {
            stockChart.destroy();
        }
    });
</script>

<div class="grid gap-5 lg:gap-7.5">
    <div class="kt-card">
        <div class="kt-card-header py-4">
            <h3 class="kt-card-title">Stock Projection</h3>
            <div class="kt-card-toolbar">
                <div class="flex flex-wrap items-center gap-2">
                    <div class="w-[240px]">
                        <Select2
                            bind:this={productSelectComponent}
                            id="stock-projection-dashboard-product-filter"
                            placeholder="Select product"
                            value={productId}
                            allowClear={true}
                            on:change={handleProductChange}
                            ajax={{
                                url: route('api.v1.admin.products.index'),
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
                                        results: data.products.map((item) => ({
                                            id: item.id,
                                            text: item.name,
                                        })),
                                    };
                                },
                            }}
                        />
                    </div>
                    <div class="flex items-center gap-1">
                        <span class="text-xs font-medium text-muted-foreground">Period:</span>
                        <select
                            class="kt-select w-[140px] h-8 text-xs"
                            value={dateRangePreset}
                            on:change={handleDateRangePresetChange}
                        >
                            {#each dateRangePresets as preset}
                                <option value={preset.id}>{preset.label}</option>
                            {/each}
                        </select>
                    </div>
                    {#if dateRangePreset === 'custom'}
                        <Flatpickr
                            id="stock-projection-from"
                            placeholder="From"
                            value={from}
                            on:change={handleFromChange}
                        />
                        <Flatpickr
                            id="stock-projection-to"
                            placeholder="To"
                            value={to}
                            on:change={handleToChange}
                        />
                    {/if}
                </div>
            </div>
        </div>

        {#if !productId}
            <div class="kt-card-content p-8 text-center">
                <div class="w-16 h-16 bg-accent/50 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fa-solid fa-chart-line text-muted-foreground text-2xl"></i>
                </div>
                <h3 class="text-sm font-semibold text-foreground">Select a product</h3>
                <p class="text-xs text-muted-foreground mt-1">
                    Choose a product and period to view projected stock.
                </p>
            </div>
        {:else if loading}
            <div class="kt-card-content p-6">
                <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
                    {#each Array(6) as _}
                        <div class="kt-skeleton w-full h-20 rounded-lg"></div>
                    {/each}
                </div>
                <div class="kt-skeleton w-full h-80 rounded-lg mt-6"></div>
            </div>
        {:else if hasFetched}
            <div class="kt-card-content p-6 space-y-6">
                <p class="text-xs text-muted-foreground">
                    Last computed: {formatComputedAt(computedAt)}
                </p>

                <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
                    <div class="kt-card border border-border">
                        <div class="kt-card-content p-4">
                            <p class="text-xs text-muted-foreground">Current Stock</p>
                            <p class="text-xl font-bold text-foreground">{formatQuantity(product, stats.current_stock)}</p>
                        </div>
                    </div>
                    <div class="kt-card border border-border">
                        <div class="kt-card-content p-4">
                            <p class="text-xs text-muted-foreground">Min Projected</p>
                            <p class="text-xl font-bold text-foreground">{formatQuantity(product, stats.min_projected)}</p>
                        </div>
                    </div>
                    <div class="kt-card border border-border">
                        <div class="kt-card-content p-4">
                            <p class="text-xs text-muted-foreground">Max Projected</p>
                            <p class="text-xl font-bold text-foreground">{formatQuantity(product, stats.max_projected)}</p>
                        </div>
                    </div>
                    <div class="kt-card border border-border">
                        <div class="kt-card-content p-4">
                            <p class="text-xs text-muted-foreground">End of Period</p>
                            <p class="text-xl font-bold text-foreground">{formatQuantity(product, stats.end_projected)}</p>
                        </div>
                    </div>
                    <div class="kt-card border border-border">
                        <div class="kt-card-content p-4">
                            <p class="text-xs text-muted-foreground">Total Inbound</p>
                            <p class="text-xl font-bold text-success">{formatQuantity(product, stats.total_inbound)}</p>
                        </div>
                    </div>
                    <div class="kt-card border border-border">
                        <div class="kt-card-content p-4">
                            <p class="text-xs text-muted-foreground">Total Outbound</p>
                            <p class="text-xl font-bold text-destructive">{formatQuantity(product, stats.total_outbound)}</p>
                        </div>
                    </div>
                </div>

                <div class="kt-card border border-border mt-4">
                    <div class="kt-card-header py-3 px-4">
                        <h4 class="text-sm font-semibold text-foreground">Projected Stock Over Time</h4>
                    </div>
                    <div class="kt-card-content p-4 pt-0">
                        {#if projections.length === 0}
                            <p class="text-sm text-muted-foreground py-8 text-center">
                                No projection data for this period. Run the stock projection recompute command.
                            </p>
                        {:else}
                            <div bind:this={stockChartEl}></div>
                        {/if}
                    </div>
                </div>
            </div>
        {/if}
    </div>
</div>
