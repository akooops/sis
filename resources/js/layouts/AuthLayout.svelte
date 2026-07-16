<script>
    /**
     * AuthLayout — Metronic branded two-column auth shell. Form column renders
     * `children`; the branded panel carries the cover art, logo + tagline.
     *
     * The cover sits under a primary-tinted overlay rather than being the panel
     * background outright: the logo and tagline are light-on-brand, and a photo
     * behind them at full strength makes both unreadable.
     */
    let { title = null, children } = $props();
    const appName = 'Saud International Schools';
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
    <div
        class="relative order-1 lg:order-2 lg:rounded-xl lg:border lg:border-border lg:m-5 overflow-hidden bg-primary text-primary-foreground"
    >
        <img
            class="absolute inset-0 size-full object-cover"
            src="/assets/media/images/auth-page-cover.jpg"
            alt=""
            aria-hidden="true"
        />
        <div class="absolute inset-0 bg-primary/85"></div>

        <div class="relative flex h-full flex-col justify-between gap-6 p-8 lg:p-16">
            <img class="h-[64px] max-w-none self-start" src="/assets/media/app/logo.png" alt={appName} />
            <div class="flex flex-col gap-3">
                <h3 class="text-2xl font-semibold">{appName}</h3>
                <div class="text-base font-medium opacity-90">
                    A content management system for the schools' day-to-day: the people,
                    the records and the files behind them with every change signed,
                    dated and kept.
                </div>
            </div>
            <div class="text-sm opacity-70">© {new Date().getFullYear()} {appName}</div>
        </div>
    </div>
</div>
