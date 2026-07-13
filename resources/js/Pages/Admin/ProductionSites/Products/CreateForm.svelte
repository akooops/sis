<script>
    import Select2 from '../../../Shared/Utils/Forms/Select2.svelte';
    import { createEventDispatcher } from 'svelte';

    const dispatch = createEventDispatcher();

    export let productionSite = null;

    let form = {
        product_id: '',
    };

    let errors = {};
    let loading = false;
    let productSelectComponent;

    function handleProductChange(event) {
        form.product_id = event.detail.value || '';

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
            const formData = new FormData();
            formData.append('product_id', form.product_id);

            const response = await fetch(route('api.v1.admin.site-products.store', { productionSite: productionSite.id }), {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: formData,
            });

            const data = await response.json();

            if (response.ok) {
                form = { product_id: '' };
                errors = {};

                if (productSelectComponent) {
                    productSelectComponent.setValue('');
                    productSelectComponent.setError(false);
                }

                toast('Site product added successfully', 'success');
                dispatch('created');
            } else if (data.errors) {
                errors = data.errors;

                if (errors.product_id && productSelectComponent) {
                    productSelectComponent.setError(true);
                }
            } else {
                errors = { general: data.message || 'An error occurred while adding the site product.' };
            }
        } catch (error) {
            console.error('Network error:', error);
            errors = { general: 'Network error occurred. Please try again.' };
        } finally {
            loading = false;
        }
    }

    function handleCancel() {
        form = { product_id: '' };
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


        <div class="flex flex-col gap-2">
            <label class="text-sm font-medium text-mono" for="product-select">
                Finished product <span class="text-destructive">*</span>
            </label>
            <Select2
                bind:this={productSelectComponent}
                id="product-select"
                placeholder="Search and select finished product..."
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
                            results: data.products
                                .filter((product) => product.type === 'finished_product')
                                .map((product) => ({
                                    id: product.id,
                                    text: product.name,
                                })),
                        };
                    },
                    cache: true,
                }}
            />
            {#if errors.product_id}
                <p class="text-sm text-destructive">{errors.product_id}</p>
            {/if}
        </div>

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-border">
            <button type="button" class="kt-btn kt-btn-secondary" on:click={handleCancel} disabled={loading}>
                Cancel
            </button>
            <button type="submit" class="kt-btn kt-btn-primary" disabled={loading || !form.product_id}>
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
