<script>
    import { createEventDispatcher } from 'svelte';

    const dispatch = createEventDispatcher();

    // Form data
    let form = {
        name: ''
    };

    // Form errors
    let errors = {};

    // Loading state
    let loading = false;

    // Generated credentials
    let generatedCredentials = null;
    let showCredentials = false;

    // Copy to clipboard function
    async function copyToClipboard(text) {
        try {
            await navigator.clipboard.writeText(text);
            toast('Copied to clipboard!', 'success');
        } catch (err) {
            console.error('Failed to copy: ', err);
            toast('Failed to copy to clipboard', 'error');
        }
    }

    // Handle form submission
    async function handleSubmit() {
        loading = true;
        errors = {};
    
        let formData = prepareFormData(form);
        
        try {
            const response = await fetch(route('api.v1.admin.api-keys.store'), {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: formData
            });
            
            const data = await response.json();
            
            if (response.ok) {
                // Success - show generated credentials
                generatedCredentials = data.data;
                showCredentials = true;
                errors = {};
                
                toast('API key created successfully', 'success');
            } else {
                // Handle validation errors
                if (data.errors) {
                    errors = data.errors;
                } else {
                    // Handle other errors
                    console.error('Error creating api key:', data.message || 'Unknown error');
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
            name: ''
        };
        errors = {};
        generatedCredentials = null;
        showCredentials = false;
        
        // Notify parent to hide form
        dispatch('canceled');
    }

    // Handle done (after showing credentials)
    function handleDone() {
        // Reset everything
        form = {
            name: ''
        };
        errors = {};
        generatedCredentials = null;
        showCredentials = false;
        
        // Notify parent to refresh data and hide form
        dispatch('created');
    }
</script>

<div class="space-y-6">    
    {#if !showCredentials}
        <!-- Form Content -->
        <form on:submit|preventDefault={handleSubmit} class="space-y-5">
            <!-- General Error Message -->
            {#if errors.general}
                <div class="p-3 bg-destructive/10 border border-destructive/20 rounded-lg">
                    <p class="text-sm text-destructive">{errors.general}</p>
                </div>
            {/if}
            
            <!-- API Key Name -->
            <div class="flex flex-col gap-2">
                <label class="text-sm font-medium text-mono" for="name">
                    API Key Name <span class="text-destructive">*</span>
                </label>
                <input
                    id="name"
                    type="text"
                    class="kt-input {errors.name ? 'kt-input-error' : ''}"
                    placeholder="Enter API key name"
                    bind:value={form.name}
                    disabled={loading}
                />
                {#if errors.name}
                    <p class="text-sm text-destructive">{errors.name}</p>
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
                        <i class="fa-solid fa-key mr-2"></i>
                        Create API Key
                    {/if}
                </button>
            </div>
        </form>
    {:else}
        <!-- Generated Credentials Display -->
        <div class="space-y-5">
            <!-- API Endpoint URL -->
            <div class="flex flex-col gap-2">
                <label class="text-sm font-medium text-mono">API Endpoint URL</label>
                <div class="flex items-center gap-2">
                    <input
                        type="text"
                        class="kt-input font-mono text-sm"
                        value={window.location.origin + '/api/v1/{route}'}
                        readonly
                    />
                    <button
                        type="button"
                        class="kt-btn kt-btn-secondary px-3"
                        on:click={() => copyToClipboard(window.location.origin + '/api/v1/{route}')}
                        title="Copy API Endpoint URL"
                    >
                        <i class="fa-solid fa-copy"></i>
                    </button>
                </div>
            </div>

            <!-- API Key -->
            <div class="flex flex-col gap-2">
                <label class="text-sm font-medium text-mono">API Key</label>
                <div class="flex items-center gap-2">
                    <input
                        type="text"
                        class="kt-input font-mono text-sm"
                        value={generatedCredentials.key}
                        readonly
                    />
                    <button
                        type="button"
                        class="kt-btn kt-btn-secondary px-3"
                        on:click={() => copyToClipboard(generatedCredentials.key)}
                        title="Copy API Key"
                    >
                        <i class="fa-solid fa-copy"></i>
                    </button>
                </div>
            </div>

            <!-- Secret Key -->
            <div class="flex flex-col gap-2">
                <label class="text-sm font-medium text-mono">Secret Key</label>
                <div class="flex items-center gap-2">
                    <input
                        type="text"
                        class="kt-input font-mono text-sm"
                        value={generatedCredentials.secret}
                        readonly
                    />
                    <button
                        type="button"
                        class="kt-btn kt-btn-secondary px-3"
                        on:click={() => copyToClipboard(generatedCredentials.secret)}
                        title="Copy Secret Key"
                    >
                        <i class="fa-solid fa-copy"></i>
                    </button>
                </div>
            </div>

            <!-- Security Warning -->
            <div class="p-4 bg-amber-50 border border-amber-200 rounded-lg">
                <div class="flex items-start gap-3">
                    <i class="fa-solid fa-triangle-exclamation text-amber-600 mt-0.5"></i>
                    <div>
                        <h3 class="text-sm font-semibold text-amber-800 mb-2">Important Security Notice</h3>
                        <ul class="text-sm text-amber-700 space-y-1">
                            <li>• Store these credentials securely - they will only be shown once</li>
                            <li>• Never share your API key or secret with unauthorized parties</li>
                            <li>• If compromised, immediately delete and create a new API key</li>
                            <li>• Keep your secret key private and never expose it in client-side code</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Action Button -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-border">
                <button
                    type="button"
                    class="kt-btn kt-btn-primary"
                    on:click={handleDone}
                >
                    <i class="fa-solid fa-check mr-2"></i>
                    Done
                </button>
            </div>
        </div>
    {/if}
</div>
