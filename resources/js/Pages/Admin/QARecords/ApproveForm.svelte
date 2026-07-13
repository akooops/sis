<script>
    import QuantityInput from '../../Shared/Utils/Forms/QuantityInput.svelte';
    import { createEventDispatcher } from 'svelte';

    const dispatch = createEventDispatcher();
    export let qaRecord = null;

    let form = { quantity_approved: '' };
    let errors = {};
    let loading = false;

    $: if (qaRecord && form.quantity_approved === '') {
        form.quantity_approved = qaRecord.quantity_received ?? '';
    }
    $: isUnitInteger = !!qaRecord?.product?.is_unit_integer;
    $: productUnit = qaRecord?.product?.unit_of_measurement?.trim() || '';

    async function handleSubmit() {
        loading = true;
        errors = {};
        try {
            const formData = prepareFormData(form, true);
            const response = await fetch(route('api.v1.admin.qa-records.approve', { qaRecord: qaRecord.id }), {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
                body: formData,
            });
            const data = await response.json();
            if (response.ok) { toast('QA record approved', 'success'); dispatch('approved'); }
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
            <div class="kt-card-header border-b border-border"><span class="text-sm font-semibold">Approve QA</span></div>
            <div class="kt-card-content p-4">
                <p class="text-xs text-muted-foreground mb-4">Enter approved quantity (max: received quantity). Status becomes approved, partially approved, or rejected.</p>
                <QuantityInput label="Quantity Approved" bind:value={form.quantity_approved} is_integer={isUnitInteger} unit={productUnit} disabled={loading} error={errors.quantity_approved} required={true} />
            </div>
        </div>
        <div class="flex justify-end gap-2.5">
            <button type="button" class="kt-btn kt-btn-secondary" on:click={() => dispatch('canceled')}>Cancel</button>
            <button type="submit" class="kt-btn kt-btn-primary" disabled={loading}>Approve</button>
        </div>
    </form>
</div>
