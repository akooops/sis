<script>
    import QuantityInput from '../../Shared/Utils/Forms/QuantityInput.svelte';
    import { createEventDispatcher } from 'svelte';

    const dispatch = createEventDispatcher();
    export let qpRecord = null;

    let form = { quantity_released: '' };
    let errors = {};
    let loading = false;

    $: if (qpRecord && form.quantity_released === '') {
        form.quantity_released = qpRecord.quantity_approved ?? '';
    }
    $: isUnitInteger = !!qpRecord?.product?.is_unit_integer;
    $: productUnit = qpRecord?.product?.unit_of_measurement?.trim() || '';

    async function handleSubmit() {
        loading = true;
        errors = {};
        try {
            const formData = prepareFormData(form, true);
            const response = await fetch(route('api.v1.admin.qp-records.release', { qpRecord: qpRecord.id }), {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
                body: formData,
            });
            const data = await response.json();
            if (response.ok) { toast('QP record released to inventory', 'success'); dispatch('released'); }
            else if (data.errors) errors = data.errors;
            else errors = { general: data.message };
        } catch { errors = { general: 'Network error.' }; }
        finally { loading = false; }
    }
</script>

<div class="space-y-6">
    <form on:submit|preventDefault={handleSubmit} class="space-y-5">
        {#if errors.general}<p class="text-sm text-destructive">{errors.general}</p>{/if}
        {#if errors.q_p_record}<p class="text-sm text-destructive">{errors.q_p_record}</p>{/if}
        <div class="kt-card border border-border">
            <div class="kt-card-header border-b border-border"><span class="text-sm font-semibold">Release to Inventory</span></div>
            <div class="kt-card-content p-4">
                <p class="text-xs text-muted-foreground mb-4">Release quantity must be greater than 0 and not exceed approved quantity. Creates an incoming inventory operation.</p>
                {#if qpRecord?.quantity_approved}
                    <p class="text-sm mb-4">Approved: <strong>{qpRecord.quantity_approved}</strong></p>
                {/if}
                <QuantityInput label="Quantity Released" bind:value={form.quantity_released} is_integer={isUnitInteger} unit={productUnit} disabled={loading} error={errors.quantity_released} required={true} />
            </div>
        </div>
        <div class="flex justify-end gap-2.5">
            <button type="button" class="kt-btn kt-btn-secondary" on:click={() => dispatch('canceled')}>Cancel</button>
            <button type="submit" class="kt-btn kt-btn-primary" disabled={loading}>Release</button>
        </div>
    </form>
</div>
