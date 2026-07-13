<script>
    import { createEventDispatcher } from 'svelte';
    import PhoneInput from '../../Shared/Utils/Forms/PhoneInput.svelte';
    import ImageInput from '../../Shared/Utils/Forms/ImageInput.svelte';
    import { onMount } from 'svelte';

    const dispatch = createEventDispatcher();

    // Props
    export let user = null;

    // Form data
    let form = {
        firstname: '',
        lastname: '',
        name: '',
        email: '',
        phone: '',
        password: '',
        confirm_password: '',
        avatar: null
    };

    // Form errors
    let errors = {};

    // Loading state
    let loading = false;

    // Password change toggle
    let changePassword = false;

    // Initialize form with user data
    $: if (user) {
        form = {
            firstname: user.firstname || '',
            lastname: user.lastname || '',
            name: user.name || '',
            email: user.email || '',
            phone: user.phone || '',
            password: '',
            confirm_password: '',
            avatar: null
        };
    }

    // Handle image upload events
    function handleImageChange(event) {
        form.avatar = event.detail.file;
    }

    // Handle form submission
    async function handleSubmit() {
        loading = true;
        errors = {};
        
        try {
            // Create FormData for file upload
            let formData = prepareFormData(form, true);
            
            const response = await fetch(route('api.v1.admin.users.update', user.id), {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: formData
            });
            
            const data = await response.json();
            
            if (response.ok) {
                // Success - notify parent to refresh data and hide form
                toast('User updated successfully', 'success');
                dispatch('updated');
            } else {
                // Handle validation errors
                if (data.errors) {
                    errors = data.errors;
                } else {
                    // Handle other errors
                    console.error('Error updating user:', data.message || 'Unknown error');
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
        // Reset form to original user data
        if (user) {
            form = {
                firstname: user.firstname || '',
                lastname: user.lastname || '',
                name: user.name || '',
                email: user.email || '',
                phone: user.phone || '',
                password: '',
                confirm_password: '',
                avatar: null
            };
        }
        errors = {};
        changePassword = false;
        
        // Notify parent to hide form
        dispatch('canceled');
    }

    $: if (changePassword) {
        if (window.KTTogglePassword && window.KTTogglePassword.init) {
            setTimeout(() => {
                window.KTTogglePassword.init();
            }, 200);
        }
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

            <div class="flex flex-col gap-2">
                <ImageInput
                    bind:value={form.avatar}
                    defaultImage={user?.avatar_url}
                    disabled={loading}
                    on:change={handleImageChange}
                />
                
                {#if errors.avatar}
                    <p class="text-sm text-destructive">{errors.avatar}</p>
                {/if}
            </div>

            <!-- Username -->
            <div class="flex flex-col gap-2">
                <label class="text-sm font-medium text-mono" for="name">
                    Username <span class="text-destructive">*</span>
                </label>
                <input
                    id="name"
                    type="text"
                    class="kt-input {errors.name ? 'kt-input-error' : ''}"
                    placeholder="Enter username"
                    bind:value={form.name}
                    disabled={loading}
                />
                {#if errors.name}
                    <p class="text-sm text-destructive">{errors.name}</p>
                {/if}
            </div>

            <div class="flex grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- First Name -->
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-mono" for="firstname">
                        First Name <span class="text-destructive">*</span>
                    </label>
                    <input
                        id="firstname"
                        type="text"
                        class="kt-input {errors.firstname ? 'kt-input-error' : ''}"
                        placeholder="Enter first name"
                        bind:value={form.firstname}
                        disabled={loading}
                    />
                    {#if errors.firstname}
                        <p class="text-sm text-destructive">{errors.firstname}</p>
                    {/if}
                </div>

                <!-- Last Name -->
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-mono" for="lastname">
                        Last Name <span class="text-destructive">*</span>
                    </label>
                    <input
                        id="lastname"
                        type="text"
                        class="kt-input {errors.lastname ? 'kt-input-error' : ''}"
                        placeholder="Enter last name"
                        bind:value={form.lastname}
                        disabled={loading}
                    />
                    {#if errors.lastname}
                        <p class="text-sm text-destructive">{errors.lastname}</p>
                    {/if}
                </div>
            </div>

            <div class="flex grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Email -->
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-mono" for="email">
                        Email <span class="text-destructive">*</span>
                    </label>
                    <input
                        id="email"
                        type="email"
                        class="kt-input {errors.email ? 'kt-input-error' : ''}"
                        placeholder="Enter email address"
                        bind:value={form.email}
                        disabled={loading}
                    />
                    {#if errors.email}
                        <p class="text-sm text-destructive">{errors.email}</p>
                    {/if}
                </div>

                <!-- Phone -->
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-mono" for="phone">
                        Phone
                    </label>
                    <PhoneInput
                        bind:value={form.phone}
                        placeholder="Enter phone number"
                        disabled={loading}
                    />
                    {#if errors.phone}
                        <p class="text-sm text-destructive">{errors.phone}</p>
                    {/if}
                </div>
            </div>

            <!-- Password Change Toggle -->
            <div class="flex flex-col gap-2">
                <label class="text-sm font-medium text-mono">
                    Change Password
                </label>
                <div class="flex items-center gap-2">
                    <input 
                        class="kt-switch" 
                        type="checkbox" 
                        id="change_password" 
                        bind:checked={changePassword}
                        disabled={loading}
                    />
                    <span class="text-sm text-muted-foreground">
                        {changePassword ? 'Password fields will be shown' : 'Keep current password'}
                    </span>
                </div>
            </div>

            <!-- Password Fields (only show if changePassword is true) -->
            {#if changePassword}
                <div class="flex grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Password -->
                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-medium text-mono" for="password">
                            New Password <span class="text-destructive">*</span>
                        </label>
                        <div class="kt-input {errors.password ? 'kt-input-error' : ''}" data-kt-toggle-password="true">
                            <input 
                                id="password"
                                name="password" 
                                placeholder="Enter new password (min 8 characters)" 
                                type="password" 
                                bind:value={form.password}
                                disabled={loading}
                                required
                            />
                            <button class="kt-btn kt-btn-sm kt-btn-ghost kt-btn-icon bg-transparent! -me-1.5" data-kt-toggle-password-trigger="true" type="button">
                                <span class="kt-toggle-password-active:hidden">
                                    <i class="ki-filled ki-eye text-muted-foreground"></i>
                                </span>
                                <span class="hidden kt-toggle-password-active:block">
                                    <i class="ki-filled ki-eye-slash text-muted-foreground"></i>
                                </span>
                            </button>
                        </div>
                        {#if errors.password}
                            <p class="text-sm text-destructive">{errors.password}</p>
                        {/if}
                    </div>
                
                    <!-- Confirm Password -->
                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-medium text-mono" for="confirm_password">
                            Confirm New Password <span class="text-destructive">*</span>
                        </label>
                        <div class="kt-input {errors.confirm_password ? 'kt-input-error' : ''}" data-kt-toggle-password="true">
                            <input 
                                id="confirm_password"
                                name="confirm_password" 
                                placeholder="Confirm new password" 
                                type="password" 
                                bind:value={form.confirm_password}
                                disabled={loading}
                                required
                            />
                            <button class="kt-btn kt-btn-sm kt-btn-ghost kt-btn-icon bg-transparent! -me-1.5" data-kt-toggle-password-trigger="true" type="button">
                                <span class="kt-toggle-password-active:hidden">
                                    <i class="ki-filled ki-eye text-muted-foreground"></i>
                                </span>
                                <span class="hidden kt-toggle-password-active:block">
                                    <i class="ki-filled ki-eye-slash text-muted-foreground"></i>
                                </span>
                            </button>
                        </div>
                        {#if errors.confirm_password}
                            <p class="text-sm text-destructive">{errors.confirm_password}</p>
                        {/if}
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
                    disabled={loading}
                >
                    {#if loading}
                        <i class="fa-solid fa-spinner fa-spin mr-2"></i>
                        Updating...
                    {:else}
                        <i class="fa-solid fa-save mr-2"></i>
                        Update User
                    {/if}
                </button>
            </div>
        </form>
</div>