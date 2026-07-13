<script>
    import QuantityInput from '../../../Shared/Utils/Forms/QuantityInput.svelte';
    import { createEventDispatcher } from 'svelte';

    const dispatch = createEventDispatcher();

    export let product = null;

    const emptyForm = () => ({
        forecast_month: '',
        quantity: '',
    });

    let form = emptyForm();
    let errors = {};
    let loading = false;

    async function handleSubmit() {
        if (!form.forecast_month) {
            errors = { forecast_month: 'Please select a month.' };
            return;
        }

        loading = true;
        errors = {};

        try {
            const formData = prepareFormData(form);

            const response = await fetch(route('api.v1.admin.demand-forecasts.store', { product: product.id }), {
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
                errors = {};
                toast('Demand forecast added successfully', 'success');
                dispatch('created');
            } else if (data.errors) {
                errors = Object.fromEntries(
                    Object.entries(data.errors).map(([key, value]) => [key, Array.isArray(value) ? value[0] : value])
                );
            } else {
                errors = { general: data.message || 'An error occurred while adding the demand forecast.' };
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
                <span class="text-sm font-semibold text-mono">Monthly demand</span>
            </div>
            <div class="kt-card-content p-4 space-y-5">
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-mono" for="forecast-month">
                        Month <span class="text-destructive">*</span>
                    </label>
                    <input
                        id="forecast-month"
                        type="month"
                        class="kt-input {errors.forecast_month ? 'kt-input-error' : ''}"
                        bind:value={form.forecast_month}
                        disabled={loading}
                    />
                    {#if errors.forecast_month}
                        <p class="text-sm text-destructive">{errors.forecast_month}</p>
                    {/if}
                </div>

                <QuantityInput
                    label="Monthly quantity"
                    bind:value={form.quantity}
                    is_integer={product?.is_unit_integer ?? false}
                    unit={product?.unit_of_measurement ?? ''}
                    disabled={loading || !form.forecast_month}
                    error={errors.quantity}
                    required={true}
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
                    Adding...
                {:else}
                    <i class="fa-solid fa-plus mr-2"></i>
                    Add Forecast
                {/if}
            </button>
        </div>
    </form>
</div>
