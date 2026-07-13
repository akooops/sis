<script>
    import Select2 from '../../Shared/Utils/Forms/Select2.svelte';
    import QuantityInput from '../../Shared/Utils/Forms/QuantityInput.svelte';
    import DocumentsInput from '../../Shared/Utils/Forms/DocumentsInput.svelte';
    import { createEventDispatcher } from 'svelte';

    const dispatch = createEventDispatcher();

    const emptyForm = () => ({
        product_id: '',
        batch: '',
        quantity_planned: '',
        planned_at: '',
        notes: '',
        documents: [],
    });

    let form = emptyForm();
    let selectedProduct = null;
    let errors = {};
    let loading = false;
    let productSelectComponent;

    $: isUnitInteger = !!(selectedProduct?.is_unit_integer);
    $: productUnit = selectedProduct?.unit_of_measurement?.trim() || '';

    const productsAjaxConfig = {
        url: route('api.v1.admin.products.index'),
        dataType: 'json',
        delay: 300,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
        },
        data: function (params) {
            return {
                search: params.term || '',
                per_page: 20,
                production_order_eligible: 1,
                page: params.page || 1,
            };
        },
        processResults: function (data) {
            return {
                results: (data.products || []).map((product) => ({
                    id: product.id,
                    text: `${product.name}${product.code ? ` (${product.code})` : ''}`,
                    is_unit_integer: product.is_unit_integer,
                    unit_of_measurement: product.unit_of_measurement,
                })),
                pagination: {
                    more: (data.pagination?.current_page || 1) < (data.pagination?.last_page || 1),
                },
            };
        },
        cache: true,
    };

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

    function handleDocumentsChange(event) {
        form.documents = event.detail.files || [];
    }

    function appendDocuments(formData) {
        form.documents.forEach((file) => {
            formData.append('documents[]', file);
        });
    }

    async function handleSubmit() {
        if (!form.product_id) {
            errors = { product_id: 'Please select a finished product.' };
            if (productSelectComponent) {
                productSelectComponent.setError(true);
            }
            return;
        }

        loading = true;
        errors = {};

        try {
            const formData = prepareFormData(form);
            appendDocuments(formData);

            const response = await fetch(route('api.v1.admin.production-orders.store'), {
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

                toast('Production order created successfully', 'success');
                dispatch('created');
            } else if (data.errors) {
                errors = data.errors;
            } else {
                errors = { general: data.message || 'An error occurred while creating the production order.' };
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
            <div class="kt-card-content p-4 space-y-2">
                <Select2
                    bind:this={productSelectComponent}
                    placeholder="Search and select a finished product..."
                    value={form.product_id}
                    on:change={handleProductChange}
                    disabled={loading}
                    ajax={productsAjaxConfig}
                />
                {#if errors.product_id}
                    <p class="text-sm text-destructive">{errors.product_id}</p>
                {/if}
            </div>
        </div>

        <div class="kt-card border border-border">
            <div class="kt-card-header border-b border-border">
                <span class="text-sm font-semibold text-mono">Production Order Details</span>
            </div>
            <div class="kt-card-content p-4 space-y-5">
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-mono" for="batch">Batch / Code <span class="text-destructive">*</span></label>
                    <input id="batch" type="text" class="kt-input {errors.batch ? 'kt-input-error' : ''}" placeholder="e.g. B26-0001" bind:value={form.batch} disabled={loading} />
                    {#if errors.batch}
                        <p class="text-sm text-destructive">{errors.batch}</p>
                    {/if}
                </div>

                <QuantityInput
                    label="Quantity planned"
                    placeholder="Enter planned quantity..."
                    bind:value={form.quantity_planned}
                    is_integer={isUnitInteger}
                    unit={productUnit}
                    disabled={loading || !form.product_id}
                    error={errors.quantity_planned}
                    required={true}
                />

                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-mono" for="planned-at">Planned at</label>
                    <input id="planned-at" type="datetime-local" class="kt-input {errors.planned_at ? 'kt-input-error' : ''}" bind:value={form.planned_at} disabled={loading} />
                    {#if errors.planned_at}
                        <p class="text-sm text-destructive">{errors.planned_at}</p>
                    {/if}
                </div>

                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-mono" for="production-order-notes">Notes</label>
                    <textarea id="production-order-notes" class="kt-textarea min-h-[90px]" placeholder="Enter notes..." bind:value={form.notes} disabled={loading}></textarea>
                    {#if errors.notes}
                        <p class="text-sm text-destructive">{errors.notes}</p>
                    {/if}
                </div>
            </div>
        </div>

        <div class="kt-card border border-border">
            <div class="kt-card-header border-b border-border">
                <span class="text-sm font-semibold text-mono">Documents</span>
            </div>
            <div class="kt-card-content p-4">
                <DocumentsInput
                    bind:files={form.documents}
                    disabled={loading}
                    label=""
                    helpText="PDF and CSV files only. You can add multiple files."
                    error={errors.documents || errors['documents.0']}
                    on:change={handleDocumentsChange}
                />
            </div>
        </div>

        <div class="flex items-center justify-end gap-2.5">
            <button type="button" class="kt-btn kt-btn-secondary" on:click={handleCancel} disabled={loading}>Cancel</button>
            <button type="submit" class="kt-btn kt-btn-primary" disabled={loading}>
                {#if loading}<i class="fa-solid fa-spinner fa-spin mr-1"></i>{/if}
                Create Production Order
            </button>
        </div>
    </form>
</div>
