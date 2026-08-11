<script>
    /**
     * Login — Metronic classic sign-in card, Svelte 5. Password login + Azure SSO.
     * Approval-gate (403) and bad creds (401) surface as an alert; 422 maps onto fields.
     */
    import AuthLayout from '@/layouts/AuthLayout.svelte';
    import PasswordInput from '@/components/form/PasswordInput.svelte';
    import { useForm } from '@/lib/api/useForm.svelte';
    import { ApiError } from '@/lib/api/client';

    const form = useForm({ email: '', password: '', remember: false });
    let generalError = $state(null);

    async function submit(event) {
        event.preventDefault();
        generalError = null;
        try {
            const data = await form.post(route('api.v1.admin.auth.login'));
            if (data) window.location.assign('/admin');
        } catch (err) {
            generalError = err instanceof ApiError ? err.message : 'Network error. Please try again.';
        }
    }
</script>

<AuthLayout title="Sign in">
    <div class="kt-card max-w-[400px] w-full">
        <form class="kt-card-content flex flex-col gap-5 p-10" onsubmit={submit}>
            <div class="flex flex-col gap-1 text-center">
                <h3 class="text-lg font-semibold text-mono leading-none">Sign in</h3>
                <span class="text-sm text-secondary-foreground">Enter your credentials to access your account.</span>
            </div>

            {#if generalError}
                <div class="kt-alert kt-alert-outline kt-alert-destructive" role="alert">
                    <div class="kt-alert-content">{generalError}</div>
                </div>
            {/if}

            <a href={route('api.v1.admin.auth.azure.redirect')} class="kt-btn kt-btn-outline justify-center">
                <img src="/assets/admin/media/brand-logos/azure.svg" alt="Azure" class="size-4" />
                Sign in with Azure
            </a>

            <div class="flex items-center gap-3 text-xs text-muted-foreground">
                <span class="h-px grow bg-border"></span>or continue with<span class="h-px grow bg-border"></span>
            </div>

            <div class="flex flex-col gap-1">
                <label class="kt-form-label font-normal text-mono" for="email">Email</label>
                <input
                    id="email"
                    class="kt-input {form.errors.email ? 'kt-input-error' : ''}"
                    type="email"
                    autocomplete="email"
                    placeholder="email@example.com"
                    bind:value={form.data.email}
                    disabled={form.processing}
                    required
                />
                {#if form.errors.email}<span class="text-sm text-destructive">{form.errors.email}</span>{/if}
            </div>

            <div class="flex flex-col gap-1">
                <label class="kt-form-label font-normal text-mono" for="password">Password</label>
                <PasswordInput
                    id="password"
                    autocomplete="current-password"
                    placeholder="Enter your password"
                    bind:value={form.data.password}
                    invalid={!!form.errors.password}
                    disabled={form.processing}
                />
                {#if form.errors.password}<span class="text-sm text-destructive">{form.errors.password}</span>{/if}
            </div>

            <label class="kt-label">
                <input class="kt-checkbox kt-checkbox-sm" type="checkbox" bind:checked={form.data.remember} disabled={form.processing} />
                <span class="kt-checkbox-label">Remember me</span>
            </label>

            <button type="submit" class="kt-btn kt-btn-primary flex justify-center grow" disabled={form.processing}>
                {#if form.processing}<i class="ki-filled ki-loading text-base animate-spin me-2"></i>Signing in…{:else}Sign in{/if}
            </button>
        </form>
    </div>
</AuthLayout>
