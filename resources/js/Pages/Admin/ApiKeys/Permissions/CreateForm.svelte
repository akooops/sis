<script>
    import Select2 from '../../../Shared/Utils/Forms/Select2.svelte';
    import { createEventDispatcher } from 'svelte';

    const dispatch = createEventDispatcher();

    // Props
    export let apiKey = null;

    let form = {
        permissions: [],
    }

    // Form data
    let selectedPermissions = [];
    let errors = {};

    // Loading state
    let loading = false;

    // Select2 component reference
    let permissionSelectComponent;

    // Handle permission selection from Select2
    function handlePermissionSelect(event) {
        const permissionId = event.detail.value;
        if (permissionId && !selectedPermissions.find(p => p.id === permissionId)) {
            // Find the permission details from the select2 data
            const permission = event.detail.data;
            if (permission) {
                selectedPermissions = [...selectedPermissions, {
                    id: permission.id,
                    name: permission.text,
                    is_web: permission.is_web,
                    is_api: permission.is_api
                }];
            }
        }
        
        // Clear the select2 after selection
        if (permissionSelectComponent) {
            permissionSelectComponent.setValue('');
        }
    }

    // Remove permission from selected list
    function removeSelectedPermission(permissionId) {
        selectedPermissions = selectedPermissions.filter(p => p.id !== permissionId);
    }

    // Handle form submission
    async function handleSubmit() {
        if (selectedPermissions.length === 0) {
            errors = { permissions: 'Please select at least one permission.' };
            return;
        }

        loading = true;
        errors = {};
        
        try {
            form.permissions = selectedPermissions.map(p => p.id);
            let formData = prepareFormData(form);

            const response = await fetch(route('api.v1.admin.api-key-permissions.store', { apiKey: apiKey.id }), {
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
                selectedPermissions = [];
                errors = {};
                
                // Clear Select2
                if (permissionSelectComponent) {
                    permissionSelectComponent.setValue('');
                    permissionSelectComponent.setError(false);
                }
                
                // Notify parent to refresh data and hide form
                toast('Api key permissions assigned successfully', 'success');
                dispatch('created');
            } else {
                // Handle validation errors
                if (data.errors) {
                    errors = data.errors;
                    
                    // Apply error styling to Select2 component
                    if (errors.permissions && permissionSelectComponent) {
                        permissionSelectComponent.setError(true);
                    }
                } else {
                    // Handle other errors
                    console.error('Error assigning api key permissions:', data.message || 'Unknown error');
                    errors = { general: data.message || 'An error occurred while assigning api key permissions.' };
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
        selectedPermissions = [];
        errors = {};
        
        // Clear Select2
        if (permissionSelectComponent) {
            permissionSelectComponent.setValue('');
            permissionSelectComponent.setError(false);
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

        <!-- Permission Selection -->
        <div class="flex flex-col gap-2">
            <label class="text-sm font-medium text-mono" for="permission-select">
                Select Api Key Permissions <span class="text-destructive">*</span>
            </label>
            <Select2
                bind:this={permissionSelectComponent}
                id="permission-select"
                placeholder="Search and select permissions..."
                on:select={handlePermissionSelect}
                disabled={loading}
                ajax={{
                    url: route('api.v1.admin.permissions.index'),
                    dataType: 'json',
                    delay: 300,
                    data: function(params) {
                        return {
                            search: params.term,
                            per_page: 10,
                            is_web: 1,
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data.permissions.map(permission => ({
                                id: permission.id,
                                text: permission.name,
                                is_web: permission.is_web,
                                is_api: permission.is_api
                            }))
                        };
                    },
                    cache: true
                }}
            />
            {#if errors.permissions}
                <p class="text-sm text-destructive">{errors.permissions}</p>
            {/if}
        </div>

        <!-- Selected Permissions List -->
        {#if selectedPermissions.length > 0}
            <div class="flex flex-col gap-2">
                <label class="text-sm font-medium text-mono">
                    Selected Api Key Permissions ({selectedPermissions.length})
                </label>
                <div class="space-y-2 max-h-40 overflow-y-auto border border-border rounded-lg p-3">
                    {#each selectedPermissions as permission (permission.id)}
                        <div class="flex items-center justify-between p-3 bg-muted/30 border border-border rounded-lg">
                            <div class="flex items-center gap-3">
                                <div class="flex-1">
                                    <p class="text-sm font-medium">{permission.name}</p>
                                </div>
                            </div>
                            <button
                                type="button"
                                class="p-1 text-muted-foreground hover:text-destructive transition-colors cursor-pointer"
                                on:click={() => removeSelectedPermission(permission.id)}
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
                disabled={loading || selectedPermissions.length === 0}
            >
                {#if loading}
                    <i class="fa-solid fa-spinner fa-spin mr-2"></i>
                    Assigning...
                {:else}
                    <i class="fa-solid fa-plus mr-2"></i>
                    Assign Api Key Permissions ({selectedPermissions.length})
                {/if}
            </button>
        </div>
    </form>
</div>
