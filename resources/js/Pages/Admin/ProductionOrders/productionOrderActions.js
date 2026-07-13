export function getAdvanceLabel(productionOrder) {
    if (!productionOrder?.can_be_advanced) {
        return '';
    }

    if (productionOrder.next_status === 'confirmed') {
        return 'Confirm';
    }

    if (productionOrder.next_status === 'producing') {
        return 'Start Producing';
    }

    return 'Advance';
}

export async function advanceProductionOrder(productionOrder) {
    const label = getAdvanceLabel(productionOrder);
    const confirmed = confirm(`Advance production order "${productionOrder.code || productionOrder.batch || `#${productionOrder.id}`}" to the next status?\n\nAction: ${label}`);

    if (!confirmed) {
        return false;
    }

    const formData = new FormData();
    formData.append('_method', 'PUT');

    const response = await fetch(route('api.v1.admin.production-orders.advance', { productionOrder: productionOrder.id }), {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        },
        body: formData,
    });

    const data = await response.json();

    if (response.ok) {
        toast('Production order advanced successfully', 'success');
        return true;
    }

    if (response.status === 422 && data.errors) {
        let errorMessage = 'Cannot advance production order:\n';
        Object.entries(data.errors).forEach(([, message]) => {
            errorMessage += `• ${Array.isArray(message) ? message[0] : message}\n`;
        });
        toast(errorMessage, 'error');
    } else {
        toast(data.message || 'An error occurred while advancing the production order.', 'error');
    }

    return false;
}
