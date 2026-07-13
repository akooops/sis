function formatDate(date) {
    return date.toISOString().split('T')[0];
}

function addDays(fromDate, days) {
    const date = new Date(fromDate);
    date.setDate(date.getDate() + days);
    return formatDate(date);
}

export function suggestShippingDates(modeOfTransportation, supplyLeadTime = 0) {
    const today = formatDate(new Date());
    const leadTime = Number(supplyLeadTime) || 0;

    if (modeOfTransportation === 'local') {
        const plannedSite = addDays(today, leadTime);

        return {
            planned_delivery_at_customs: '',
            planned_delivery_at_site: plannedSite,
            actual_delivery_at_customs: '',
            actual_delivery_at_site: plannedSite,
        };
    }

    const plannedCustoms = addDays(today, leadTime);
    const plannedSite = addDays(plannedCustoms, 15);

    return {
        planned_delivery_at_customs: plannedCustoms,
        planned_delivery_at_site: plannedSite,
        actual_delivery_at_customs: plannedCustoms,
        actual_delivery_at_site: plannedSite,
    };
}

export function suggestReceiptDates(warehouseGoodsReceiptTime = 0) {
    const today = formatDate(new Date());
    const planned = addDays(today, Number(warehouseGoodsReceiptTime) || 0);

    return {
        planned_receipt_date: planned,
        actual_receipt_date: planned,
    };
}
