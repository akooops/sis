<script>
    import QuantityInput from '../../Shared/Utils/Forms/QuantityInput.svelte';
    import DocumentsInput from '../../Shared/Utils/Forms/DocumentsInput.svelte';
    import { createEventDispatcher } from 'svelte';

    const dispatch = createEventDispatcher();

    export let whGoodReceipt = null;

    let form = {
        batch: '',
        quantity: '',
        expiry_date: '',
        notes: '',
        documents: [],
    };

    let existingDocuments = [];
    let errors = {};
    let loading = false;

    $: if (whGoodReceipt) {
        form = {
            batch: whGoodReceipt.batch ?? '',
            quantity: whGoodReceipt.quantity ?? '',
            expiry_date: whGoodReceipt.expiry_date ?? '',
            notes: whGoodReceipt.notes ?? '',
            documents: [],
        };
        existingDocuments = whGoodReceipt.documents ? [...whGoodReceipt.documents] : [];
    }

    $: isUnitInteger = !!(whGoodReceipt?.product?.is_unit_integer);
    $: productUnit = whGoodReceipt?.product?.unit_of_measurement?.trim() || '';
    $: canEdit = whGoodReceipt?.can_be_updated !== false;

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

            const response = await fetch(route('api.v1.admin.wh-good-receipts.update', { whGoodReceipt: whGoodReceipt.id }), {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: formData,
            });

            const data = await response.json();

            if (response.ok) {
                toast('Warehouse good receipt updated successfully', 'success');
                dispatch('updated');
            } else if (data.errors) {
                errors = data.errors;
            } else {
                errors = { general: data.message || 'An error occurred while updating the receipt.' };
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

        {#if errors.w_h_good_receipt}
            <div class="p-3 bg-destructive/10 border border-destructive/20 rounded-lg">
                <p class="text-sm text-destructive">{errors.w_h_good_receipt}</p>
            </div>
        {/if}

        <div class="kt-card border border-border">
            <div class="kt-card-header border-b border-border">
                <span class="text-sm font-semibold text-mono">Receipt</span>
            </div>
            <div class="kt-card-content p-4 space-y-3">
                {#if whGoodReceipt?.code}
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-muted-foreground">Code</span>
                        <span class="text-sm font-semibold">{whGoodReceipt.code}</span>
                    </div>
                {/if}
                {#if whGoodReceipt?.product}
                    <div class="flex items-center gap-3">
                        <img src={whGoodReceipt.product.image_url} alt={whGoodReceipt.product.name} class="w-[30px] h-[30px] rounded-lg object-cover" />
                        <div>
                            <p class="text-sm font-medium">{whGoodReceipt.product.name}</p>
                        </div>
                    </div>
                {/if}
            </div>
        </div>

        <div class="kt-card border border-border">
            <div class="kt-card-header border-b border-border">
                <span class="text-sm font-semibold text-mono">Receipt Details</span>
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
                    <label class="text-sm font-medium text-mono" for="edit-batch">Batch</label>
                    <input id="edit-batch" type="text" class="kt-input" placeholder="Enter batch..." bind:value={form.batch} disabled={loading || !canEdit} />
                    {#if errors.batch}
                        <p class="text-sm text-destructive">{errors.batch}</p>
                    {/if}
                </div>

                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-mono" for="edit-expiry">Expiry Date</label>
                    <input id="edit-expiry" type="date" class="kt-input" bind:value={form.expiry_date} disabled={loading || !canEdit} />
                    {#if errors.expiry_date}
                        <p class="text-sm text-destructive">{errors.expiry_date}</p>
                    {/if}
                </div>

                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-mono" for="edit-receipt-notes">Notes</label>
                    <textarea id="edit-receipt-notes" class="kt-textarea min-h-[90px]" placeholder="Enter notes..." bind:value={form.notes} disabled={loading || !canEdit}></textarea>
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
