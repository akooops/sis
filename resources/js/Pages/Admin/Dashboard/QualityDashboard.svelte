<script>
    import TimelinessDashboard from './TimelinessDashboard.svelte';

    const config = {
        id: 'quality',
        title: 'Quality',
        overviewRoute: 'api.v1.admin.dashboard.quality',
        recordsRoute: 'api.v1.admin.dashboard.quality.records',
        segments: [
            { id: 'qc', label: 'QC', description: 'Active QC records (pending, completed)' },
            { id: 'qa', label: 'QA', description: 'Active QA records (pending, approved, partially approved)' },
            { id: 'qp', label: 'QP', description: 'Active QP records (pending only)' },
        ],
        statsLabels: {
            total: 'Total Records',
            active: 'Active Records',
            onTimeRate: 'On-Time Rate',
        },
        tableColumns: [
            { key: 'code', label: 'Code' },
            { key: 'product', label: 'Product', type: 'product' },
            { key: 'supplier', label: 'Supplier', type: 'supplier' },
            { key: 'diff_days', label: 'Time Diff', type: 'diff' },
        ],
        tableFilterHints: {
            all: 'Showing active records only',
            on_time: 'Showing on-time active records only',
            off_time: 'Showing off-time active records only',
        },
        pipelineMetricLabels: {
            total: 'Active',
        },
    };
</script>

<TimelinessDashboard {config} />
