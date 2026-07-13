<script>
    import Flatpickr from '../../Shared/Utils/Forms/Flatpickr.svelte';
    import Select2 from '../../Shared/Utils/Forms/Select2.svelte';
    import Pagination from '../../Shared/Utils/Pagination.svelte';
    import { onMount } from 'svelte';
    import {
        computePresetDates,
        dateRangePresets,
        defaultDateRangePreset,
        formatDiffDays,
        getDateStr,
    } from './timelinessDashboardUtils.js';

    export let config;

    const defaultDates = computePresetDates(defaultDateRangePreset);

    let productionSiteId = '';
    let segment = config.segments[0]?.id ?? '';
    let timeliness = '';
    let dateRangePreset = defaultDateRangePreset;
    let from = defaultDates.from;
    let to = defaultDates.to;

    let stats = {
        total_records: 0,
        active_records: 0,
        on_time_rate: 0,
    };
    let pipeline = {};
    let records = [];
    let pagination = {};
    let overviewLoading = true;
    let tableLoading = true;
    let currentPage = 1;
    let perPage = 10;

    config.segments.forEach((item) => {
        pipeline[item.id] = { total: 0, on_time: 0, off_time: 0 };
    });

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
            const data = await apiFetch(config.overviewRoute, buildFilterParams());
            stats = data.stats;
            pipeline = data.pipeline;
        } catch (error) {
            console.error(error);
        }

        overviewLoading = false;
    }

    async function fetchTable() {
        tableLoading = true;

        try {
            const data = await apiFetch(config.recordsRoute, buildTableParams());
            records = data.records;
            pagination = data.pagination;
        } catch (error) {
            console.error(error);
        }

        tableLoading = false;
    }

    async function refreshDashboard() {
        await Promise.all([fetchOverview(), fetchTable()]);
    }

    function tableFilterHint(timeliness) {
        const hints = config.tableFilterHints ?? {
            all: 'Showing all records with a planned date',
            on_time: 'Showing on-time records only',
            off_time: 'Showing off-time records only',
        };

        if (timeliness === 'on_time') {
            return hints.on_time;
        }

        if (timeliness === 'off_time') {
            return hints.off_time;
        }

        return hints.all;
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

    function segmentMeta(segmentId) {
        return config.segments.find((item) => item.id === segmentId);
    }

    function selectPipelineMetric(segmentId, metric) {
        segment = segmentId;
        timeliness = metric;
        currentPage = 1;
        fetchTable();
    }

    function cellValue(record, column) {
        if (column.type === 'product') {
            return record.product?.name ?? 'N/A';
        }

        if (column.type === 'supplier') {
            return record.supplier?.name ?? 'N/A';
        }

        if (column.type === 'diff') {
            return formatDiffDays(record.diff_days);
        }

        return record[column.key] ?? 'N/A';
    }

    onMount(() => {
        refreshDashboard();
    });
</script>

<div class="grid gap-5 lg:gap-7.5">
    <div class="kt-card">
        <div class="kt-card-header py-4">
            <h3 class="kt-card-title">{config.title}</h3>
            <div class="kt-card-toolbar">
                <div class="flex flex-wrap items-center gap-2">
                    <div class="w-[180px]">
                        <Select2
                            id="{config.id}-site-filter"
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
                            <Flatpickr bind:value={from} on:change={handleFromChange} class="w-[110px]" />
                        </div>
                        <div class="flex items-center gap-1">
                            <span class="text-xs font-medium text-muted-foreground">To:</span>
                            <Flatpickr bind:value={to} on:change={handleToChange} class="w-[110px]" />
                        </div>
                    {/if}
                </div>
            </div>
        </div>
        <div class="kt-card-content p-6">
            {#if overviewLoading}
                <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
                    {#each Array(3) as _}
                        <div class="kt-card">
                            <div class="kt-card-content p-4">
                                <div class="space-y-2">
                                    <div class="kt-skeleton w-24 h-4 rounded"></div>
                                    <div class="kt-skeleton w-16 h-8 rounded"></div>
                                </div>
                            </div>
                        </div>
                    {/each}
                </div>
            {:else}
                <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
                    <div class="kt-card">
                        <div class="kt-card-content p-4">
                            <p class="text-sm text-muted-foreground">{config.statsLabels.total}</p>
                            <p class="text-2xl font-bold text-foreground">{stats.total_records}</p>
                        </div>
                    </div>
                    <div class="kt-card">
                        <div class="kt-card-content p-4">
                            <p class="text-sm text-muted-foreground">{config.statsLabels.active}</p>
                            <p class="text-2xl font-bold text-foreground">{stats.active_records}</p>
                        </div>
                    </div>
                    <div class="kt-card">
                        <div class="kt-card-content p-4">
                            <p class="text-sm text-muted-foreground">{config.statsLabels.onTimeRate}</p>
                            <p class="text-2xl font-bold text-foreground">{stats.on_time_rate}%</p>
                        </div>
                    </div>
                </div>
            {/if}
        </div>
    </div>

    <div class="kt-card">
        <div class="kt-card-header py-4">
            <h3 class="kt-card-title">Performance</h3>
        </div>
        <div class="kt-card-content p-6">
            {#if overviewLoading}
                <div class="grid grid-cols-1 gap-4 {config.segments.length === 2 ? 'xl:grid-cols-2' : 'xl:grid-cols-3'}">
                    {#each config.segments as _}
                        <div class="kt-skeleton w-full h-24 rounded-lg"></div>
                    {/each}
                </div>
            {:else}
                <div class="grid grid-cols-1 gap-4 {config.segments.length === 2 ? 'xl:grid-cols-2' : 'xl:grid-cols-3'}">
                    {#each config.segments as item}
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
                                        class="text-left rounded-lg border border-border p-3 hover:bg-accent/40 transition-colors {segment === item.id && !timeliness ? 'ring-2 ring-primary/30' : ''}"
                                        on:click={() => selectPipelineMetric(item.id, '')}
                                    >
                                        <p class="text-xs text-muted-foreground">{config.pipelineMetricLabels?.total ?? 'Total'}</p>
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

    <div class="kt-card">
        <div class="kt-card-header py-4">
            <div>
                <h3 class="kt-card-title">{segmentMeta(segment)?.label ?? 'Records'}</h3>
                <p class="text-xs text-muted-foreground mt-0.5">
                    {tableFilterHint(timeliness)}
                </p>
            </div>
            <div class="kt-card-toolbar">
                <div class="flex flex-wrap gap-2">
                    {#each config.segments as item}
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
                        {#each config.tableColumns as column}
                            <th>{column.label}</th>
                        {/each}
                    </tr>
                </thead>
                <tbody>
                    {#if tableLoading}
                        {#each Array(5) as _}
                            <tr>
                                <td colspan={config.tableColumns.length}>
                                    <div class="kt-skeleton w-full h-5 rounded"></div>
                                </td>
                            </tr>
                        {/each}
                    {:else if records.length === 0}
                        <tr>
                            <td colspan={config.tableColumns.length} class="text-center text-muted-foreground py-8">
                                No records found for the selected filters.
                            </td>
                        </tr>
                    {:else}
                        {#each records as record}
                            <tr>
                                {#each config.tableColumns as column}
                                    <td class:font-medium={column.key === 'code'}>
                                        {#if column.type === 'diff'}
                                            <span class="{record.is_late ? 'text-destructive' : 'text-success'} font-medium">
                                                {cellValue(record, column)}
                                            </span>
                                        {:else}
                                            {cellValue(record, column)}
                                        {/if}
                                    </td>
                                {/each}
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
