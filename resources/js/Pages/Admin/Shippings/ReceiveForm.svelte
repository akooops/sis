<script>
    import QuantityInput from '../../Shared/Utils/Forms/QuantityInput.svelte';
    import DocumentsInput from '../../Shared/Utils/Forms/DocumentsInput.svelte';
    import Flatpickr from '../../Shared/Utils/Forms/Flatpickr.svelte';
    import { suggestReceiptDates } from './dateSuggestions.js';
    import { createEventDispatcher } from 'svelte';

    const dispatch = createEventDispatcher();

    export let shipping = null;

    function buildEmptyReceipt() {
        const suggested = suggestReceiptDates(shipping?.product?.warehouse_goods_receipt_time);

        return {
            quantity: '',
            batch: '',
            expiry_date: '',
            planned_receipt_date: suggested.planned_receipt_date,
            actual_receipt_date: suggested.actual_receipt_date,
            notes: '',
            documents: [],
        };
    }

    let receipts = [buildEmptyReceipt()];
    let errors = {};
    let loading = false;

    $: isUnitInteger = !!(shipping?.product?.is_unit_integer);
    $: productUnit = shipping?.product?.unit_of_measurement?.trim() || '';

    $: if (shipping) {
        receipts = [buildEmptyReceipt()];
    }

    function addReceipt() {
        receipts = [...receipts, buildEmptyReceipt()];
    }

    function removeReceipt(index) {
        if (receipts.length <= 1) {
            return;
        }
        receipts = receipts.filter((_, i) => i !== index);
    }

    function handleReceiptDocumentsChange(index, event) {
        receipts[index].documents = event.detail.files || [];
        receipts = receipts;
    }

    function appendReceipts(formData) {
        receipts.forEach((receipt, index) => {
            formData.append(`receipts[${index}][quantity]`, receipt.quantity);
            if (receipt.batch) {
                formData.append(`receipts[${index}][batch]`, receipt.batch);
            }
            if (receipt.expiry_date) {
                formData.append(`receipts[${index}][expiry_date]`, receipt.expiry_date);
            }
            if (receipt.planned_receipt_date) {
                formData.append(`receipts[${index}][planned_receipt_date]`, receipt.planned_receipt_date);
            }
            if (receipt.actual_receipt_date) {
                formData.append(`receipts[${index}][actual_receipt_date]`, receipt.actual_receipt_date);
            }
            if (receipt.notes) {
                formData.append(`receipts[${index}][notes]`, receipt.notes);
            }
            (receipt.documents || []).forEach((file) => {
                formData.append(`receipts[${index}][documents][]`, file);
            });
        });
    }

    async function handleSubmit() {
        loading = true;
        errors = {};

        try {
            const formData = new FormData();
            formData.append('_method', 'PUT');
            appendReceipts(formData);

            const response = await fetch(route('api.v1.admin.shippings.receive', { shipping: shipping.id }), {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: formData,
            });

            const data = await response.json();

            if (response.ok) {
                toast('Shipping received successfully', 'success');
                dispatch('received');
            } else if (data.errors) {
                errors = data.errors;
            } else {
                errors = { general: data.message || 'An error occurred while receiving the shipping.' };
            }
        } catch (error) {
            console.error('Network error:', error);
            errors = { general: 'Network error occurred. Please try again.' };
        } finally {
            loading = false;
        }
    }

    function handleCancel() {
        receipts = [buildEmptyReceipt()];
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
                        </div>
                    </div>
                {/if}
            </div>
        </div>

        {#each receipts as receipt, index}
            <div class="kt-card border border-border">
                <div class="kt-card-header border-b border-border flex items-center justify-between">
                    <span class="text-sm font-semibold text-mono">Warehouse Receipt #{index + 1}</span>
                    {#if receipts.length > 1}
                        <button type="button" class="kt-btn kt-btn-sm kt-btn-ghost text-destructive" on:click={() => removeReceipt(index)} disabled={loading}>
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    {/if}
                </div>
                <div class="kt-card-content p-4 space-y-5">
                    <QuantityInput
                        label="Quantity"
                        placeholder="Enter quantity..."
                        bind:value={receipt.quantity}
                        is_integer={isUnitInteger}
                        unit={productUnit}
                        disabled={loading}
                        error={errors[`receipts.${index}.quantity`] || errors[`receipts.${index}`]}
                        required={true}
                    />

                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-medium text-mono" for="batch-{index}">Batch</label>
                        <input id="batch-{index}" type="text" class="kt-input" placeholder="Enter batch..." bind:value={receipt.batch} disabled={loading} />
                        {#if errors[`receipts.${index}.batch`]}
                            <p class="text-sm text-destructive">{errors[`receipts.${index}.batch`]}</p>
                        {/if}
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-medium text-mono" for="planned-receipt-{index}">Planned Receipt Date</label>
                        <Flatpickr
                            id="planned-receipt-{index}"
                            placeholder="Select planned receipt date..."
                            bind:value={receipt.planned_receipt_date}
                            disabled={loading}
                        />
                        {#if errors[`receipts.${index}.planned_receipt_date`]}
                            <p class="text-sm text-destructive">{errors[`receipts.${index}.planned_receipt_date`]}</p>
                        {/if}
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-medium text-mono" for="actual-receipt-{index}">Actual Receipt Date</label>
                        <Flatpickr
                            id="actual-receipt-{index}"
                            placeholder="Select actual receipt date..."
                            bind:value={receipt.actual_receipt_date}
                            disabled={loading}
                        />
                        {#if errors[`receipts.${index}.actual_receipt_date`]}
                            <p class="text-sm text-destructive">{errors[`receipts.${index}.actual_receipt_date`]}</p>
                        {/if}
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-medium text-mono" for="expiry-{index}">Expiry Date</label>
                        <Flatpickr
                            id="expiry-{index}"
                            placeholder="Select expiry date..."
                            bind:value={receipt.expiry_date}
                            disabled={loading}
                        />
                        {#if errors[`receipts.${index}.expiry_date`]}
                            <p class="text-sm text-destructive">{errors[`receipts.${index}.expiry_date`]}</p>
                        {/if}
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-medium text-mono" for="receipt-notes-{index}">Notes</label>
                        <textarea id="receipt-notes-{index}" class="kt-textarea min-h-[70px]" placeholder="Enter notes..." bind:value={receipt.notes} disabled={loading}></textarea>
                    </div>

                    <DocumentsInput
                        bind:files={receipt.documents}
                        disabled={loading}
                        label="Documents"
                        helpText="PDF and CSV files only."
                        error={errors[`receipts.${index}.documents`] || errors[`receipts.${index}.documents.0`]}
                        on:change={(event) => handleReceiptDocumentsChange(index, event)}
                    />
                </div>
            </div>
        {/each}

        <button type="button" class="kt-btn kt-btn-sm kt-btn-secondary w-full" on:click={addReceipt} disabled={loading}>
            <i class="fa-solid fa-plus mr-1"></i>
            Add Another Receipt
        </button>

        <div class="flex items-center justify-end gap-2.5">
            <button type="button" class="kt-btn kt-btn-secondary" on:click={handleCancel} disabled={loading}>Cancel</button>
            <button type="submit" class="kt-btn kt-btn-primary" disabled={loading}>
                {#if loading}<i class="fa-solid fa-spinner fa-spin mr-1"></i>{/if}
                Receive Shipping
            </button>
        </div>
    </form>
</div>
