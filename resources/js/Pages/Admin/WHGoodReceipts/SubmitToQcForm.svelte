<script>
    import Flatpickr from '../../Shared/Utils/Forms/Flatpickr.svelte';
    import DocumentsInput from '../../Shared/Utils/Forms/DocumentsInput.svelte';
    import { suggestQcDates } from '../Warehouse/dateSuggestions.js';
    import { createEventDispatcher } from 'svelte';

    const dispatch = createEventDispatcher();

    export let whGoodReceipt = null;

    let form = { planned_qc_date: '', notes: '', documents: [] };
    let errors = {};
    let loading = false;

    $: if (whGoodReceipt?.product) {
        form = {
            ...form,
            ...suggestQcDates(whGoodReceipt.product.qc_inspection_time),
        };
    }

    function handleDocumentsChange(event) {
        form.documents = event.detail.files || [];
    }

    async function handleSubmit() {
        loading = true;
        errors = {};

        try {
            const formData = prepareFormData(form, true);
            form.documents.forEach((file) => formData.append('documents[]', file));

            const response = await fetch(route('api.v1.admin.wh-good-receipts.submit-to-qc', { whGoodReceipt: whGoodReceipt.id }), {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: formData,
            });

            const data = await response.json();

            if (response.ok) {
                toast('Submitted to QC successfully', 'success');
                dispatch('submitted');
            } else if (data.errors) {
                errors = data.errors;
            } else {
                errors = { general: data.message || 'Failed to submit to QC.' };
            }
        } catch (error) {
            errors = { general: 'Network error occurred.' };
        } finally {
            loading = false;
        }
    }

    function handleCancel() {
        dispatch('canceled');
    }
</script>

<div class="space-y-6">
    <form on:submit|preventDefault={handleSubmit} class="space-y-5">
        {#if errors.general}<p class="text-sm text-destructive">{errors.general}</p>{/if}
        {#if errors.w_h_good_receipt}<p class="text-sm text-destructive">{errors.w_h_good_receipt}</p>{/if}

        <div class="kt-card border border-border">
            <div class="kt-card-header border-b border-border"><span class="text-sm font-semibold text-mono">Submit to QC</span></div>
            <div class="kt-card-content p-4 space-y-5">
                <p class="text-xs text-muted-foreground">Planned QC date is suggested from the product QC inspection time.</p>
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-mono" for="planned-qc-date">Planned QC Date</label>
                    <Flatpickr id="planned-qc-date" placeholder="Select planned QC date..." bind:value={form.planned_qc_date} disabled={loading} />
                </div>
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-mono" for="qc-notes">Notes</label>
                    <textarea id="qc-notes" class="kt-textarea min-h-[90px]" bind:value={form.notes} disabled={loading}></textarea>
                </div>
                <DocumentsInput bind:files={form.documents} disabled={loading} label="Documents" on:change={handleDocumentsChange} />
            </div>
        </div>

        <div class="flex items-center justify-end gap-2.5">
            <button type="button" class="kt-btn kt-btn-secondary" on:click={handleCancel} disabled={loading}>Cancel</button>
            <button type="submit" class="kt-btn kt-btn-primary" disabled={loading}>Submit to QC</button>
        </div>
    </form>
</div>
