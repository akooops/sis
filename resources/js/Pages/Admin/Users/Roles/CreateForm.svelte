<script>
    import Select2 from '../../../Shared/Utils/Forms/Select2.svelte';
    import { createEventDispatcher } from 'svelte';

    const dispatch = createEventDispatcher();

    // Props
    export let user = null;

    let form = {
        roles: [],
    }

    // Form data
    let selectedRoles = [];
    let errors = {};

    // Loading state
    let loading = false;

    // Select2 component reference
    let roleSelectComponent;

    // Handle role selection from Select2
    function handleRoleSelect(event) {
        const roleId = event.detail.value;
        if (roleId && !selectedRoles.find(p => p.id === roleId)) {
            // Find the role details from the select2 data
            const role = event.detail.data;
            if (role) {
                selectedRoles = [...selectedRoles, {
                    id: role.id,
                    name: role.text,
                }];
            }
        }
        
        // Clear the select2 after selection
        if (roleSelectComponent) {
            roleSelectComponent.setValue('');
        }
    }

    // Remove role from selected list
    function removeSelectedRole(roleId) {
        selectedRoles = selectedRoles.filter(p => p.id !== roleId);
    }

    // Handle form submission
    async function handleSubmit() {
        if (selectedRoles.length === 0) {
            errors = { roles: 'Please select at least one role.' };
            return;
        }

        loading = true;
        errors = {};
        
        try {
            form.roles = selectedRoles.map(p => p.id);
            let formData = prepareFormData(form);

            const response = await fetch(route('api.v1.admin.user-roles.store', { user: user.id }), {
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
                selectedRoles = [];
                errors = {};
                
                // Clear Select2
                if (roleSelectComponent) {
                    roleSelectComponent.setValue('');
                    roleSelectComponent.setError(false);
                }
                
                // Notify parent to refresh data and hide form
                toast('Roles assigned successfully', 'success');
                dispatch('created');
            } else {
                // Handle validation errors
                if (data.errors) {
                    errors = data.errors;
                    
                    // Apply error styling to Select2 component
                    if (errors.roles && roleSelectComponent) {
                        roleSelectComponent.setError(true);
                    }
                } else {
                    // Handle other errors
                    console.error('Error assigning roles:', data.message || 'Unknown error');
                    errors = { general: data.message || 'An error occurred while assigning roles.' };
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
        selectedRoles = [];
        errors = {};
        
        // Clear Select2
        if (roleSelectComponent) {
            roleSelectComponent.setValue('');
            roleSelectComponent.setError(false);
        }
        
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

        <!-- Role Selection -->
        <div class="flex flex-col gap-2">
            <label class="text-sm font-medium text-mono" for="role-select">
                Select Roles <span class="text-destructive">*</span>
            </label>
            <Select2
                bind:this={roleSelectComponent}
                id="role-select"
                placeholder="Search and select roles..."
                on:select={handleRoleSelect}
                disabled={loading}
                ajax={{
                    url: route('api.v1.admin.roles.index'),
                    dataType: 'json',
                    delay: 300,
                    data: function(params) {
                        return {
                            search: params.term,
                            per_page: 10,
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data.roles.map(role => ({
                                id: role.id,
                                text: role.name,
                            }))
                        };
                    },
                    cache: true
                }}
            />
            {#if errors.roles}
                <p class="text-sm text-destructive">{errors.roles}</p>
            {/if}
        </div>

        <!-- Selected Roles List -->
        {#if selectedRoles.length > 0}
            <div class="flex flex-col gap-2">
                <label class="text-sm font-medium text-mono">
                    Selected Roles ({selectedRoles.length})
                </label>
                <div class="space-y-2 max-h-40 overflow-y-auto border border-border rounded-lg p-3">
                    {#each selectedRoles as role (role.id)}
                        <div class="flex items-center justify-between p-3 bg-muted/30 border border-border rounded-lg">
                            <div class="flex items-center gap-3">
                                <div class="flex-1">
                                    <p class="text-sm font-medium">{role.name}</p>
                                </div>
                            </div>
                            <button
                                type="button"
                                class="p-1 text-muted-foreground hover:text-destructive transition-colors cursor-pointer"
                                on:click={() => removeSelectedRole(role.id)}
                                disabled={loading}
                            >
                                <i class="fa-solid fa-times text-sm"></i>
                            </button>
                        </div>
                    {/each}
                </div>
            </div>
        {/if}

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
                disabled={loading || selectedRoles.length === 0}
            >
                {#if loading}
                    <i class="fa-solid fa-spinner fa-spin mr-2"></i>
                    Assigning...
                {:else}
                    <i class="fa-solid fa-plus mr-2"></i>
                    Assign Roles ({selectedRoles.length})
                {/if}
            </button>
        </div>
    </form>
</div>
