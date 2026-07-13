<script>
    import Select2 from '../../../Shared/Utils/Forms/Select2.svelte';
    import QuantityInput from '../../../Shared/Utils/Forms/QuantityInput.svelte';
    import { createEventDispatcher } from 'svelte';

    const dispatch = createEventDispatcher();

    export let supplier = null;

    const emptyForm = () => ({
        product_id: '',
        supply_lead_time: '',
        min_order_quantity: '',
        has_order_quantity_step: false,
        order_quantity_step: '',
        notes: '',
    });

    let form = emptyForm();
    let selectedProduct = null;
    let errors = {};
    let loading = false;
    let productSelectComponent;

    $: isUnitInteger = !!(selectedProduct?.is_unit_integer);
    $: productUnit = selectedProduct?.unit_of_measurement?.trim() || '';

    function handleProductChange(event) {
        form.product_id = event.detail.value || '';

        if (event.detail.data) {
            selectedProduct = {
                is_unit_integer: event.detail.data.is_unit_integer,
                unit_of_measurement: event.detail.data.unit_of_measurement,
            };
        } else {
            selectedProduct = null;
        }

        if (productSelectComponent) {
            productSelectComponent.setError(false);
        }
    }

    async function handleSubmit() {
        if (!form.product_id) {
            errors = { product_id: 'Please select a product.' };
            if (productSelectComponent) {
                productSelectComponent.setError(true);
            }
            return;
        }

        loading = true;
        errors = {};

        try {
            const formData = prepareFormData(form);

            const response = await fetch(route('api.v1.admin.supplier-products.store', { supplier: supplier.id }), {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: formData,
            });

            const data = await response.json();

            if (response.ok) {
                form = emptyForm();
                selectedProduct = null;
                errors = {};

                if (productSelectComponent) {
                    productSelectComponent.setValue('');
                    productSelectComponent.setError(false);
                }

                toast('Supplier product added successfully', 'success');
                dispatch('created');
            } else if (data.errors) {
                errors = data.errors;
                if (errors.product_id && productSelectComponent) {
                    productSelectComponent.setError(true);
                }
            } else {
                errors = { general: data.message || 'An error occurred while adding the supplier product.' };
            }
        } catch (error) {
            console.error('Network error:', error);
            errors = { general: 'Network error occurred. Please try again.' };
        } finally {
            loading = false;
        }
    }

    function handleCancel() {
        form = emptyForm();
        selectedProduct = null;
        errors = {};

        if (productSelectComponent) {
            productSelectComponent.setValue('');
            productSelectComponent.setError(false);
        }

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
            <div class="kt-card-content p-4 space-y-5">
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-mono" for="product-select">
                        Product <span class="text-destructive">*</span>
                    </label>
                    <Select2
                        bind:this={productSelectComponent}
                        id="product-select"
                        placeholder="Search and select product..."
                        value={form.product_id}
                        on:change={handleProductChange}
                        disabled={loading}
                        ajax={{
                            url: route('api.v1.admin.products.index'),
                            dataType: 'json',
                            delay: 300,
                            data: function(params) {
                                return {
                                    search: params.term,
                                    per_page: 10,
                                };
                            },
                            processResults: function(data) {
                                return {
                                    results: data.products.map(product => ({
                                        id: product.id,
                                        text: product.name,
                                        is_unit_integer: product.is_unit_integer,
                                        unit_of_measurement: product.unit_of_measurement,
                                    }))
                                };
                            },
                            cache: true
                        }}
                    />
                    {#if errors.product_id}
                        <p class="text-sm text-destructive">{errors.product_id}</p>
                    {/if}
                </div>
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
                    disabled={loading || !form.product_id}
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
                        disabled={loading || !form.product_id}
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
                    Adding...
                {:else}
                    <i class="fa-solid fa-plus mr-2"></i>
                    Add Product
                {/if}
            </button>
        </div>
    </form>
</div>
