<script>
    import Select2 from '../../Shared/Utils/Forms/Select2.svelte';
    import QuantityInput from '../../Shared/Utils/Forms/QuantityInput.svelte';
    import DocumentsInput from '../../Shared/Utils/Forms/DocumentsInput.svelte';
    import { createEventDispatcher } from 'svelte';

    const dispatch = createEventDispatcher();

    export let purchaseOrderId = null;

    const emptyForm = () => ({
        purchase_order_id: purchaseOrderId || '',
        product_id: '',
        quantity: '',
        notes: '',
        documents: [],
    });

    let form = emptyForm();
    let selectedProduct = null;
    let errors = {};
    let loading = false;
    let purchaseOrderSelectComponent;
    let productSelectComponent;

    $: if (purchaseOrderId) {
        form.purchase_order_id = purchaseOrderId;
    }

    $: isUnitInteger = !!(selectedProduct?.is_unit_integer);
    $: productUnit = selectedProduct?.unit_of_measurement?.trim() || '';

    function handlePurchaseOrderChange(event) {
        form.purchase_order_id = event.detail.value || '';

        if (purchaseOrderSelectComponent) {
            purchaseOrderSelectComponent.setError(false);
        }
    }

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
        if (!form.purchase_order_id) {
            errors = { purchase_order_id: 'Please select a purchase order.' };
            if (purchaseOrderSelectComponent) {
                purchaseOrderSelectComponent.setError(true);
            }
            return;
        }

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
            appendDocuments(formData);

            const response = await fetch(route('api.v1.admin.purchase-order-lines.store'), {
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
                if (purchaseOrderId) {
                    form.purchase_order_id = purchaseOrderId;
                }
                selectedProduct = null;
                errors = {};

                if (purchaseOrderSelectComponent) {
                    purchaseOrderSelectComponent.setValue(purchaseOrderId || '');
                    purchaseOrderSelectComponent.setError(false);
                }

                if (productSelectComponent) {
                    productSelectComponent.setValue('');
                    productSelectComponent.setError(false);
                }

                toast('Purchase order line added successfully', 'success');
                dispatch('created');
            } else if (data.errors) {
                errors = data.errors;
            } else {
                errors = { general: data.message || 'An error occurred while adding the line.' };
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

        {#if errors.purchase_order}
            <div class="p-3 bg-destructive/10 border border-destructive/20 rounded-lg">
                <p class="text-sm text-destructive">{errors.purchase_order}</p>
            </div>
        {/if}

        {#if !purchaseOrderId}
            <div class="kt-card border border-border">
                <div class="kt-card-header border-b border-border">
                    <span class="text-sm font-semibold text-mono">Purchase Order</span>
                </div>
                <div class="kt-card-content p-4 space-y-2">
                    <Select2
                        bind:this={purchaseOrderSelectComponent}
                        placeholder="Search and select purchase order..."
                        value={form.purchase_order_id}
                        on:change={handlePurchaseOrderChange}
                        disabled={loading}
                        ajax={{
                            url: route('api.v1.admin.purchase-orders.index'),
                            dataType: 'json',
                            delay: 300,
                            data: function(params) {
                                return { search: params.term, per_page: 10, status: 'pending' };
                            },
                            processResults: function(data) {
                                return {
                                    results: data.purchase_orders.map(purchaseOrder => ({
                                        id: purchaseOrder.id,
                                        text: purchaseOrder.name,
                                    }))
                                };
                            },
                            cache: true
                        }}
                    />
                    {#if errors.purchase_order_id}
                        <p class="text-sm text-destructive">{errors.purchase_order_id}</p>
                    {/if}
                </div>
            </div>
        {/if}

        <div class="kt-card border border-border">
            <div class="kt-card-header border-b border-border">
                <span class="text-sm font-semibold text-mono">Product</span>
            </div>
            <div class="kt-card-content p-4 space-y-2">
                <Select2
                    bind:this={productSelectComponent}
                    placeholder="Search and select product..."
                    value={form.product_id}
                    on:change={handleProductChange}
                    disabled={loading}
                    ajax={{
                        url: route('api.v1.admin.products.index'),
                        dataType: 'json',
                        delay: 300,
                        data: function(params) {
                            return { search: params.term, per_page: 10 };
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

        <div class="kt-card border border-border">
            <div class="kt-card-header border-b border-border">
                <span class="text-sm font-semibold text-mono">Line Details</span>
            </div>
            <div class="kt-card-content p-4 space-y-5">
                <QuantityInput
                    label="Quantity"
                    placeholder="Enter quantity..."
                    bind:value={form.quantity}
                    is_integer={isUnitInteger}
                    unit={productUnit}
                    disabled={loading || !form.product_id}
                    error={errors.quantity}
                    required={true}
                />

                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-mono" for="line-notes">Notes</label>
                    <textarea
                        id="line-notes"
                        class="kt-textarea min-h-[90px]"
                        placeholder="Enter line notes..."
                        bind:value={form.notes}
                        disabled={loading}
                    ></textarea>
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
                Add Line
            </button>
        </div>
    </form>
</div>
