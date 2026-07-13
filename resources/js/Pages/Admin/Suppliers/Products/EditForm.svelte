<script>
    import QuantityInput from '../../../Shared/Utils/Forms/QuantityInput.svelte';
    import { createEventDispatcher } from 'svelte';

    const dispatch = createEventDispatcher();

    export let supplierProduct = null;

    let form = {
        supply_lead_time: '',
        min_order_quantity: '',
        has_order_quantity_step: false,
        order_quantity_step: '',
        notes: '',
    };

    let errors = {};
    let loading = false;

    $: if (supplierProduct) {
        form = {
            supply_lead_time: supplierProduct.supply_lead_time ?? '',
            min_order_quantity: supplierProduct.min_order_quantity ?? '',
            has_order_quantity_step: !!supplierProduct.has_order_quantity_step,
            order_quantity_step: supplierProduct.order_quantity_step ?? '',
            notes: supplierProduct.notes || '',
        };
    }

    $: isUnitInteger = !!(supplierProduct?.product?.is_unit_integer);
    $: productUnit = supplierProduct?.product?.unit_of_measurement?.trim() || '';

    async function handleSubmit() {
        loading = true;
        errors = {};

        try {
            const formData = prepareFormData(form, true);

            const response = await fetch(route('api.v1.admin.supplier-products.update', { supplierProduct: supplierProduct.id }), {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: formData,
            });

            const data = await response.json();

            if (response.ok) {
                toast('Supplier product updated successfully', 'success');
                dispatch('updated');
            } else if (data.errors) {
                errors = data.errors;
            } else {
                errors = { general: data.message || 'An error occurred while updating the supplier product.' };
            }
        } catch (error) {
            console.error('Network error:', error);
            errors = { general: 'Network error occurred. Please try again.' };
        } finally {
            loading = false;
        }
    }

    function handleCancel() {
        if (supplierProduct) {
            form = {
                supply_lead_time: supplierProduct.supply_lead_time ?? '',
                min_order_quantity: supplierProduct.min_order_quantity ?? '',
                has_order_quantity_step: !!supplierProduct.has_order_quantity_step,
                order_quantity_step: supplierProduct.order_quantity_step ?? '',
                notes: supplierProduct.notes || '',
            };
        }
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

        <div class="kt-card border border-border">
            <div class="kt-card-header border-b border-border">
                <span class="text-sm font-semibold text-mono">Product</span>
            </div>
            <div class="kt-card-content p-4">
                {#if supplierProduct?.product}
                    <div class="flex items-center gap-3">
                        <img
                            src={supplierProduct.product.image_url}
                            alt={supplierProduct.product.name}
                            class="w-[30px] h-[30px] rounded-lg object-cover"
                        />
                        <div class="flex flex-col gap-1">
                            <span class="text-sm font-medium text-secondary-foreground">
                                {supplierProduct.product.name}
                            </span>
                        </div>
                    </div>
                {:else}
                    <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">N/A</span>
                {/if}
            </div>
        </div>

        <div class="kt-card border border-border">
            <div class="kt-card-header border-b border-border">
                <span class="text-sm font-semibold text-mono">Ordering</span>
            </div>
            <div class="kt-card-content p-4 space-y-5">
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-mono" for="supply_lead_time">Supply lead time (days)</label>
                    <input
                        id="supply_lead_time"
                        type="number"
                        min="0"
                        class="kt-input {errors.supply_lead_time ? 'kt-input-error' : ''}"
                        bind:value={form.supply_lead_time}
                        disabled={loading}
                    />
                    {#if errors.supply_lead_time}
                        <p class="text-sm text-destructive">{errors.supply_lead_time}</p>
                    {/if}
                </div>

                <QuantityInput
                    label="Min order quantity"
                    bind:value={form.min_order_quantity}
                    is_integer={isUnitInteger}
                    unit={productUnit}
                    disabled={loading}
                    error={errors.min_order_quantity}
                />

                <div class="flex items-center gap-2">
                    <input
                        class="kt-switch"
                        type="checkbox"
                        id="has_order_quantity_step"
                        bind:checked={form.has_order_quantity_step}
                        disabled={loading}
                    />
                    <label class="kt-label text-sm" for="has_order_quantity_step">
                        Has order quantity step
                    </label>
                </div>

                {#if form.has_order_quantity_step}
                    <QuantityInput
                        label="Order quantity step"
                        bind:value={form.order_quantity_step}
                        is_integer={isUnitInteger}
                        unit={productUnit}
                        disabled={loading}
                        error={errors.order_quantity_step}
                    />
                {/if}

                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-mono" for="notes">Notes</label>
                    <textarea
                        id="notes"
                        class="kt-textarea min-h-[100px] {errors.notes ? 'kt-input-error' : ''}"
                        placeholder="Optional notes"
                        bind:value={form.notes}
                        disabled={loading}
                    ></textarea>
                    {#if errors.notes}
                        <p class="text-sm text-destructive">{errors.notes}</p>
                    {/if}
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-border">
            <button type="button" class="kt-btn kt-btn-secondary" on:click={handleCancel} disabled={loading}>
                Cancel
            </button>
            <button type="submit" class="kt-btn kt-btn-primary" disabled={loading}>
                {#if loading}
                    <i class="fa-solid fa-spinner fa-spin mr-2"></i>
                    Updating...
                {:else}
                    <i class="fa-solid fa-save mr-2"></i>
                    Update Product
                {/if}
            </button>
        </div>
    </form>
</div>
