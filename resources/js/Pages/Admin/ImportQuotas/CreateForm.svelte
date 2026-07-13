<script>
    import Select2 from '../../Shared/Utils/Forms/Select2.svelte';
    import QuantityInput from '../../Shared/Utils/Forms/QuantityInput.svelte';
    import { createEventDispatcher } from 'svelte';

    const dispatch = createEventDispatcher();

    export let year = null;

    const emptyForm = () => ({
        product_id: '',
        consumption_quota: '0',
        max_quota: '',
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
            const formData = prepareFormData({
                year,
                ...form,
            });

            const response = await fetch(route('api.v1.admin.import-quotas.store'), {
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

                toast('Import quota added successfully', 'success');
                dispatch('created');
            } else if (data.errors) {
                errors = data.errors;
                if (errors.product_id && productSelectComponent) {
                    productSelectComponent.setError(true);
                }
            } else {
                errors = { general: data.message || 'An error occurred while adding the import quota.' };
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
                    <label class="text-sm font-medium text-mono" for="import-quota-product-select">
                        Product <span class="text-destructive">*</span>
                    </label>
                    <Select2
                        bind:this={productSelectComponent}
                        id="import-quota-product-select"
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
                <span class="text-sm font-semibold text-mono">Quotas</span>
            </div>
            <div class="kt-card-content p-4 space-y-5">
                <QuantityInput
                    label="Allowed quota"
                    bind:value={form.max_quota}
                    is_integer={isUnitInteger}
                    unit={productUnit}
                    disabled={loading || !form.product_id}
                    error={errors.max_quota}
                    required={true}
                />

                <QuantityInput
                    label="Consumed quota"
                    bind:value={form.consumption_quota}
                    is_integer={isUnitInteger}
                    unit={productUnit}
                    disabled={loading || !form.product_id}
                    error={errors.consumption_quota}
                />
            </div>
        </div>

        <div class="flex items-center justify-end gap-2.5">
            <button type="button" class="kt-btn kt-btn-secondary" on:click={handleCancel} disabled={loading}>
                Cancel
            </button>
            <button type="submit" class="kt-btn kt-btn-primary" disabled={loading}>
                {#if loading}
                    <i class="fa-solid fa-spinner fa-spin mr-1"></i>
                {/if}
                Add Import Quota
            </button>
        </div>
    </form>
</div>
