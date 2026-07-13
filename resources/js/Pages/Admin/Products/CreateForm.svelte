<script>
    import { createEventDispatcher } from 'svelte';
    import ImageInput from '../../Shared/Utils/Forms/ImageInput.svelte';
    import CodeInput from '../../Shared/Utils/Forms/CodeInput.svelte';
    import QuantityInput from '../../Shared/Utils/Forms/QuantityInput.svelte';
    import Select2 from '../../Shared/Utils/Forms/Select2.svelte';

    const dispatch = createEventDispatcher();

    const emptyForm = () => ({
        name: '',
        code: '',
        description: '',
        type: '',
        unit_of_measurement: '',
        is_unit_integer: false,
        supply_lead_time: '',
        warehouse_goods_receipt_time: '',
        qa_inspection_time: '',
        qc_inspection_time: '',
        qp_inspection_time: '',
        batch_size: '',
        scrap_rate: '',
        has_shelf_life: false,
        shelf_life_time: '',
        max_stock: '',
        min_stock: '',
        current_stock: '',
        preferred_supplier_id: '',
        image: null,
    });

    let form = emptyForm();
    let errors = {};
    let loading = false;
    let supplierSelectComponent;

    $: if (!form.unit_of_measurement?.trim()) {
        form.is_unit_integer = false;
    }

    function handleImageChange(event) {
        form.image = event.detail.file;
    }

    function handleSupplierChange(event) {
        form.preferred_supplier_id = event.detail.value || '';
    }

    async function handleSubmit() {
        loading = true;
        errors = {};

        try {
            let formData = prepareFormData(form);

            const response = await fetch(route('api.v1.admin.products.store'), {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: formData
            });

            const data = await response.json();

            if (response.ok) {
                form = emptyForm();
                errors = {};

                if (supplierSelectComponent) {
                    supplierSelectComponent.setValue('');
                }

                toast('Product created successfully', 'success');
                dispatch('created');
            } else {
                if (data.errors) {
                    errors = data.errors;
                    if (errors.preferred_supplier_id && supplierSelectComponent) {
                        supplierSelectComponent.setError(true);
                    }
                } else {
                    console.error('Error creating product:', data.message || 'Unknown error');
                }
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
        if (supplierSelectComponent) {
            supplierSelectComponent.setValue('');
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
                <span class="text-sm font-semibold text-mono">Product</span>
            </div>
            <div class="kt-card-content p-4 space-y-5">
                <div class="flex flex-col gap-2">
                    <ImageInput
                        bind:value={form.image}
                        disabled={loading}
                        on:change={handleImageChange}
                    />
                    {#if errors.image}
                        <p class="text-sm text-destructive">{errors.image}</p>
                    {/if}
                </div>

                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-mono" for="name">
                        Name <span class="text-destructive">*</span>
                    </label>
                    <input
                        id="name"
                        type="text"
                        class="kt-input {errors.name ? 'kt-input-error' : ''}"
                        placeholder="Enter product name"
                        bind:value={form.name}
                        disabled={loading}
                    />
                    {#if errors.name}
                        <p class="text-sm text-destructive">{errors.name}</p>
                    {/if}
                </div>

                <CodeInput
                    bind:value={form.code}
                    label="Code"
                    placeholder="Enter code or leave blank to auto-generate from name"
                    generateFrom={[form.name]}
                    disabled={loading}
                    error={errors.code}
                />

                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-mono" for="description">Description</label>
                    <textarea
                        id="description"
                        class="kt-textarea {errors.description ? 'kt-input-error' : ''}"
                        placeholder="Enter description"
                        bind:value={form.description}
                        rows="3"
                        disabled={loading}
                    ></textarea>
                    {#if errors.description}
                        <p class="text-sm text-destructive">{errors.description}</p>
                    {/if}
                </div>

                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-mono" for="type">Type <span class="text-destructive">*</span></label>
                    <select id="type" bind:value={form.type} class="kt-select" disabled={loading}>
                        <option value="">Select type</option>
                        <option value="raw_material">Raw Material</option>
                        <option value="component">Component</option>
                        <option value="finished_product">Finished Product</option>
                    </select>
                    {#if errors.type}
                        <p class="text-sm text-destructive">{errors.type}</p>
                    {/if}
                </div>
            </div>
        </div>

        <div class="kt-card border border-border">
            <div class="kt-card-header border-b border-border">
                <span class="text-sm font-semibold text-mono">Unit</span>
            </div>
            <div class="kt-card-content p-4 space-y-5">
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-mono" for="unit_of_measurement">Unit of measurement</label>
                    <input
                        id="unit_of_measurement"
                        type="text"
                        class="kt-input {errors.unit_of_measurement ? 'kt-input-error' : ''}"
                        placeholder="e.g. kg, pcs, L"
                        bind:value={form.unit_of_measurement}
                        disabled={loading}
                    />
                    {#if errors.unit_of_measurement}
                        <p class="text-sm text-destructive">{errors.unit_of_measurement}</p>
                    {/if}
                </div>

                {#if form.unit_of_measurement?.trim()}
                    <div class="flex items-center gap-2">
                        <input
                            class="kt-switch"
                            type="checkbox"
                            id="is_unit_integer"
                            bind:checked={form.is_unit_integer}
                            disabled={loading}
                        />
                        <label class="kt-label text-sm" for="is_unit_integer">
                            Integer unit only
                        </label>
                    </div>
                {/if}
            </div>
        </div>

        <div class="kt-card border border-border">
            <div class="kt-card-header border-b border-border">
                <span class="text-sm font-semibold text-mono">Lead Times (days)</span>
            </div>
            <div class="kt-card-content p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                {#each [
                    { key: 'supply_lead_time', label: 'Supply lead time' },
                    { key: 'warehouse_goods_receipt_time', label: 'Warehouse goods receipt' },
                    { key: 'qa_inspection_time', label: 'QA inspection' },
                    { key: 'qc_inspection_time', label: 'QC inspection' },
                    { key: 'qp_inspection_time', label: 'QP inspection' },
                ] as field}
                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-medium text-mono" for={field.key}>{field.label}</label>
                        <input
                            id={field.key}
                            type="number"
                            min="0"
                            class="kt-input {errors[field.key] ? 'kt-input-error' : ''}"
                            bind:value={form[field.key]}
                            disabled={loading}
                        />
                        {#if errors[field.key]}
                            <p class="text-sm text-destructive">{errors[field.key]}</p>
                        {/if}
                    </div>
                {/each}
            </div>
        </div>

        <div class="kt-card border border-border">
            <div class="kt-card-header border-b border-border">
                <span class="text-sm font-semibold text-mono">Production</span>
            </div>
            <div class="kt-card-content p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                <QuantityInput
                    label="Batch size"
                    bind:value={form.batch_size}
                    is_integer={form.is_unit_integer}
                    unit={form.unit_of_measurement?.trim() || ''}
                    disabled={loading}
                    error={errors.batch_size}
                />
                <QuantityInput
                    label="Scrap rate (%)"
                    bind:value={form.scrap_rate}
                    is_integer={false}
                    disabled={loading}
                    error={errors.scrap_rate}
                />
            </div>
        </div>

        <div class="kt-card border border-border">
            <div class="kt-card-header border-b border-border">
                <span class="text-sm font-semibold text-mono">Shelf Life</span>
            </div>
            <div class="kt-card-content p-4 space-y-5">
                <div class="flex items-center gap-2">
                    <input
                        class="kt-switch"
                        type="checkbox"
                        id="has_shelf_life"
                        bind:checked={form.has_shelf_life}
                        disabled={loading}
                    />
                    <label class="kt-label text-sm" for="has_shelf_life">
                        Has shelf life
                    </label>
                </div>

                {#if form.has_shelf_life}
                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-medium text-mono" for="shelf_life_time">Shelf life time (days)</label>
                        <input
                            id="shelf_life_time"
                            type="number"
                            min="0"
                            class="kt-input {errors.shelf_life_time ? 'kt-input-error' : ''}"
                            bind:value={form.shelf_life_time}
                            disabled={loading}
                        />
                        {#if errors.shelf_life_time}
                            <p class="text-sm text-destructive">{errors.shelf_life_time}</p>
                        {/if}
                    </div>
                {/if}
            </div>
        </div>

        <div class="kt-card border border-border">
            <div class="kt-card-header border-b border-border">
                <span class="text-sm font-semibold text-mono">Stock</span>
            </div>
            <div class="kt-card-content p-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="kt-alert mb-2">
                    <div class="kt-alert-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-info" aria-hidden="true">
                            <circle cx="12" cy="12" r="10"></circle>
                            <path d="M12 16v-4"></path>
                            <path d="M12 8h.01"></path>
                        </svg>
                    </div>
                    <div class="kt-alert-title">
                        Changing the current stock will trigger a warehouse incoming operation.
                    </div>
                </div>

                <QuantityInput
                    label="Max stock"
                    bind:value={form.max_stock}
                    is_integer={form.is_unit_integer}
                    unit={form.unit_of_measurement?.trim() || ''}
                    disabled={loading}
                    error={errors.max_stock}
                />
                <QuantityInput
                    label="Min stock"
                    bind:value={form.min_stock}
                    is_integer={form.is_unit_integer}
                    unit={form.unit_of_measurement?.trim() || ''}
                    disabled={loading}
                    error={errors.min_stock}
                />
                <QuantityInput
                    label="Current stock"
                    bind:value={form.current_stock}
                    is_integer={form.is_unit_integer}
                    unit={form.unit_of_measurement?.trim() || ''}
                    disabled={loading}
                    error={errors.current_stock}
                />
            </div>
        </div>

        <div class="kt-card border border-border">
            <div class="kt-card-header border-b border-border">
                <span class="text-sm font-semibold text-mono">Preferred Supplier</span>
            </div>
            <div class="kt-card-content p-4">
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-mono" for="preferred-supplier-select">Supplier</label>
                    <Select2
                        bind:this={supplierSelectComponent}
                        id="preferred-supplier-select"
                        placeholder="Search and select supplier..."
                        value={form.preferred_supplier_id}
                        on:change={handleSupplierChange}
                        disabled={loading}
                        ajax={{
                            url: route('api.v1.admin.suppliers.index'),
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
                                    results: data.suppliers.map(supplier => ({
                                        id: supplier.id,
                                        text: supplier.name,
                                    }))
                                };
                            },
                            cache: true
                        }}
                    />
                    {#if errors.preferred_supplier_id}
                        <p class="text-sm text-destructive">{errors.preferred_supplier_id}</p>
                    {/if}
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-border">
            <button type="button" class="kt-btn kt-btn-secondary" on:click={handleCancel} disabled={loading}>
                Cancel
            </button>
            <button type="submit" class="kt-btn kt-btn-primary" disabled={loading}>
                {#if loading}
                    <i class="fa-solid fa-spinner fa-spin mr-2"></i>
                    Creating...
                {:else}
                    <i class="fa-solid fa-box mr-2"></i>
                    Create Product
                {/if}
            </button>
        </div>
    </form>
</div>
