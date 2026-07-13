<script>
    import QuantityInput from '../../Shared/Utils/Forms/QuantityInput.svelte';
    import { createEventDispatcher } from 'svelte';

    const dispatch = createEventDispatcher();

    export let importQuota = null;

    let form = {
        consumption_quota: '',
        max_quota: '',
    };

    let errors = {};
    let loading = false;

    $: if (importQuota) {
        form = {
            consumption_quota: importQuota.consumption_quota ?? '0',
            max_quota: importQuota.max_quota ?? '',
        };
    }

    $: isUnitInteger = !!(importQuota?.product?.is_unit_integer);
    $: productUnit = importQuota?.product?.unit_of_measurement?.trim() || '';

    async function handleSubmit() {
        loading = true;
        errors = {};

        try {
            const formData = prepareFormData(form, true);

            const response = await fetch(route('api.v1.admin.import-quotas.update', { importQuota: importQuota.id }), {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: formData,
            });

            const data = await response.json();

            if (response.ok) {
                toast('Import quota updated successfully', 'success');
                dispatch('updated');
            } else if (data.errors) {
                errors = data.errors;
            } else {
                errors = { general: data.message || 'An error occurred while updating the import quota.' };
            }
        } catch (error) {
            console.error('Network error:', error);
            errors = { general: 'Network error occurred. Please try again.' };
        } finally {
            loading = false;
        }
    }

    function handleCancel() {
        if (importQuota) {
            form = {
                consumption_quota: importQuota.consumption_quota ?? '0',
                max_quota: importQuota.max_quota ?? '',
            };
        }
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

        <div class="kt-card border border-border">
            <div class="kt-card-header border-b border-border">
                <span class="text-sm font-semibold text-mono">Product</span>
            </div>
            <div class="kt-card-content p-4">
                {#if importQuota?.product}
                    <div class="flex items-center gap-3">
                        <img
                            src={importQuota.product.image_url}
                            alt={importQuota.product.name}
                            class="w-[30px] h-[30px] rounded-lg object-cover"
                        />
                        <div class="flex flex-col gap-1">
                            <span class="text-sm font-medium text-secondary-foreground">
                                {importQuota.product.name}
                            </span>
                            {#if importQuota.product.code}
                                <span class="text-xs text-muted-foreground">{importQuota.product.code}</span>
                            {/if}
                        </div>
                    </div>
                {:else}
                    <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">N/A</span>
                {/if}
            </div>
        </div>

        <div class="kt-card border border-border">
            <div class="kt-card-header border-b border-border">
                <span class="text-sm font-semibold text-mono">Quotas</span>
            </div>
            <div class="kt-card-content p-4 space-y-5">
                <QuantityInput
                    label="Allowed quota"
                    bind:value={form.max_quota}
                    is_integer={isUnitInteger}
                    unit={productUnit}
                    disabled={loading}
                    error={errors.max_quota}
                    required={true}
                />

                <QuantityInput
                    label="Consumed quota"
                    bind:value={form.consumption_quota}
                    is_integer={isUnitInteger}
                    unit={productUnit}
                    disabled={loading}
                    error={errors.consumption_quota}
                />
            </div>
        </div>

        <div class="flex items-center justify-end gap-2.5">
            <button type="button" class="kt-btn kt-btn-secondary" on:click={handleCancel} disabled={loading}>
                Cancel
            </button>
            <button type="submit" class="kt-btn kt-btn-primary" disabled={loading}>
                {#if loading}
                    <i class="fa-solid fa-spinner fa-spin mr-1"></i>
                {/if}
                Save Changes
            </button>
        </div>
    </form>
</div>
