export async function putRecordAction(routeName, params, formData = null, successMessage = 'Action completed successfully') {
    const body = formData || new FormData();
    if (!formData) {
        body.append('_method', 'PUT');
    } else if (!body.has('_method')) {
        body.append('_method', 'PUT');
    }

    const response = await fetch(route(routeName, params), {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        },
        body,
    });

    const data = await response.json();

    if (response.ok) {
        toast(successMessage, 'success');
        return { ok: true, data };
    }

    if (response.status === 422 && data.errors) {
        return { ok: false, errors: data.errors };
    }

    toast(data.message || 'An error occurred.', 'error');
    return { ok: false, errors: data.errors || { general: data.message } };
}

export function formatWarehouseStatus(status) {
    if (!status) return 'N/A';
    return status.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

export function formatInventoryType(type) {
    if (!type) return 'N/A';
    return type.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

export function getInventoryTypeBadgeClass(type) {
    if (type === 'incoming') return 'kt-badge-success';
    if (type === 'consumption') return 'kt-badge-info';
    if (type === 'transfer') return 'kt-badge-primary';
    if (['scrap', 'expired'].includes(type)) return 'kt-badge-destructive';
    return 'kt-badge-secondary';
}

export function getWarehouseStatusBadgeClass(status) {
    if (status === 'pending') return 'kt-badge-warning';
    if (['received', 'completed', 'approved', 'released'].includes(status)) return 'kt-badge-success';
    if (['partially_approved', 'partially_released', 'submitted_to_qc', 'submitted_to_qa', 'submitted_to_qp'].includes(status)) return 'kt-badge-info';
    if (status === 'rejected') return 'kt-badge-destructive';
    if (status === 'cancelled') return 'kt-badge-destructive';
    return 'kt-badge-secondary';
}
