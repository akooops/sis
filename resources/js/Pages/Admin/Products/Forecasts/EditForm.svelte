<script>
    import QuantityInput from '../../../Shared/Utils/Forms/QuantityInput.svelte';
    import { createEventDispatcher } from 'svelte';

    const dispatch = createEventDispatcher();

    export let demandForecast = null;
    export let product = null;

    let form = {
        forecast_month: '',
        quantity: '',
    };

    let errors = {};
    let loading = false;

    $: if (demandForecast) {
        form = {
            forecast_month: demandForecast.forecast_month ?? '',
            quantity: demandForecast.quantity ?? '',
        };
    }

    async function handleSubmit() {
        loading = true;
        errors = {};

        try {
            const formData = prepareFormData(form, true);

            const response = await fetch(route('api.v1.admin.demand-forecasts.update', { demandForecast: demandForecast.id }), {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: formData,
            });

            const data = await response.json();

            if (response.ok) {
                toast('Demand forecast updated successfully', 'success');
                dispatch('updated');
            } else if (data.errors) {
                errors = Object.fromEntries(
                    Object.entries(data.errors).map(([key, value]) => [key, Array.isArray(value) ? value[0] : value])
                );
            } else {
                errors = { general: data.message || 'An error occurred while updating the demand forecast.' };
            }
        } catch (error) {
            console.error('Network error:', error);
            errors = { general: 'Network error occurred. Please try again.' };
        } finally {
            loading = false;
        }
    }

    function handleCancel() {
        if (demandForecast) {
            form = {
                forecast_month: demandForecast.forecast_month ?? '',
                quantity: demandForecast.quantity ?? '',
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
                <span class="text-sm font-semibold text-mono">Month</span>
            </div>
            <div class="kt-card-content p-4">
                <input
                    type="month"
                    class="kt-input {errors.forecast_month ? 'kt-input-error' : ''}"
                    bind:value={form.forecast_month}
                    disabled={loading}
                />
                {#if errors.forecast_month}
                    <p class="text-sm text-destructive mt-2">{errors.forecast_month}</p>
                {/if}
            </div>
        </div>

        <div class="kt-card border border-border">
            <div class="kt-card-header border-b border-border">
                <span class="text-sm font-semibold text-mono">Quantity</span>
            </div>
            <div class="kt-card-content p-4">
                <QuantityInput
                    bind:value={form.quantity}
                    is_integer={product?.is_unit_integer ?? false}
                    unit={product?.unit_of_measurement ?? ''}
                    disabled={loading}
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
                    Updating...
                {:else}
                    <i class="fa-solid fa-save mr-2"></i>
                    Update Forecast
                {/if}
            </button>
        </div>
    </form>
</div>
