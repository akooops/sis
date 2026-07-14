<script>
    /**
     * AuthLayout — Metronic branded two-column auth shell. Form column renders
     * `children`; the branded panel carries the logo + tagline.
     */
    let { title = null, children } = $props();
    const appName = 'Novonordisk Supply Chain Management System';
</script>

<svelte:head>
    <title>{title ? `${appName} — ${title}` : appName}</title>
</svelte:head>

<div class="grid lg:grid-cols-2 grow bg-background" style="min-height: 100vh;">
    <!-- Form column -->
    <div class="flex justify-center items-center p-8 lg:p-10 order-2 lg:order-1">
        {@render children?.()}
    </div>

    <!-- Branded panel -->
    <div class="order-1 lg:order-2 lg:rounded-xl lg:border lg:border-border lg:m-5 bg-primary text-primary-foreground flex flex-col justify-between p-8 lg:p-16 gap-6">
        <img class="h-[46px] max-w-none self-start" src="/assets/media/app/logo-dark.svg" alt={appName} />
        <div class="flex flex-col gap-3">
            <h3 class="text-2xl font-semibold">{appName}</h3>
            <div class="text-base font-medium opacity-90">
                A supply-chain platform where specialized AI agents collaborate to satisfy demand,
                optimize inventory, and coordinate planning — smarter and more efficient.
            </div>
        </div>
        <div class="text-sm opacity-70">© {new Date().getFullYear()} Novonordisk</div>
    </div>
</div>
