<script>
    import QuantityInput from '../../Shared/Utils/Forms/QuantityInput.svelte';
    import DocumentsInput from '../../Shared/Utils/Forms/DocumentsInput.svelte';
    import { createEventDispatcher } from 'svelte';

    const dispatch = createEventDispatcher();

    export let purchaseOrderLine = null;

    let form = {
        quantity: '',
        notes: '',
        documents: [],
    };

    let existingDocuments = [];
    let errors = {};
    let loading = false;

    $: if (purchaseOrderLine) {
        form = {
            quantity: purchaseOrderLine.quantity ?? '',
            notes: purchaseOrderLine.notes ?? '',
            documents: [],
        };
        existingDocuments = purchaseOrderLine.documents ? [...purchaseOrderLine.documents] : [];
    }

    $: isUnitInteger = !!(purchaseOrderLine?.product?.is_unit_integer);
    $: productUnit = purchaseOrderLine?.product?.unit_of_measurement?.trim() || '';
    $: canEdit = purchaseOrderLine?.can_be_updated !== false;

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

            const response = await fetch(route('api.v1.admin.purchase-order-lines.update', { purchaseOrderLine: purchaseOrderLine.id }), {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: formData,
            });

            const data = await response.json();

            if (response.ok) {
                toast('Purchase order line updated successfully', 'success');
                dispatch('updated');
            } else if (data.errors) {
                errors = data.errors;
            } else {
                errors = { general: data.message || 'An error occurred while updating the line.' };
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

        {#if errors.purchase_order_line}
            <div class="p-3 bg-destructive/10 border border-destructive/20 rounded-lg">
                <p class="text-sm text-destructive">{errors.purchase_order_line}</p>
            </div>
        {/if}

        <div class="kt-card border border-border">
            <div class="kt-card-header border-b border-border">
                <span class="text-sm font-semibold text-mono">Line</span>
            </div>
            <div class="kt-card-content p-4 space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-muted-foreground">Line number</span>
                    <span class="text-sm font-semibold">{purchaseOrderLine?.line}</span>
                </div>
                {#if purchaseOrderLine?.code}
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-muted-foreground">Code</span>
                        <span class="text-sm font-semibold">{purchaseOrderLine.code}</span>
                    </div>
                {/if}
                {#if purchaseOrderLine?.product}
                    <div class="flex items-center gap-3">
                        <img src={purchaseOrderLine.product.image_url} alt={purchaseOrderLine.product.name} class="w-[30px] h-[30px] rounded-lg object-cover" />
                        <div>
                            <p class="text-sm font-medium">{purchaseOrderLine.product.name}</p>
                            {#if purchaseOrderLine.product.code}
                                <p class="text-xs text-muted-foreground">{purchaseOrderLine.product.code}</p>
                            {/if}
                        </div>
                    </div>
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
                    disabled={loading || !canEdit}
                    error={errors.quantity}
                    required={true}
                />

                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-mono" for="edit-line-notes">Notes</label>
                    <textarea
                        id="edit-line-notes"
                        class="kt-textarea min-h-[90px]"
                        placeholder="Enter line notes..."
                        bind:value={form.notes}
                        disabled={loading || !canEdit}
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
                    existingDocuments={existingDocuments}
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
