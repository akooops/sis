<script>
    import TimelinessDashboard from './TimelinessDashboard.svelte';

    const config = {
        id: 'wh-good-receipts',
        title: 'WH Good Receipts',
        overviewRoute: 'api.v1.admin.dashboard.wh-good-receipts',
        recordsRoute: 'api.v1.admin.dashboard.wh-good-receipts.records',
        segments: [
            { id: 'pending', label: 'Pending', description: 'Awaiting warehouse receipt' },
            { id: 'received', label: 'Received', description: 'Received, pending QC submission' },
        ],
        statsLabels: {
            total: 'Total Receipts',
            active: 'Active Receipts',
            onTimeRate: 'On-Time Rate',
        },
        tableColumns: [
            { key: 'code', label: 'Code' },
            { key: 'batch', label: 'Batch' },
            { key: 'product', label: 'Product', type: 'product' },
            { key: 'supplier', label: 'Supplier', type: 'supplier' },
            { key: 'diff_days', label: 'Time Diff', type: 'diff' },
        ],
    };
</script>

<TimelinessDashboard {config} />
