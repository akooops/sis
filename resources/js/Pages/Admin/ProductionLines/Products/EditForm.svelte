<script>
    import QuantityInput from '../../../Shared/Utils/Forms/QuantityInput.svelte';
    import { createEventDispatcher } from 'svelte';

    const dispatch = createEventDispatcher();

    export let lineProduct = null;

    let form = {
        default_daily_one_shift_capacity: '',
        default_daily_two_shift_capacity: '',
        default_daily_three_shift_capacity: '',
    };

    let errors = {};
    let loading = false;

    $: if (lineProduct) {
        form = {
            default_daily_one_shift_capacity: lineProduct.default_daily_one_shift_capacity ?? '',
            default_daily_two_shift_capacity: lineProduct.default_daily_two_shift_capacity ?? '',
            default_daily_three_shift_capacity: lineProduct.default_daily_three_shift_capacity ?? '',
        };
    }

    $: isUnitInteger = !!(lineProduct?.product?.is_unit_integer);
    $: productUnit = lineProduct?.product?.unit_of_measurement?.trim() || '';

    async function handleSubmit() {
        loading = true;
        errors = {};

        try {
            const formData = prepareFormData(form, true);

            const response = await fetch(route('api.v1.admin.line-products.update', { lineProduct: lineProduct.id }), {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: formData,
            });

            const data = await response.json();

            if (response.ok) {
                toast('Line product updated successfully', 'success');
                dispatch('updated');
            } else if (data.errors) {
                errors = data.errors;
            } else {
                errors = { general: data.message || 'An error occurred while updating the line product.' };
            }
        } catch (error) {
            console.error('Network error:', error);
            errors = { general: 'Network error occurred. Please try again.' };
        } finally {
            loading = false;
        }
    }

    function handleCancel() {
        if (lineProduct) {
            form = {
                default_daily_one_shift_capacity: lineProduct.default_daily_one_shift_capacity ?? '',
                default_daily_two_shift_capacity: lineProduct.default_daily_two_shift_capacity ?? '',
                default_daily_three_shift_capacity: lineProduct.default_daily_three_shift_capacity ?? '',
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
                {#if lineProduct?.product}
                    <div class="flex items-center gap-3">
                        <img
                            src={lineProduct.product.image_url}
                            alt={lineProduct.product.name}
                            class="w-[30px] h-[30px] rounded-lg object-cover"
                        />
                        <div class="flex flex-col gap-1">
                            <span class="text-sm font-medium text-secondary-foreground">
                                {lineProduct.product.name}
                            </span>
                            {#if lineProduct.product.code}
                                <span class="text-xs text-muted-foreground">{lineProduct.product.code}</span>
                            {/if}
                        </div>
                    </div>
                {:else}
                    <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">N/A</span>
                {/if}
            </div>
        </div>

        <div class="kt-card border border-border">
            <div class="kt-card-header border-b border-border">
                <span class="text-sm font-semibold text-mono">Daily Capacities</span>
            </div>
            <div class="kt-card-content p-4 space-y-5">
                <QuantityInput
                    label="1-shift daily capacity"
                    bind:value={form.default_daily_one_shift_capacity}
                    is_integer={isUnitInteger}
                    unit={productUnit}
                    disabled={loading}
                    error={errors.default_daily_one_shift_capacity}
                />

                <QuantityInput
                    label="2-shift daily capacity"
                    bind:value={form.default_daily_two_shift_capacity}
                    is_integer={isUnitInteger}
                    unit={productUnit}
                    disabled={loading}
                    error={errors.default_daily_two_shift_capacity}
                />

                <QuantityInput
                    label="3-shift daily capacity"
                    bind:value={form.default_daily_three_shift_capacity}
                    is_integer={isUnitInteger}
                    unit={productUnit}
                    disabled={loading}
                    error={errors.default_daily_three_shift_capacity}
                />
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
