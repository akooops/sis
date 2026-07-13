function formatDate(date) {
    return date.toISOString().split('T')[0];
}

function addDays(fromDate, days) {
    const date = new Date(fromDate);
    date.setDate(date.getDate() + days);
    return formatDate(date);
}

export function suggestQcDates(qcInspectionTime = 0) {
    const planned = addDays(formatDate(new Date()), Number(qcInspectionTime) || 0);
    return { planned_qc_date: planned };
}

export function suggestQaDates(qaInspectionTime = 0) {
    const planned = addDays(formatDate(new Date()), Number(qaInspectionTime) || 0);
    return { planned_qa_date: planned };
}

export function suggestQpDates(qpInspectionTime = 0) {
    const planned = addDays(formatDate(new Date()), Number(qpInspectionTime) || 0);
    return { planned_qp_date: planned };
}
