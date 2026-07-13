export function getAdvanceLabel(shipping) {
    if (!shipping?.can_be_advanced) {
        return '';
    }

    if (shipping.next_status === 'at_customs') {
        return 'Mark At Customs';
    }

    if (shipping.next_status === 'delivered') {
        return 'Mark Delivered';
    }

    return 'Advance';
}

export async function advanceShipping(shipping) {
    const label = getAdvanceLabel(shipping);
    const confirmed = confirm(`Advance shipping "${shipping.code || `#${shipping.id}`}" to the next status?\n\nAction: ${label}`);

    if (!confirmed) {
        return false;
    }

    const formData = new FormData();
    formData.append('_method', 'PUT');

    const response = await fetch(route('api.v1.admin.shippings.advance', { shipping: shipping.id }), {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        },
        body: formData,
    });

    const data = await response.json();

    if (response.ok) {
        toast('Shipping advanced successfully', 'success');
        return true;
    }

    if (response.status === 422 && data.errors) {
        let errorMessage = 'Cannot advance shipping:\n';
        Object.entries(data.errors).forEach(([, message]) => {
            errorMessage += `• ${Array.isArray(message) ? message[0] : message}\n`;
        });
        toast(errorMessage, 'error');
    } else {
        toast(data.message || 'An error occurred while advancing the shipping.', 'error');
    }

    return false;
}
