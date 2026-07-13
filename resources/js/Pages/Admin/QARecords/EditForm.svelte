<script>
    import Flatpickr from '../../Shared/Utils/Forms/Flatpickr.svelte';
    import DocumentsInput from '../../Shared/Utils/Forms/DocumentsInput.svelte';
    import { createEventDispatcher } from 'svelte';

    const dispatch = createEventDispatcher();
    export let qaRecord = null;

    let form = { planned_qa_date: '', notes: '', documents: [] };
    let existingDocuments = [];
    let errors = {};
    let loading = false;

    $: if (qaRecord) {
        form = { planned_qa_date: qaRecord.planned_qa_date ?? '', notes: qaRecord.notes ?? '', documents: [] };
        existingDocuments = qaRecord.documents ? [...qaRecord.documents] : [];
    }
    $: canEdit = qaRecord?.can_be_updated !== false;

    async function handleSubmit() {
        loading = true;
        errors = {};
        try {
            const formData = prepareFormData(form, true);
            form.documents.forEach((file) => formData.append('documents[]', file));
            const response = await fetch(route('api.v1.admin.qa-records.update', { qaRecord: qaRecord.id }), {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
                body: formData,
            });
            const data = await response.json();
            if (response.ok) { toast('QA record updated', 'success'); dispatch('updated'); }
            else if (data.errors) errors = data.errors;
            else errors = { general: data.message };
        } catch { errors = { general: 'Network error.' }; }
        finally { loading = false; }
    }
</script>

<div class="space-y-6">
    <form on:submit|preventDefault={handleSubmit} class="space-y-5">
        {#if errors.general}<p class="text-sm text-destructive">{errors.general}</p>{/if}
        {#if errors.q_a_record}<p class="text-sm text-destructive">{errors.q_a_record}</p>{/if}
        <div class="kt-card border border-border">
            <div class="kt-card-header border-b border-border"><span class="text-sm font-semibold">QA Details</span></div>
            <div class="kt-card-content p-4 space-y-5">
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium" for="planned-qa-date">Planned QA Date</label>
                    <Flatpickr id="planned-qa-date" bind:value={form.planned_qa_date} disabled={loading || !canEdit} />
                </div>
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium" for="qa-notes">Notes</label>
                    <textarea id="qa-notes" class="kt-textarea min-h-[90px]" bind:value={form.notes} disabled={loading || !canEdit}></textarea>
                </div>
                <DocumentsInput bind:files={form.documents} {existingDocuments} disabled={loading || !canEdit} allowDelete={canEdit} on:change={(e) => form.documents = e.detail.files || []} on:deleted={(e) => { existingDocuments = existingDocuments.filter((d) => d.id !== e.detail.document.id); dispatch('documentDeleted', e.detail); }} />
            </div>
        </div>
        <div class="flex justify-end gap-2.5">
            <button type="button" class="kt-btn kt-btn-secondary" on:click={() => dispatch('canceled')}>Cancel</button>
            {#if canEdit}<button type="submit" class="kt-btn kt-btn-primary" disabled={loading}>Save</button>{/if}
        </div>
    </form>
</div>
