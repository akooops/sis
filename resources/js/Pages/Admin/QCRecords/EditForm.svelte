<script>
    import Flatpickr from '../../Shared/Utils/Forms/Flatpickr.svelte';
    import DocumentsInput from '../../Shared/Utils/Forms/DocumentsInput.svelte';
    import { createEventDispatcher } from 'svelte';

    const dispatch = createEventDispatcher();
    export let qcRecord = null;

    let form = { planned_qc_date: '', notes: '', documents: [] };
    let existingDocuments = [];
    let errors = {};
    let loading = false;

    $: if (qcRecord) {
        form = {
            planned_qc_date: qcRecord.planned_qc_date ?? '',
            notes: qcRecord.notes ?? '',
            documents: [],
        };
        existingDocuments = qcRecord.documents ? [...qcRecord.documents] : [];
    }

    $: canEdit = qcRecord?.can_be_updated !== false;

    function handleDocumentsChange(event) {
        form.documents = event.detail.files || [];
    }

    function handleDocumentDeleted(event) {
        existingDocuments = existingDocuments.filter((d) => d.id !== event.detail.document.id);
        dispatch('documentDeleted', event.detail);
    }

    async function handleSubmit() {
        loading = true;
        errors = {};
        try {
            const formData = prepareFormData(form, true);
            form.documents.forEach((file) => formData.append('documents[]', file));
            const response = await fetch(route('api.v1.admin.qc-records.update', { qcRecord: qcRecord.id }), {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: formData,
            });
            const data = await response.json();
            if (response.ok) {
                toast('QC record updated successfully', 'success');
                dispatch('updated');
            } else if (data.errors) {
                errors = data.errors;
            } else {
                errors = { general: data.message || 'An error occurred.' };
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
            <div class="kt-card-header border-b border-border"><span class="text-sm font-semibold text-mono">QC Details</span></div>
            <div class="kt-card-content p-4 space-y-5">
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-mono" for="planned-qc-date">Planned QC Date</label>
                    <Flatpickr id="planned-qc-date" bind:value={form.planned_qc_date} disabled={loading || !canEdit} />
                    {#if errors.planned_qc_date}<p class="text-sm text-destructive">{errors.planned_qc_date}</p>{/if}
                </div>
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-mono" for="qc-notes">Notes</label>
                    <textarea id="qc-notes" class="kt-textarea min-h-[90px]" bind:value={form.notes} disabled={loading || !canEdit}></textarea>
                </div>
                <DocumentsInput bind:files={form.documents} {existingDocuments} disabled={loading || !canEdit} allowDelete={canEdit} on:change={handleDocumentsChange} on:deleted={handleDocumentDeleted} />
            </div>
        </div>

        <div class="flex items-center justify-end gap-2.5">
            <button type="button" class="kt-btn kt-btn-secondary" on:click={() => dispatch('canceled')} disabled={loading}>Cancel</button>
            {#if canEdit}
                <button type="submit" class="kt-btn kt-btn-primary" disabled={loading}>Save Changes</button>
            {/if}
        </div>
    </form>
</div>
