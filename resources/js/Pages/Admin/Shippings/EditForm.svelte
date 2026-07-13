<script>
    import QuantityInput from '../../Shared/Utils/Forms/QuantityInput.svelte';
    import DocumentsInput from '../../Shared/Utils/Forms/DocumentsInput.svelte';
    import ShippingDeliveryDates from './ShippingDeliveryDates.svelte';
    import { createEventDispatcher } from 'svelte';

    const dispatch = createEventDispatcher();

    export let shipping = null;

    let form = {
        quantity: '',
        mode_of_transportation: 'sea',
        carrier: '',
        tracking_number: '',
        notes: '',
        planned_delivery_at_customs: '',
        planned_delivery_at_site: '',
        actual_delivery_at_customs: '',
        actual_delivery_at_site: '',
        documents: [],
    };

    let existingDocuments = [];
    let errors = {};
    let loading = false;

    $: if (shipping) {
        form = {
            quantity: shipping.quantity ?? '',
            mode_of_transportation: shipping.mode_of_transportation ?? 'sea',
            carrier: shipping.carrier ?? '',
            tracking_number: shipping.tracking_number ?? '',
            notes: shipping.notes ?? '',
            planned_delivery_at_customs: shipping.planned_delivery_at_customs ?? '',
            planned_delivery_at_site: shipping.planned_delivery_at_site ?? '',
            actual_delivery_at_customs: shipping.actual_delivery_at_customs ?? '',
            actual_delivery_at_site: shipping.actual_delivery_at_site ?? '',
            documents: [],
        };
        existingDocuments = shipping.documents ? [...shipping.documents] : [];
    }

    $: isUnitInteger = !!(shipping?.product?.is_unit_integer);
    $: productUnit = shipping?.product?.unit_of_measurement?.trim() || '';
    $: canEdit = shipping?.can_be_updated !== false;

    function handleDocumentsChange(event) {
        form.documents = event.detail.files || [];
    }

    function handleDocumentDeleted(event) {
        existingDocuments = existingDocuments.filter((document) => document.id !== event.detail.document.id);
        dispatch('documentDeleted', event.detail);
    }

    function appendDocuments(formData) {
        form.documents.forEach((file) => {
            formData.append('documents[]', file);
        });
    }

    async function handleSubmit() {
        loading = true;
        errors = {};

        try {
            const formData = prepareFormData(form, true);
            appendDocuments(formData);

            const response = await fetch(route('api.v1.admin.shippings.update', { shipping: shipping.id }), {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: formData,
            });

            const data = await response.json();

            if (response.ok) {
                toast('Shipping updated successfully', 'success');
                dispatch('updated');
            } else if (data.errors) {
                errors = data.errors;
            } else {
                errors = { general: data.message || 'An error occurred while updating the shipping.' };
            }
        } catch (error) {
            console.error('Network error:', error);
            errors = { general: 'Network error occurred. Please try again.' };
        } finally {
            loading = false;
        }
    }

    function handleCancel() {
        errors = {};
        dispatch('canceled');
    }
</script>

<div class="space-y-6">
    <form on:submit|preventDefault={handleSubmit} class="space-y-5">
        {#if errors.general}
            <div class="p-3 bg-destructive/10 border border-destructive/20 rounded-lg">
                <p class="text-sm text-destructive">{errors.general}</p>
            </div>
        {/if}

        {#if errors.shipping}
            <div class="p-3 bg-destructive/10 border border-destructive/20 rounded-lg">
                <p class="text-sm text-destructive">{errors.shipping}</p>
            </div>
        {/if}

        <div class="kt-card border border-border">
            <div class="kt-card-header border-b border-border">
                <span class="text-sm font-semibold text-mono">Shipping</span>
            </div>
            <div class="kt-card-content p-4 space-y-3">
                {#if shipping?.code}
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-muted-foreground">Code</span>
                        <span class="text-sm font-semibold">{shipping.code}</span>
                    </div>
                {/if}
                {#if shipping?.product}
                    <div class="flex items-center gap-3">
                        <img src={shipping.product.image_url} alt={shipping.product.name} class="w-[30px] h-[30px] rounded-lg object-cover" />
                        <div>
                            <p class="text-sm font-medium">{shipping.product.name}</p>
                            {#if shipping.product.code}
                                <p class="text-xs text-muted-foreground">{shipping.product.code}</p>
                            {/if}
                        </div>
                    </div>
                {/if}
            </div>
        </div>

        <div class="kt-card border border-border">
            <div class="kt-card-header border-b border-border">
                <span class="text-sm font-semibold text-mono">Shipping Details</span>
            </div>
            <div class="kt-card-content p-4 space-y-5">
                <QuantityInput
                    label="Quantity"
                    placeholder="Enter quantity..."
                    bind:value={form.quantity}
                    is_integer={isUnitInteger}
                    unit={productUnit}
                    disabled={loading || !canEdit}
                    error={errors.quantity}
                    required={true}
                />

                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-mono" for="edit-mode-of-transportation">Mode of Transportation</label>
                    <select id="edit-mode-of-transportation" class="kt-select" bind:value={form.mode_of_transportation} disabled={loading || !canEdit}>
                        <option value="sea">Sea</option>
                        <option value="air">Air</option>
                        <option value="local">Local</option>
                    </select>
                    {#if errors.mode_of_transportation}
                        <p class="text-sm text-destructive">{errors.mode_of_transportation}</p>
                    {/if}
                </div>

                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-mono" for="edit-carrier">Carrier</label>
                    <input id="edit-carrier" type="text" class="kt-input" placeholder="Enter carrier..." bind:value={form.carrier} disabled={loading || !canEdit} />
                    {#if errors.carrier}
                        <p class="text-sm text-destructive">{errors.carrier}</p>
                    {/if}
                </div>

                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-mono" for="edit-tracking-number">Tracking Number</label>
                    <input id="edit-tracking-number" type="text" class="kt-input" placeholder="Enter tracking number..." bind:value={form.tracking_number} disabled={loading || !canEdit} />
                    {#if errors.tracking_number}
                        <p class="text-sm text-destructive">{errors.tracking_number}</p>
                    {/if}
                </div>

                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-mono" for="edit-shipping-notes">Notes</label>
                    <textarea id="edit-shipping-notes" class="kt-textarea min-h-[90px]" placeholder="Enter notes..." bind:value={form.notes} disabled={loading || !canEdit}></textarea>
                    {#if errors.notes}
                        <p class="text-sm text-destructive">{errors.notes}</p>
                    {/if}
                </div>
            </div>
        </div>

        <div class="kt-card border border-border">
            <div class="kt-card-header border-b border-border">
                <span class="text-sm font-semibold text-mono">Delivery Dates</span>
            </div>
            <div class="kt-card-content p-4">
                <ShippingDeliveryDates
                    modeOfTransportation={form.mode_of_transportation}
                    disabled={loading || !canEdit}
                    {errors}
                    bind:planned_delivery_at_customs={form.planned_delivery_at_customs}
                    bind:planned_delivery_at_site={form.planned_delivery_at_site}
                    bind:actual_delivery_at_customs={form.actual_delivery_at_customs}
                    bind:actual_delivery_at_site={form.actual_delivery_at_site}
                />
            </div>
        </div>

        <div class="kt-card border border-border">
            <div class="kt-card-header border-b border-border">
                <span class="text-sm font-semibold text-mono">Documents</span>
            </div>
            <div class="kt-card-content p-4">
                <DocumentsInput
                    bind:files={form.documents}
                    existingDocuments={existingDocuments}
                    disabled={loading || !canEdit}
                    allowDelete={canEdit}
                    label=""
                    helpText={canEdit ? 'PDF and CSV files only. You can add multiple files.' : ''}
                    error={errors.documents || errors['documents.0']}
                    on:change={handleDocumentsChange}
                    on:deleted={handleDocumentDeleted}
                />
            </div>
        </div>

        <div class="flex items-center justify-end gap-2.5">
            <button type="button" class="kt-btn kt-btn-secondary" on:click={handleCancel} disabled={loading}>Cancel</button>
            {#if canEdit}
                <button type="submit" class="kt-btn kt-btn-primary" disabled={loading}>
                    {#if loading}<i class="fa-solid fa-spinner fa-spin mr-1"></i>{/if}
                    Save Changes
                </button>
            {/if}
        </div>
    </form>
</div>
