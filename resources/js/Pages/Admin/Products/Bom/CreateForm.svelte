<script>
    import Select2 from '../../../Shared/Utils/Forms/Select2.svelte';
    import QuantityInput from '../../../Shared/Utils/Forms/QuantityInput.svelte';
    import { createEventDispatcher } from 'svelte';

    const dispatch = createEventDispatcher();

    export let product = null;

    const emptyForm = () => ({
        component_product_id: '',
        multiplier: '',
    });

    let form = emptyForm();
    let selectedComponent = null;
    let errors = {};
    let loading = false;
    let componentSelectComponent;

    function handleComponentChange(event) {
        form.component_product_id = event.detail.value || '';

        if (event.detail.data) {
            selectedComponent = event.detail.data.id;
        } else {
            selectedComponent = null;
        }
    }

    async function handleSubmit() {
        if (!form.component_product_id) {
            errors = { component_product_id: 'Please select a component product.' };
            if (componentSelectComponent) {
                componentSelectComponent.setError(true);
            }
            return;
        }

        loading = true;
        errors = {};

        try {
            const formData = prepareFormData(form);

            const response = await fetch(route('api.v1.admin.product-boms.store', { product: product.id }), {
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
                selectedComponent = null;
                errors = {};

                if (componentSelectComponent) {
                    componentSelectComponent.setValue('');
                    componentSelectComponent.setError(false);
                }

                toast('BOM component added successfully', 'success');
                dispatch('created');
            } else if (data.errors) {
                errors = data.errors;
                if (errors.component_product_id && componentSelectComponent) {
                    componentSelectComponent.setError(true);
                }
            } else {
                errors = { general: data.message || 'An error occurred while adding the BOM component.' };
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
        selectedComponent = null;
        errors = {};

        if (componentSelectComponent) {
            componentSelectComponent.setValue('');
            componentSelectComponent.setError(false);
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
                <span class="text-sm font-semibold text-mono">Component</span>
            </div>
            <div class="kt-card-content p-4 space-y-5">
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-mono" for="component-product-select">
                        Component product <span class="text-destructive">*</span>
                    </label>
                    <Select2
                        bind:this={componentSelectComponent}
                        id="component-product-select"
                        placeholder="Search and select component..."
                        value={form.component_product_id}
                        on:change={handleComponentChange}
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
                                    results: data.products
                                        .filter((item) => item.id !== product?.id)
                                        .map((item) => ({
                                            id: item.id,
                                            text: item.name,
                                        })),
                                };
                            },
                            cache: true,
                        }}
                    />
                    {#if errors.component_product_id}
                        <p class="text-sm text-destructive">{errors.component_product_id}</p>
                    {/if}
                </div>

                <input 
                    type="number"
                    class="kt-input {errors.multiplier ? 'kt-input-error' : ''}"
                    placeholder="Enter multiplier"
                    bind:value={form.multiplier}
                    disabled={loading || !form.component_product_id}
                    min="0.001"
                    step="0.001"
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
                    Adding...
                {:else}
                    <i class="fa-solid fa-plus mr-2"></i>
                    Add Component
                {/if}
            </button>
        </div>
    </form>
</div>
