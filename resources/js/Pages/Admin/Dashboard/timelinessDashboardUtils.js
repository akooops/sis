export const dateRangePresets = [
    { id: 'last_7_days', label: 'Last 7 days' },
    { id: 'last_28_days', label: 'Last 28 days' },
    { id: 'last_3_months', label: 'Last 3 months' },
    { id: 'last_6_months', label: 'Last 6 months' },
    { id: 'last_12_months', label: 'Last 12 months' },
    { id: 'lifetime', label: 'Lifetime' },
    { id: 'custom', label: 'Custom' },
];

export function getDateStr(date) {
    return date.toISOString().split('T')[0];
}

export function computePresetDates(preset) {
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

export const defaultDateRangePreset = 'last_28_days';

export function formatDiffDays(diffDays) {
    if (diffDays === null || diffDays === undefined) {
        return 'N/A';
    }

    const prefix = diffDays > 0 ? '+' : '';
    const label = Math.abs(diffDays) === 1 ? 'day' : 'days';
    return `${prefix}${diffDays} ${label}`;
}
