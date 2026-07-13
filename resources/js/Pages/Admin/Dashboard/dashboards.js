import ShippingDashboard from './ShippingDashboard.svelte';
import ImportQuotasDashboard from './ImportQuotasDashboard.svelte';
import WHGoodReceiptDashboard from './WHGoodReceiptDashboard.svelte';
import QualityDashboard from './QualityDashboard.svelte';
import StockProjectionDashboard from './StockProjectionDashboard.svelte';

export const dashboards = [
    {
        id: 'shipping',
        label: 'Shipping',
        icon: 'fa-truck-fast',
        component: ShippingDashboard,
        permission: 'dashboard.shipping',
    },
    {
        id: 'wh-good-receipts',
        label: 'WH Receipts',
        icon: 'fa-warehouse',
        component: WHGoodReceiptDashboard,
        permission: 'dashboard.wh-good-receipts',
    },
    {
        id: 'quality',
        label: 'Quality',
        icon: 'fa-flask',
        component: QualityDashboard,
        permission: 'dashboard.quality',
    },
    {
        id: 'import-quotas',
        label: 'Import Quotas',
        icon: 'fa-chart-pie',
        component: ImportQuotasDashboard,
        permission: 'dashboard.import-quotas',
    },
    {
        id: 'stock-projection',
        label: 'Stock Projection',
        icon: 'fa-chart-line',
        component: StockProjectionDashboard,
        permission: 'dashboard.stock-projection',
    },
];
