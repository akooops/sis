<script>
    import QuantityInput from '../../Shared/Utils/Forms/QuantityInput.svelte';
    import DocumentsInput from '../../Shared/Utils/Forms/DocumentsInput.svelte';
    import { createEventDispatcher } from 'svelte';

    const dispatch = createEventDispatcher();

    export let productionOrder = null;

    let form = {
        batch: '',
        quantity_planned: '',
        planned_at: '',
        notes: '',
        documents: [],
    };

    let existingDocuments = [];
    let errors = {};
    let loading = false;

    $: if (productionOrder) {
        form = {
            batch: productionOrder.batch ?? '',
            quantity_planned: productionOrder.quantity_planned ?? '',
            planned_at: productionOrder.planned_at ?? '',
            notes: productionOrder.notes ?? '',
            documents: [],
        };
        existingDocuments = productionOrder.documents ? [...productionOrder.documents] : [];
    }

    $: isUnitInteger = !!(productionOrder?.product?.is_unit_integer);
    $: productUnit = productionOrder?.product?.unit_of_measurement?.trim() || '';
    $: canEdit = productionOrder?.can_be_updated !== false;

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

            const response = await fetch(route('api.v1.admin.production-orders.update', { productionOrder: productionOrder.id }), {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: formData,
            });

            const data = await response.json();

            if (response.ok) {
                toast('Production order updated successfully', 'success');
                dispatch('updated');
            } else if (data.errors) {
                errors = data.errors;
            } else {
                errors = { general: data.message || 'An error occurred while updating the production order.' };
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
                <span class="text-sm font-semibold text-mono">Details</span>
            </div>
            <div class="kt-card-content p-4 space-y-5">
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-mono" for="edit-batch">Batch / Code</label>
                    <input id="edit-batch" type="text" class="kt-input {errors.batch ? 'kt-input-error' : ''}" bind:value={form.batch} disabled={loading || !canEdit} />
                    {#if errors.batch}
                        <p class="text-sm text-destructive">{errors.batch}</p>
                    {/if}
                </div>

                <QuantityInput
                    label="Quantity planned"
                    bind:value={form.quantity_planned}
                    is_integer={isUnitInteger}
                    unit={productUnit}
                    disabled={loading || !canEdit}
                    error={errors.quantity_planned}
                    required={true}
                />

                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-mono" for="edit-planned-at">Planned at</label>
                    <input id="edit-planned-at" type="datetime-local" class="kt-input {errors.planned_at ? 'kt-input-error' : ''}" bind:value={form.planned_at} disabled={loading || !canEdit} />
                    {#if errors.planned_at}
                        <p class="text-sm text-destructive">{errors.planned_at}</p>
                    {/if}
                </div>

                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-mono" for="edit-notes">Notes</label>
                    <textarea id="edit-notes" class="kt-textarea min-h-[90px]" bind:value={form.notes} disabled={loading || !canEdit}></textarea>
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
                    {existingDocuments}
                    disabled={loading || !canEdit}
                    allowDelete={canEdit}
                    label=""
                    helpText={canEdit ? 'PDF and CSV files only. You can add multiple files.' : ''}
                    error={errors.documents || errors['documents.0']}
                    on:change={handleDocumentsChange}
                    on:deleted={handleDocumentDeleted}
                />
            </div>
        </div>

        <div class="flex items-center justify-end gap-2.5">
            <button type="button" class="kt-btn kt-btn-secondary" on:click={handleCancel} disabled={loading}>Cancel</button>
            {#if canEdit}
                <button type="submit" class="kt-btn kt-btn-primary" disabled={loading}>
                    {#if loading}<i class="fa-solid fa-spinner fa-spin mr-1"></i>{/if}
                    Save Changes
                </button>
            {/if}
        </div>
    </form>
</div>
