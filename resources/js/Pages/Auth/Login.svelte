<script>
    import AuthLayout from '../Shared/Layouts/AuthLayout.svelte';
    import { onMount } from 'svelte';
    
    const pageTitle = 'Login to your account';
    
    let emailForm = {
        email: '',
        password: '',
        remember: false
    };
    
    let errors = {};
    let loading = false;
    
    async function handleLogin() {
        loading = true;
        errors = {};

        try {
            let formData = prepareFormData(emailForm);

            const response = await fetch(route('api.v1.auth.login'), {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: formData
            });
            
            const data = await response.json();
            
            if (response.ok) {
                window.location.replace(data.data.redirect_url);
            } else {
                if (data.errors) {
                    errors = data.errors;
                } else if (data.code === 401) {
                    errors.email = data.message;
                } else {
                    console.error('Error logging in:', data.message || 'Unknown error');
                    errors = { general: data.message || 'Unknown error' };
                }
            }
        } catch (error) {
            console.error('Network error:', error);
            errors = { general: 'Network error occurred. Please try again.' };
        } finally {
            loading = false;
        }
    }

    onMount(() => {
        setTimeout(() => {
            if (window.KTTogglePassword && window.KTTogglePassword.init) {
                window.KTTogglePassword.init();
            }
        }, 100);
    });
</script>

<svelte:head>
    <title>Novonordisk Supply Chain Management System - {pageTitle}</title>
</svelte:head>

<AuthLayout {pageTitle}>
    <div class="flex justify-center items-center p-8 lg:p-10 order-2 lg:order-1">
        <div class="kt-card max-w-[370px] w-full">
            <div class="kt-card-content flex flex-col gap-5 p-10">
                <form on:submit|preventDefault={handleLogin} class="flex flex-col gap-5">
                    <div class="flex flex-col gap-1">
                        <label class="kt-form-label font-normal text-mono" for="email">
                            Email
                        </label>
                        <input 
                            id="email"
                            name="email"
                            class="kt-input {errors.email ? 'kt-input-error' : ''}" 
                            placeholder="email@email.com" 
                            type="email" 
                            bind:value={emailForm.email}
                            disabled={loading}
                            required
                        />
                        {#if errors.email}
                            <span class="text-sm text-destructive">{errors.email}</span>
                        {/if}
                    </div>
                    
                    <div class="flex flex-col gap-1">
                        <div class="flex items-center justify-between gap-1">
                            <label class="kt-form-label font-normal text-mono" for="password">
                                Password
                            </label>
                        </div>
                        <div class="kt-input {errors.password ? 'kt-input-error' : ''}" data-kt-toggle-password="true">
                            <input 
                                id="password"
                                name="password" 
                                placeholder="Enter Password" 
                                type="password" 
                                bind:value={emailForm.password}
                                disabled={loading}
                                required
                            />
                            <button class="kt-btn kt-btn-sm kt-btn-ghost kt-btn-icon bg-transparent! -me-1.5" data-kt-toggle-password-trigger="true" type="button" aria-label="Toggle password visibility">
                                <span class="kt-toggle-password-active:hidden">
                                    <i class="ki-filled ki-eye text-muted-foreground"></i>
                                </span>
                                <span class="hidden kt-toggle-password-active:block">
                                    <i class="ki-filled ki-eye-slash text-muted-foreground"></i>
                                </span>
                            </button>
                        </div>
                        {#if errors.password}
                            <span class="text-sm text-destructive">{errors.password}</span>
                        {/if}
                    </div>
                    
                    <label class="kt-label">
                        <input 
                            class="kt-checkbox kt-checkbox-sm" 
                            name="remember" 
                            type="checkbox" 
                            bind:checked={emailForm.remember}
                            disabled={loading}
                        />
                        <span class="kt-checkbox-label">
                            Remember me
                        </span>
                    </label>

                    {#if errors.general}
                        <span class="text-sm text-destructive">{errors.general}</span>
                    {/if}
                    
                    <button 
                        type="submit" 
                        class="kt-btn kt-btn-primary flex justify-center grow"
                        disabled={loading}
                    >
                        {#if loading}
                            <i class="ki-outline ki-loading text-base animate-spin me-2"></i>
                            Signing In...
                        {:else}
                            Sign In
                        {/if}
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="lg:rounded-xl lg:border lg:border-border lg:m-5 order-1 lg:order-2 bg-top xxl:bg-center xl:bg-cover bg-no-repeat branded-bg">
        <div class="flex flex-col p-8 lg:p-16 gap-4">
            <a href="#">
                <img class="h-[50px] max-w-none" src="/assets/media/app/logo-dark.svg"/>
            </a>
            <div class="flex flex-col gap-3">
                <h3 class="text-2xl font-semibold">
                    Novonordisk Supply Chain Management System
                </h3>
                <div class="text-base font-medium">
                    A multi-agent platform where specialized AI agents collaborate to satistfy demand, optimize inventory, and coordinate planning to be smarter and more efficient.
                </div>
            </div>
        </div>
    </div>
</AuthLayout>
