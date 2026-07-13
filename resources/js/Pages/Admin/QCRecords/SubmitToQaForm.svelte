<script>
    import Flatpickr from '../../Shared/Utils/Forms/Flatpickr.svelte';
    import DocumentsInput from '../../Shared/Utils/Forms/DocumentsInput.svelte';
    import { suggestQaDates } from '../Warehouse/dateSuggestions.js';
    import { createEventDispatcher } from 'svelte';

    const dispatch = createEventDispatcher();
    export let qcRecord = null;

    let form = { planned_qa_date: '', notes: '', documents: [] };
    let errors = {};
    let loading = false;

    $: if (qcRecord?.product) {
        form = { ...form, ...suggestQaDates(qcRecord.product.qa_inspection_time) };
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
            const response = await fetch(route('api.v1.admin.qc-records.submit-to-qa', { qcRecord: qcRecord.id }), {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: formData,
            });
            const data = await response.json();
            if (response.ok) {
                toast('Submitted to QA successfully', 'success');
                dispatch('submitted');
            } else if (data.errors) {
                errors = data.errors;
            } else {
                errors = { general: data.message || 'Failed to submit to QA.' };
            }
        } catch {
            errors = { general: 'Network error occurred.' };
        } finally {
            loading = false;
        }
    }
</script>

<div class="space-y-6">
    <form on:submit|preventDefault={handleSubmit} class="space-y-5">
        {#if errors.general}<p class="text-sm text-destructive">{errors.general}</p>{/if}
        {#if errors.q_c_record}<p class="text-sm text-destructive">{errors.q_c_record}</p>{/if}

        <div class="kt-card border border-border">
            <div class="kt-card-header border-b border-border"><span class="text-sm font-semibold text-mono">Submit to QA</span></div>
            <div class="kt-card-content p-4 space-y-5">
                <p class="text-xs text-muted-foreground">Planned QA date is suggested from the product QA inspection time.</p>
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-mono" for="planned-qa-date">Planned QA Date</label>
                    <Flatpickr id="planned-qa-date" bind:value={form.planned_qa_date} disabled={loading} />
                </div>
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-mono" for="qa-notes">Notes</label>
                    <textarea id="qa-notes" class="kt-textarea min-h-[90px]" bind:value={form.notes} disabled={loading}></textarea>
                </div>
                <DocumentsInput bind:files={form.documents} disabled={loading} on:change={handleDocumentsChange} />
            </div>
        </div>

        <div class="flex items-center justify-end gap-2.5">
            <button type="button" class="kt-btn kt-btn-secondary" on:click={() => dispatch('canceled')} disabled={loading}>Cancel</button>
            <button type="submit" class="kt-btn kt-btn-primary" disabled={loading}>Submit to QA</button>
        </div>
    </form>
</div>
