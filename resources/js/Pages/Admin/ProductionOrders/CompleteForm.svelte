<script>
    import QuantityInput from '../../Shared/Utils/Forms/QuantityInput.svelte';
    import { createEventDispatcher } from 'svelte';

    const dispatch = createEventDispatcher();

    export let productionOrder = null;

    let form = { quantity_produced: '' };
    let errors = {};
    let loading = false;

    $: if (productionOrder) {
        form = {
            quantity_produced: productionOrder.quantity_planned ?? '',
        };
    }

    $: isUnitInteger = !!(productionOrder?.product?.is_unit_integer);
    $: productUnit = productionOrder?.product?.unit_of_measurement?.trim() || '';
    $: scrapRate = productionOrder?.product?.scrap_rate ?? 0;
    $: incomingPreview = form.quantity_produced
        ? (Number(form.quantity_produced) * (1 - Number(scrapRate) / 100)).toFixed(isUnitInteger ? 0 : 3)
        : null;

    async function handleSubmit() {
        loading = true;
        errors = {};

        try {
            const formData = prepareFormData(form);
            formData.append('_method', 'PUT');

            const response = await fetch(route('api.v1.admin.production-orders.complete', { productionOrder: productionOrder.id }), {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: formData,
            });

            const data = await response.json();

            if (response.ok) {
                toast('Production order completed successfully', 'success');
                dispatch('completed');
            } else if (data.errors) {
                errors = data.errors;
            } else {
                errors = { general: data.message || 'An error occurred while completing the production order.' };
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

        {#if errors.production_order}
            <div class="p-3 bg-destructive/10 border border-destructive/20 rounded-lg">
                <p class="text-sm text-destructive">{errors.production_order}</p>
            </div>
        {/if}

        <div class="kt-card border border-border">
            <div class="kt-card-header border-b border-border">
                <span class="text-sm font-semibold text-mono">Production Order</span>
            </div>
            <div class="kt-card-content p-4 space-y-3">
                {#if productionOrder?.batch}
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-muted-foreground">Batch</span>
                        <span class="text-sm font-semibold">{productionOrder.batch}</span>
                    </div>
                {/if}
                {#if productionOrder?.product}
                    <div class="flex items-center gap-3">
                        <img src={productionOrder.product.image_url} alt={productionOrder.product.name} class="w-[30px] h-[30px] rounded-lg object-cover" />
                        <div>
                            <p class="text-sm font-medium">{productionOrder.product.name}</p>
                            {#if productionOrder.product.code}
                                <p class="text-xs text-muted-foreground">{productionOrder.product.code}</p>
                            {/if}
                        </div>
                    </div>
                {/if}
            </div>
        </div>

        <div class="kt-card border border-border">
            <div class="kt-card-header border-b border-border">
                <span class="text-sm font-semibold text-mono">Completion</span>
            </div>
            <div class="kt-card-content p-4 space-y-5">
                <QuantityInput
                    label="Quantity produced"
                    bind:value={form.quantity_produced}
                    is_integer={isUnitInteger}
                    unit={productUnit}
                    disabled={loading}
                    error={errors.quantity_produced}
                    required={true}
                />

                <div class="rounded-lg border border-border bg-muted/40 p-3 text-sm text-secondary-foreground space-y-1">
                    <p>On completion, inventory records will be created:</p>
                    <ul class="list-disc ps-5 space-y-1">
                        <li>Incoming finished product: quantity produced × (1 − scrap rate {scrapRate}%){#if incomingPreview} ≈ {incomingPreview}{#if productUnit} {productUnit}{/if}{/if}</li>
                        <li>Consumption for each BOM component: quantity produced × BOM multiplier</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2.5">
            <button type="button" class="kt-btn kt-btn-secondary" on:click={handleCancel} disabled={loading}>Cancel</button>
            <button type="submit" class="kt-btn kt-btn-primary" disabled={loading}>
                {#if loading}<i class="fa-solid fa-spinner fa-spin mr-1"></i>{/if}
                Complete Production Order
            </button>
        </div>
    </form>
</div>
