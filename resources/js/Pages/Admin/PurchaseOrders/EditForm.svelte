<script>
    import Select2 from '../../Shared/Utils/Forms/Select2.svelte';
    import CodeInput from '../../Shared/Utils/Forms/CodeInput.svelte';
    import DocumentsInput from '../../Shared/Utils/Forms/DocumentsInput.svelte';
    import { createEventDispatcher } from 'svelte';

    const dispatch = createEventDispatcher();

    export let purchaseOrder = null;

    let form = {
        name: '',
        code: '',
        description: '',
        supplier_id: '',
        production_site_id: '',
        documents: [],
    };

    let errors = {};
    let loading = false;
    let existingDocuments = [];
    let supplierSelectComponent;
    let productionSiteSelectComponent;

    $: if (purchaseOrder) {
        form = {
            name: purchaseOrder.name || '',
            code: purchaseOrder.code || '',
            description: purchaseOrder.description || '',
            supplier_id: purchaseOrder.supplier?.id || '',
            production_site_id: purchaseOrder.production_site?.id || '',
            documents: [],
        };
        existingDocuments = purchaseOrder.documents ? [...purchaseOrder.documents] : [];
    }

    function handleSupplierChange(event) {
        form.supplier_id = event.detail.value || '';
    }

    function handleProductionSiteChange(event) {
        form.production_site_id = event.detail.value || '';
    }

    function handleDocumentsChange(event) {
        form.documents = event.detail.files || [];
    }

    function handleDocumentDeleted(event) {
        existingDocuments = existingDocuments.filter((document) => document.id !== event.detail.document.id);
        dispatch('documentDeleted', event.detail);
    }

    function appendDocuments(formData) {
        form.documents.forEach((file) => {
            formData.append('documents[]', file);
        });
    }

    async function handleSubmit() {
        loading = true;
        errors = {};

        try {
            const formData = prepareFormData(form, true);
            appendDocuments(formData);

            const response = await fetch(route('api.v1.admin.purchase-orders.update', { purchaseOrder: purchaseOrder.id }), {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: formData,
            });

            const data = await response.json();

            if (response.ok) {
                toast('Purchase order updated successfully', 'success');
                dispatch('updated');
            } else if (data.errors) {
                errors = data.errors;
            } else {
                errors = { general: data.message || 'An error occurred while updating the purchase order.' };
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

        {#if errors.purchase_order}
            <div class="p-3 bg-destructive/10 border border-destructive/20 rounded-lg">
                <p class="text-sm text-destructive">{errors.purchase_order}</p>
            </div>
        {/if}

        <div class="kt-card border border-border">
            <div class="kt-card-header border-b border-border">
                <span class="text-sm font-semibold text-mono">Purchase Order</span>
            </div>
            <div class="kt-card-content p-4 space-y-5">
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-mono" for="edit-purchase-order-name">
                        Name <span class="text-destructive">*</span>
                    </label>
                    <input
                        id="edit-purchase-order-name"
                        type="text"
                        placeholder="Enter purchase order name..."
                        class="kt-input {errors.name ? 'kt-input-error' : ''}"
                        bind:value={form.name}
                        disabled={loading || purchaseOrder?.status === 'cancelled'}
                    />
                    {#if errors.name}
                        <p class="text-sm text-destructive">{errors.name}</p>
                    {/if}
                </div>

                <CodeInput
                    label="Code"
                    required={true}
                    placeholder="Enter purchase order code..."
                    bind:value={form.code}
                    disabled={loading || purchaseOrder?.status === 'cancelled'}
                    error={errors.code}
                />

                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-mono" for="edit-purchase-order-description">Description</label>
                    <textarea
                        id="edit-purchase-order-description"
                        placeholder="Enter description"
                        class="kt-textarea min-h-[90px]"
                        bind:value={form.description}
                        disabled={loading || purchaseOrder?.status === 'cancelled'}
                    ></textarea>
                </div>
            </div>
        </div>

        <div class="kt-card border border-border">
            <div class="kt-card-header border-b border-border">
                <span class="text-sm font-semibold text-mono">Supplier & Site</span>
            </div>
            <div class="kt-card-content p-4 space-y-5">
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-mono">Supplier <span class="text-destructive">*</span></label>
                    <Select2
                        bind:this={supplierSelectComponent}
                        placeholder="Search and select supplier..."
                        value={form.supplier_id}
                        on:change={handleSupplierChange}
                        disabled={loading || purchaseOrder?.status === 'cancelled'}
                        ajax={{
                            url: route('api.v1.admin.suppliers.index'),
                            dataType: 'json',
                            delay: 300,
                            data: function(params) {
                                return { search: params.term, per_page: 10 };
                            },
                            processResults: function(data) {
                                return {
                                    results: data.suppliers.map(supplier => ({
                                        id: supplier.id,
                                        text: supplier.name,
                                    }))
                                };
                            },
                            cache: true
                        }}
                    />
                    {#if errors.supplier_id}
                        <p class="text-sm text-destructive">{errors.supplier_id}</p>
                    {/if}
                </div>

                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-mono">Production Site <span class="text-destructive">*</span></label>
                    <Select2
                        bind:this={productionSiteSelectComponent}
                        placeholder="Search and select production site..."
                        value={form.production_site_id}
                        on:change={handleProductionSiteChange}
                        disabled={loading || purchaseOrder?.status === 'cancelled'}
                        ajax={{
                            url: route('api.v1.admin.production-sites.index'),
                            dataType: 'json',
                            delay: 300,
                            data: function(params) {
                                return { search: params.term, per_page: 10 };
                            },
                            processResults: function(data) {
                                return {
                                    results: data.production_sites.map(site => ({
                                        id: site.id,
                                        text: site.name,
                                    }))
                                };
                            },
                            cache: true
                        }}
                    />
                    {#if errors.production_site_id}
                        <p class="text-sm text-destructive">{errors.production_site_id}</p>
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
                    existingDocuments={existingDocuments}
                    disabled={loading || purchaseOrder?.status === 'cancelled'}
                    allowDelete={purchaseOrder?.status !== 'cancelled'}
                    label=""
                    helpText={purchaseOrder?.status === 'cancelled' ? '' : 'PDF and CSV files only. You can add multiple files.'}
                    error={errors.documents || errors['documents.0']}
                    on:change={handleDocumentsChange}
                    on:deleted={handleDocumentDeleted}
                />
            </div>
        </div>

        <div class="flex items-center justify-end gap-2.5">
            <button type="button" class="kt-btn kt-btn-secondary" on:click={handleCancel} disabled={loading}>
                Cancel
            </button>
            {#if purchaseOrder?.status !== 'cancelled'}
                <button type="submit" class="kt-btn kt-btn-primary" disabled={loading}>
                    {#if loading}
                        <i class="fa-solid fa-spinner fa-spin mr-1"></i>
                    {/if}
                    Save Changes
                </button>
            {/if}
        </div>
    </form>
</div>
