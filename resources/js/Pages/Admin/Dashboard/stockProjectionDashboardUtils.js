export const dateRangePresets = [
    { id: 'next_7_days', label: 'Next 7 days' },
    { id: 'next_28_days', label: 'Next 28 days' },
    { id: 'next_3_months', label: 'Next 3 months' },
    { id: 'next_6_months', label: 'Next 6 months' },
    { id: 'next_12_months', label: 'Next 12 months' },
    { id: 'custom', label: 'Custom' },
];

export function getDateStr(date) {
    return date.toISOString().split('T')[0];
}

export function computePresetDates(preset) {
    const start = new Date();
    const end = new Date();

    switch (preset) {
        case 'next_7_days':
            end.setDate(start.getDate() + 6);
            break;
        case 'next_28_days':
            end.setDate(start.getDate() + 27);
            break;
        case 'next_3_months':
            end.setMonth(start.getMonth() + 3);
            break;
        case 'next_6_months':
            end.setMonth(start.getMonth() + 6);
            break;
        case 'next_12_months':
            end.setFullYear(start.getFullYear() + 1);
            break;
        default:
            return null;
    }

    return {
        from: getDateStr(start),
        to: getDateStr(end),
    };
}

export const defaultDateRangePreset = 'next_28_days';

export function formatQuantity(product, value) {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    const unit = product?.unit_of_measurement?.trim();
    const numeric = Number(value);
    const formatted = product?.is_unit_integer
        ? numeric.toLocaleString('en-US', { maximumFractionDigits: 0 })
        : numeric.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 3 });

    return unit ? `${formatted} ${unit}` : formatted;
}
