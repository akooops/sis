<script>
    import { createEventDispatcher } from 'svelte';

    const dispatch = createEventDispatcher();

    // Form data
    let form = {
        name: '',
        is_default: false,
    };

    // Form errors
    let errors = {};

    // Loading state
    let loading = false;

    // Handle form submission
    async function handleSubmit() {
        loading = true;
        errors = {};
    
        let formData = prepareFormData(form);
        
        try {
            const response = await fetch(route('api.v1.admin.roles.store'), {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: formData
            });
            
            const data = await response.json();
            
            if (response.ok) {
                // Success - reset form
                form = {
                    name: '',
                    is_default: false,
                };
                errors = {};
                
                // Notify parent to refresh data and hide form
                toast('Role created successfully', 'success');
                dispatch('created');
            } else {
                // Handle validation errors
                if (data.errors) {
                    errors = data.errors;
                } else {
                    // Handle other errors
                    console.error('Error creating role:', data.message || 'Unknown error');
                }
            }
        } catch (error) {
            console.error('Network error:', error);
            errors = { general: 'Network error occurred. Please try again.' };
        } finally {
            loading = false;
        }
    }

    // Handle cancel
    function handleCancel() {
        // Reset form
        form = {
            name: '',
            is_default: false,
        };
        errors = {};
    
        // Notify parent to hide form
        dispatch('canceled');
    }
</script>

<div class="space-y-6">    
    <!-- Form Content -->
        <form on:submit|preventDefault={handleSubmit} class="space-y-5">
            <!-- General Error Message -->
            {#if errors.general}
                <div class="p-3 bg-destructive/10 border border-destructive/20 rounded-lg">
                    <p class="text-sm text-destructive">{errors.general}</p>
                </div>
            {/if}
            <!-- Role Name -->
            <div class="flex flex-col gap-2">
                <label class="text-sm font-medium text-mono" for="name">
                    Role Name <span class="text-destructive">*</span>
                </label>
                <input
                    id="name"
                    type="text"
                    class="kt-input {errors.name ? 'kt-input-error' : ''}"
                    placeholder="Enter role name"
                    bind:value={form.name}
                    disabled={loading}
                />
                {#if errors.name}
                    <p class="text-sm text-destructive">{errors.name}</p>
                {/if}
            </div>

            <!-- Default Role Switch -->
            <div class="flex flex-col gap-2 mt-4">
                <label class="text-sm font-medium text-mono">
                    Default Role <span class="text-destructive">*</span>
                </label>
                <div class="flex items-center gap-2">
                    <input 
                        class="kt-switch" 
                        type="checkbox" 
                        id="is_default" 
                        bind:checked={form.is_default}
                        disabled={loading}
                    />
                </div>
                {#if errors.is_default}
                    <p class="text-sm text-destructive">{errors.is_default}</p>
                {/if}
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-border">
                <button
                    type="button"
                    class="kt-btn kt-btn-secondary"
                    on:click={handleCancel}
                    disabled={loading}
                >
                    Cancel
                </button>
                <button
                    type="submit"
                    class="kt-btn kt-btn-primary"
                    disabled={loading}
                >
                    {#if loading}
                        <i class="fa-solid fa-spinner fa-spin mr-2"></i>
                        Creating...
                    {:else}
                        <i class="fa-solid fa-plus mr-2"></i>
                        Create Role
                    {/if}
                </button>
            </div>
        </form>
</div>
