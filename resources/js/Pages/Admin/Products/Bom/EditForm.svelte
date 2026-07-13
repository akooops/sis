<script>
    import QuantityInput from '../../../Shared/Utils/Forms/QuantityInput.svelte';
    import { createEventDispatcher } from 'svelte';

    const dispatch = createEventDispatcher();

    export let productBom = null;

    let form = {
        multiplier: '',
    };

    let errors = {};
    let loading = false;

    $: if (productBom) {
        form = {
            multiplier: productBom.multiplier ?? '',
        };
    }

    async function handleSubmit() {
        loading = true;
        errors = {};

        try {
            const formData = prepareFormData(form, true);

            const response = await fetch(route('api.v1.admin.product-boms.update', { productBom: productBom.id }), {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: formData,
            });

            const data = await response.json();

            if (response.ok) {
                toast('BOM component updated successfully', 'success');
                dispatch('updated');
            } else if (data.errors) {
                errors = data.errors;
            } else {
                errors = { general: data.message || 'An error occurred while updating the BOM component.' };
            }
        } catch (error) {
            console.error('Network error:', error);
            errors = { general: 'Network error occurred. Please try again.' };
        } finally {
            loading = false;
        }
    }

    function handleCancel() {
        if (productBom) {
            form = {
                multiplier: productBom.multiplier ?? '',
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
                <span class="text-sm font-semibold text-mono">Component</span>
            </div>
            <div class="kt-card-content p-4">
                {#if productBom?.component_product}
                    <div class="flex items-center gap-3">
                        <img
                            src={productBom.component_product.image_url}
                            alt={productBom.component_product.name}
                            class="w-[30px] h-[30px] rounded-lg object-cover"
                        />
                        <div class="flex flex-col gap-1">
                            <span class="text-sm font-medium text-secondary-foreground">
                                {productBom.component_product.name}
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
                <span class="text-sm font-semibold text-mono">Quantity</span>
            </div>
            <div class="kt-card-content p-4">
                <input 
                    type="number"
                    class="kt-input {errors.multiplier ? 'kt-input-error' : ''}"
                    placeholder="Enter multiplier"
                    label="Multiplier"
                    bind:value={form.multiplier}
                    min="0.001"
                    step="0.001"
                    disabled={loading}
                    error={errors.multiplier}
                />
                {#if errors.multiplier}
                    <p class="text-sm text-destructive">{errors.multiplier}</p>
                {/if}
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
                    Update Component
                {/if}
            </button>
        </div>
    </form>
</div>
