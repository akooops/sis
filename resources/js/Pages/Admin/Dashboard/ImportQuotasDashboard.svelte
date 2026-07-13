<script>
    import Select2 from '../../Shared/Utils/Forms/Select2.svelte';
    import { onDestroy, onMount, tick } from 'svelte';

    const chartColors = {
        primary: 'var(--color-primary)',
        green: '#22c55e',
        red: '#ef4444',
        orange: '#f97316',
        violet: '#8b5cf6',
    };

    const yearOptions = Array.from({ length: 101 }, (_, index) => 2000 + index);

    let selectedYear = new Date().getFullYear();
    let productId = '';
    let loading = true;

    let stats = {
        total_products: 0,
        over_consumed: 0,
        within_quota: 0,
    };
    let importQuotas = [];

    let productSelectComponent;
    let consumptionChartEl;
    let consumptionChart = null;

    function buildFilterParams() {
        const params = { year: selectedYear };

        if (productId) {
            params.product_id = productId;
        }

        return params;
    }

    async function fetchDashboard() {
        loading = true;

        try {
            const response = await fetch(route('api.v1.admin.dashboard.import-quotas', buildFilterParams()), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            const data = await response.json();

            stats = data.stats;
            importQuotas = data.import_quotas;
        } catch (error) {
            console.error(error);
        }

        loading = false;
        await tick();
        await renderConsumptionChart();
    }

    function handleYearChange(event) {
        selectedYear = parseInt(event.target.value, 10);
        fetchDashboard();
    }

    function handleProductChange(event) {
        productId = event.detail.value || '';
        fetchDashboard();
    }

    function formatQty(product, value) {
        if (value === null || value === undefined || value === '') {
            return '—';
        }

        const unit = product?.unit_of_measurement?.trim();
        const formatted = Number(value).toLocaleString('en-US', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 3,
        });

        return unit ? `${formatted} ${unit}` : formatted;
    }

    function formatPercentage(value) {
        if (value === null || value === undefined) {
            return '—';
        }

        return `${value}%`;
    }

    function baseChartOptions() {
        return {
            chart: { toolbar: { show: false } },
            dataLabels: { enabled: false },
            grid: {
                borderColor: 'var(--color-border)',
                strokeDashArray: 5,
                yaxis: { lines: { show: false } },
                xaxis: { lines: { show: true } },
            },
            xaxis: {
                min: 0,
                max: Math.max(100, ...importQuotas.map((item) => item.consumption_percentage ?? 0), 0) + 10,
                axisBorder: { show: false },
                axisTicks: { show: false },
                labels: {
                    style: {
                        colors: 'var(--color-secondary-foreground)',
                        fontSize: '12px',
                    },
                    formatter: (value) => `${value}%`,
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
            annotations: {
                xaxis: [
                    {
                        x: 100,
                        borderColor: chartColors.orange,
                        strokeDashArray: 4,
                        label: {
                            text: '100%',
                            style: {
                                color: chartColors.orange,
                                background: 'transparent',
                            },
                        },
                    },
                ],
            },
        };
    }

    async function renderConsumptionChart() {
        await new Promise((resolve) => setTimeout(resolve, 100));
        if (!consumptionChartEl || typeof window.ApexCharts === 'undefined' || importQuotas.length === 0) {
            if (consumptionChart) {
                consumptionChart.destroy();
                consumptionChart = null;
            }
            return;
        }

        if (consumptionChart) consumptionChart.destroy();

        consumptionChart = new window.ApexCharts(consumptionChartEl, {
            ...baseChartOptions(),
            series: [{
                name: 'Consumption %',
                data: importQuotas.map((item) => item.consumption_percentage ?? 0),
            }],
            chart: { ...baseChartOptions().chart, type: 'bar', height: Math.max(280, importQuotas.length * 42) },
            colors: importQuotas.map((item) => (item.is_over_consumed ? chartColors.red : chartColors.green)),
            plotOptions: {
                bar: {
                    borderRadius: 4,
                    horizontal: true,
                    barHeight: '65%',
                    distributed: true,
                },
            },
            xaxis: {
                ...baseChartOptions().xaxis,
                categories: importQuotas.map((item) => item.product?.name ?? 'Unknown'),
            },
            tooltip: {
                y: {
                    formatter: (value) => `${value}%`,
                },
            },
        });
        consumptionChart.render();
    }

    onMount(() => {
        fetchDashboard();
    });

    onDestroy(() => {
        if (consumptionChart) consumptionChart.destroy();
    });
</script>

<div class="grid gap-5 lg:gap-7.5">
    <div class="kt-card">
        <div class="kt-card-header py-4">
            <h3 class="kt-card-title">Import Quotas</h3>
            <div class="kt-card-toolbar">
                <div class="flex flex-wrap items-center gap-2">
                    <div class="flex items-center gap-1">
                        <span class="text-xs font-medium text-muted-foreground">Year:</span>
                        <select
                            class="kt-select w-[100px] h-8 text-xs"
                            value={selectedYear}
                            on:change={handleYearChange}
                        >
                            {#each yearOptions as year}
                                <option value={year}>{year}</option>
                            {/each}
                        </select>
                    </div>
                    <div class="w-[220px]">
                        <Select2
                            bind:this={productSelectComponent}
                            id="import-quotas-dashboard-product-filter"
                            placeholder="All products"
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
                                        results: data.products.map((product) => ({
                                            id: product.id,
                                            text: product.name,
                                        })),
                                    };
                                },
                            }}
                        />
                    </div>
                </div>
            </div>
        </div>
        <div class="kt-card-content p-6">
            {#if loading}
                <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
                    {#each Array(3) as _}
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
                <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
                    <div class="kt-card">
                        <div class="kt-card-content p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-muted-foreground">Tracked Products</p>
                                    <p class="text-2xl font-bold text-foreground">{stats.total_products}</p>
                                </div>
                                <div class="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center">
                                    <i class="fa-solid fa-boxes-stacked text-primary text-xl"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="kt-card">
                        <div class="kt-card-content p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-muted-foreground">Within Quota</p>
                                    <p class="text-2xl font-bold text-success">{stats.within_quota}</p>
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
                                    <p class="text-sm text-muted-foreground">Over Quota</p>
                                    <p class="text-2xl font-bold text-destructive">{stats.over_consumed}</p>
                                </div>
                                <div class="w-12 h-12 bg-red-50 dark:bg-red-500/10 rounded-full flex items-center justify-center">
                                    <i class="fa-solid fa-triangle-exclamation text-red-500 text-xl"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            {/if}
        </div>
    </div>

    <div class="kt-card">
        <div class="kt-card-header py-4">
            <h3 class="kt-card-title">Consumption by Product</h3>
        </div>
        <div class="kt-card-content px-3 py-1">
            {#if loading}
                <div class="flex flex-col gap-5 h-72 p-4 pt-10">
                    {#each Array(6) as _}
                        <div class="kt-skeleton w-full h-4 rounded-lg"></div>
                    {/each}
                </div>
            {:else if importQuotas.length === 0}
                <div class="flex items-center justify-center h-72 text-muted-foreground">
                    No import quotas found for {selectedYear}.
                </div>
            {:else}
                <div bind:this={consumptionChartEl}></div>
            {/if}
        </div>
    </div>

    <div class="kt-card">
        <div class="kt-card-header py-4">
            <h3 class="kt-card-title">Quota Details</h3>
        </div>
        <div class="kt-card-table kt-scrollable-x-auto">
            <table class="kt-table kt-table-border-b">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Allowed</th>
                        <th>Consumed</th>
                        <th>Consumption %</th>
                    </tr>
                </thead>
                <tbody>
                    {#if loading}
                        {#each Array(5) as _}
                            <tr>
                                <td colspan="4">
                                    <div class="kt-skeleton w-full h-5 rounded"></div>
                                </td>
                            </tr>
                        {/each}
                    {:else if importQuotas.length === 0}
                        <tr>
                            <td colspan="4" class="text-center text-muted-foreground py-8">
                                No import quotas found for {selectedYear}.
                            </td>
                        </tr>
                    {:else}
                        {#each importQuotas as importQuota (importQuota.id)}
                            {@const overConsumed = importQuota.is_over_consumed}
                            <tr class="hover:bg-muted {overConsumed ? 'bg-destructive/5' : ''}">
                                <td>
                                    {#if importQuota.product}
                                        <div class="flex items-center gap-3">
                                            <img
                                                src={importQuota.product.image_url}
                                                alt={importQuota.product.name}
                                                class="w-[30px] h-[30px] rounded-lg object-cover"
                                            />
                                            <div class="flex flex-col gap-0.5">
                                                <span class="text-sm font-medium text-secondary-foreground">
                                                    {importQuota.product.name}
                                                </span>
                                                {#if importQuota.product.code}
                                                    <span class="text-xs text-muted-foreground">
                                                        {importQuota.product.code}
                                                    </span>
                                                {/if}
                                            </div>
                                        </div>
                                    {:else}
                                        <span class="text-sm text-muted-foreground">N/A</span>
                                    {/if}
                                </td>
                                <td class:text-destructive={overConsumed} class:font-semibold={overConsumed}>
                                    {formatQty(importQuota.product, importQuota.max_quota)}
                                </td>
                                <td class:text-destructive={overConsumed} class:font-semibold={overConsumed}>
                                    {formatQty(importQuota.product, importQuota.consumption_quota)}
                                </td>
                                <td>
                                    <span class="font-semibold {overConsumed ? 'text-destructive' : 'text-success'}">
                                        {formatPercentage(importQuota.consumption_percentage)}
                                    </span>
                                </td>
                            </tr>
                        {/each}
                    {/if}
                </tbody>
            </table>
        </div>
    </div>
</div>
