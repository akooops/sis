<script>
    import Select2 from '../../Shared/Utils/Forms/Select2.svelte';
    import CodeInput from '../../Shared/Utils/Forms/CodeInput.svelte';
    import DocumentsInput from '../../Shared/Utils/Forms/DocumentsInput.svelte';
    import { createEventDispatcher } from 'svelte';

    const dispatch = createEventDispatcher();

    const emptyForm = () => ({
        name: '',
        code: '',
        description: '',
        supplier_id: '',
        production_site_id: '',
        documents: [],
    });

    let form = emptyForm();
    let errors = {};
    let loading = false;
    let supplierSelectComponent;
    let productionSiteSelectComponent;

    function handleSupplierChange(event) {
        form.supplier_id = event.detail.value || '';
        if (supplierSelectComponent) {
            supplierSelectComponent.setError(false);
        }
    }

    function handleProductionSiteChange(event) {
        form.production_site_id = event.detail.value || '';
        if (productionSiteSelectComponent) {
            productionSiteSelectComponent.setError(false);
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
        loading = true;
        errors = {};

        try {
            const formData = prepareFormData(form);
            appendDocuments(formData);

            const response = await fetch(route('api.v1.admin.purchase-orders.store'), {
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

                if (supplierSelectComponent) {
                    supplierSelectComponent.setValue('');
                    supplierSelectComponent.setError(false);
                }
                if (productionSiteSelectComponent) {
                    productionSiteSelectComponent.setValue('');
                    productionSiteSelectComponent.setError(false);
                }

                toast('Purchase order created successfully', 'success');
                dispatch('created');
            } else if (data.errors) {
                errors = data.errors;
            } else {
                errors = { general: data.message || 'An error occurred while creating the purchase order.' };
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
                <span class="text-sm font-semibold text-mono">Purchase Order</span>
            </div>
            <div class="kt-card-content p-4 space-y-5">
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-mono" for="purchase-order-name">
                        Name <span class="text-destructive">*</span>
                    </label>
                    <input
                        id="purchase-order-name"
                        type="text"
                        placeholder="Enter purchase order name..."
                        class="kt-input {errors.name ? 'kt-input-error' : ''}"
                        bind:value={form.name}
                        disabled={loading}
                    />
                    {#if errors.name}
                        <p class="text-sm text-destructive">{errors.name}</p>
                    {/if}
                </div>

                <div class="flex flex-col gap-2">
                    <CodeInput
                        label="Code"
                        required={true}
                        placeholder="Enter purchase order code..."
                        generateFrom={[form.name]}
                        bind:value={form.code}
                        disabled={loading}
                        error={errors.code}
                    />
                </div>

                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-mono" for="purchase-order-description">Description</label>
                    <textarea
                        id="purchase-order-description"
                        placeholder="Enter purchase order description..."
                        class="kt-textarea min-h-[90px] {errors.description ? 'kt-input-error' : ''}"
                        bind:value={form.description}
                        disabled={loading}
                    ></textarea>
                    {#if errors.description}
                        <p class="text-sm text-destructive">{errors.description}</p>
                    {/if}
                </div>
            </div>
        </div>

        <div class="kt-card border border-border">
            <div class="kt-card-header border-b border-border">
                <span class="text-sm font-semibold text-mono">Supplier & Site</span>
            </div>
            <div class="kt-card-content p-4 space-y-5">
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-mono" for="purchase-order-supplier">
                        Supplier <span class="text-destructive">*</span>
                    </label>
                    <Select2
                        bind:this={supplierSelectComponent}
                        id="purchase-order-supplier"
                        placeholder="Search and select supplier..."
                        value={form.supplier_id}
                        on:change={handleSupplierChange}
                        disabled={loading}
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
                    <label class="text-sm font-medium text-mono" for="purchase-order-site">
                        Production Site <span class="text-destructive">*</span>
                    </label>
                    <Select2
                        bind:this={productionSiteSelectComponent}
                        id="purchase-order-site"
                        placeholder="Search and select production site..."
                        value={form.production_site_id}
                        on:change={handleProductionSiteChange}
                        disabled={loading}
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
                    disabled={loading}
                    error={errors.documents || errors['documents.0']}
                    on:change={handleDocumentsChange}
                />
            </div>
        </div>

        <div class="flex items-center justify-end gap-2.5">
            <button type="button" class="kt-btn kt-btn-secondary" on:click={handleCancel} disabled={loading}>
                Cancel
            </button>
            <button type="submit" class="kt-btn kt-btn-primary" disabled={loading}>
                {#if loading}
                    <i class="fa-solid fa-spinner fa-spin mr-1"></i>
                {/if}
                Create Purchase Order
            </button>
        </div>
    </form>
</div>
