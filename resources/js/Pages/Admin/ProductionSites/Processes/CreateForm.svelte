<script>
    import { createEventDispatcher } from 'svelte';

    const dispatch = createEventDispatcher();

    export let productionSite = null;

    const emptyForm = () => ({
        name: '',
        description: '',
        order: 0,
    });

    let form = emptyForm();
    let errors = {};
    let loading = false;

    async function handleSubmit() {
        loading = true;
        errors = {};

        try {
            const formData = prepareFormData(form);

            const response = await fetch(route('api.v1.admin.production-processes.store', { productionSite: productionSite.id }), {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: formData,
            });

            const data = await response.json();

            if (response.ok) {
                form = emptyForm();
                errors = {};

                toast('Production process added successfully', 'success');
                dispatch('created');
            } else if (data.errors) {
                errors = data.errors;
            } else {
                errors = { general: data.message || 'An error occurred while adding the production process.' };
            }
        } catch (error) {
            console.error('Network error:', error);
            errors = { general: 'Network error occurred. Please try again.' };
        } finally {
            loading = false;
        }
    }

    function handleCancel() {
        form = emptyForm();
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

        <div class="flex flex-col gap-2">
            <label class="text-sm font-medium text-mono" for="name">
                Name <span class="text-destructive">*</span>
            </label>
            <input
                id="name"
                type="text"
                class="kt-input {errors.name ? 'kt-input-error' : ''}"
                placeholder="Enter process name"
                bind:value={form.name}
                disabled={loading}
            />
            {#if errors.name}
                <p class="text-sm text-destructive">{errors.name}</p>
            {/if}
        </div>

        <div class="flex flex-col gap-2">
            <label class="text-sm font-medium text-mono" for="description">Description</label>
            <textarea
                id="description"
                class="kt-textarea min-h-[100px] {errors.description ? 'kt-input-error' : ''}"
                placeholder="Optional description"
                bind:value={form.description}
                disabled={loading}
            ></textarea>
            {#if errors.description}
                <p class="text-sm text-destructive">{errors.description}</p>
            {/if}
        </div>

        <div class="flex flex-col gap-2">
            <label class="text-sm font-medium text-mono" for="order">Order</label>
            <input
                id="order"
                type="number"
                min="0"
                class="kt-input {errors.order ? 'kt-input-error' : ''}"
                bind:value={form.order}
                disabled={loading}
            />
            {#if errors.order}
                <p class="text-sm text-destructive">{errors.order}</p>
            {/if}
        </div>

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-border">
            <button type="button" class="kt-btn kt-btn-secondary" on:click={handleCancel} disabled={loading}>
                Cancel
            </button>
            <button type="submit" class="kt-btn kt-btn-primary" disabled={loading}>
                {#if loading}
                    <i class="fa-solid fa-spinner fa-spin mr-2"></i>
                    Adding...
                {:else}
                    <i class="fa-solid fa-plus mr-2"></i>
                    Add Process
                {/if}
            </button>
        </div>
    </form>
</div>
