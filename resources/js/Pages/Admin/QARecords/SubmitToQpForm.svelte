<script>
    import Flatpickr from '../../Shared/Utils/Forms/Flatpickr.svelte';
    import DocumentsInput from '../../Shared/Utils/Forms/DocumentsInput.svelte';
    import { suggestQpDates } from '../Warehouse/dateSuggestions.js';
    import { createEventDispatcher } from 'svelte';

    const dispatch = createEventDispatcher();
    export let qaRecord = null;

    let form = { planned_qp_date: '', notes: '', documents: [] };
    let errors = {};
    let loading = false;

    $: if (qaRecord?.product) {
        form = { ...form, ...suggestQpDates(qaRecord.product.qp_inspection_time) };
    }

    async function handleSubmit() {
        loading = true;
        errors = {};
        try {
            const formData = prepareFormData(form, true);
            form.documents.forEach((file) => formData.append('documents[]', file));
            const response = await fetch(route('api.v1.admin.qa-records.submit-to-qp', { qaRecord: qaRecord.id }), {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
                body: formData,
            });
            const data = await response.json();
            if (response.ok) { toast('Submitted to QP successfully', 'success'); dispatch('submitted'); }
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
            <div class="kt-card-header border-b border-border"><span class="text-sm font-semibold">Submit to QP</span></div>
            <div class="kt-card-content p-4 space-y-5">
                <p class="text-xs text-muted-foreground">Planned QP date is suggested from the product QP inspection time.</p>
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium" for="planned-qp-date">Planned QP Date</label>
                    <Flatpickr id="planned-qp-date" bind:value={form.planned_qp_date} disabled={loading} />
                </div>
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium" for="qp-notes">Notes</label>
                    <textarea id="qp-notes" class="kt-textarea min-h-[90px]" bind:value={form.notes} disabled={loading}></textarea>
                </div>
                <DocumentsInput bind:files={form.documents} disabled={loading} on:change={(e) => form.documents = e.detail.files || []} />
            </div>
        </div>
        <div class="flex justify-end gap-2.5">
            <button type="button" class="kt-btn kt-btn-secondary" on:click={() => dispatch('canceled')}>Cancel</button>
            <button type="submit" class="kt-btn kt-btn-primary" disabled={loading}>Submit to QP</button>
        </div>
    </form>
</div>
