<script>
    /**
     * Topbar — Metronic demo1 header, Svelte 5. Chrome (dropdowns, theme switch)
     * is KTUI-driven; language switch + logout hit the app. Language is a flag
     * dropdown so new locales are one config line (localeMeta + supported_locales).
     */
    import { inertia, router } from '@inertiajs/svelte';
    import Breadcrumbs from './Breadcrumbs.svelte';
    import { authUser } from '@/lib/permissions';
    import { api } from '@/lib/api/client';
    import { locale as currentLocale, t } from '@/lib/i18n';
    import { sidebarTheme, toggleSidebarTheme } from '@/lib/sidebar';

    let { breadcrumbs = [] } = $props();

    const user = authUser();

    // Locale display metadata — add a line here to offer a new language.
    const localeMeta = {
        en: { label: 'English', flag: 'united-states' },
        ar: { label: 'العربية', flag: 'saudi-arabia' },
    };
    const locales = Object.keys(localeMeta);

    function setLocale(loc) {
        if (loc !== $currentLocale) {
            router.post(route('web.locale.set'), { locale: loc }, { preserveScroll: true });
        }
    }

    async function logout() {
        try {
            await api.post(route('api.v1.auth.logout'));
        } finally {
            window.location.assign(route('web.auth.login-page'));
        }
    }
</script>

<header
    class="kt-header fixed top-0 z-10 start-0 end-0 flex items-stretch shrink-0 bg-background"
    data-kt-sticky="true"
    data-kt-sticky-class="border-b border-border"
    data-kt-sticky-name="header"
    id="header"
>
    <div class="kt-container-fixed flex justify-between items-stretch lg:gap-4" id="headerContainer">
        <!-- Mobile logo + toggles -->
        <div class="flex gap-2.5 lg:hidden items-center -ms-1">
            <a class="shrink-0" href={route('web.home')} use:inertia>
                <img class="max-h-[25px] w-full" src="/assets/media/app/mini-logo.svg" alt="Logo" />
            </a>
            <button class="kt-btn kt-btn-icon kt-btn-ghost" data-kt-drawer-toggle="#sidebar">
                <i class="ki-filled ki-menu"></i>
            </button>
        </div>

        <!-- Breadcrumbs -->
        <div class="flex items-center">
            <Breadcrumbs items={breadcrumbs} />
        </div>

        <!-- Topbar actions -->
        <div class="flex items-center gap-2.5">
            <!-- Language (flags) -->
            <div data-kt-dropdown="true" data-kt-dropdown-offset="10px, 10px" data-kt-dropdown-placement="bottom-end" data-kt-dropdown-trigger="click">
                <button class="kt-btn kt-btn-ghost kt-btn-icon size-9 rounded-full hover:bg-primary/10" data-kt-dropdown-toggle="true" aria-label="Language">
                    <img class="size-5 rounded-full" src="/assets/media/flags/{localeMeta[$currentLocale]?.flag ?? 'united-states'}.svg" alt={$currentLocale} />
                </button>
                <div class="kt-dropdown-menu w-[200px]" data-kt-dropdown-menu="true">
                    <ul class="kt-dropdown-menu-sub">
                        {#each locales as loc}
                            <li class={$currentLocale === loc ? 'active' : ''}>
                                <button class="kt-dropdown-menu-link w-full" onclick={() => setLocale(loc)}>
                                    <img class="inline-block size-4 rounded-full" src="/assets/media/flags/{localeMeta[loc].flag}.svg" alt={loc} />
                                    <span class="kt-menu-title grow">{localeMeta[loc].label}</span>
                                    {#if $currentLocale === loc}<i class="ki-solid ki-check-circle text-green-500 text-base"></i>{/if}
                                </button>
                            </li>
                        {/each}
                    </ul>
                </div>
            </div>

            <!-- User -->
            <div
                class="shrink-0"
                data-kt-dropdown="true"
                data-kt-dropdown-offset="10px, 10px"
                data-kt-dropdown-placement="bottom-end"
                data-kt-dropdown-trigger="click"
            >
                <div class="cursor-pointer shrink-0" data-kt-dropdown-toggle="true">
                    <img class="size-9 rounded-full border-2 border-green-500 shrink-0" src={user?.avatar_url || '/assets/media/app/mini-logo.svg'} alt={user?.username} />
                </div>
                <div class="kt-dropdown-menu w-[250px]" data-kt-dropdown-menu="true">
                    <div class="flex items-center justify-between px-2.5 py-1.5 gap-1.5">
                        <div class="flex items-center gap-2">
                            <img class="size-9 shrink-0 rounded-full border-2 border-green-500" src={user?.avatar_url || '/assets/media/app/mini-logo.svg'} alt={user?.username} />
                            <div class="flex flex-col gap-1.5">
                                <span class="text-sm text-foreground font-semibold leading-none">{user?.firstname} {user?.lastname}</span>
                                <span class="text-xs text-secondary-foreground font-medium leading-none">{user?.email}</span>
                            </div>
                        </div>
                    </div>
                    <ul class="kt-dropdown-menu-sub">
                        <li><div class="kt-dropdown-menu-separator"></div></li>
                    </ul>
                    <div class="px-2.5 pt-1.5 mb-2.5 flex flex-col gap-3.5">
                        <!-- Sidebar theme (independent of page dark mode) -->
                        <div class="flex items-center gap-2 justify-between">
                            <span class="flex items-center gap-2">
                                <i class="ki-filled ki-color-swatch text-base text-muted-foreground"></i>
                                <span class="font-medium text-2sm">{$t('common.actions.dark_sidebar')}</span>
                            </span>
                            <input class="kt-switch" type="checkbox" checked={$sidebarTheme === 'dark'} onchange={toggleSidebarTheme} />
                        </div>
                        <!-- Dark mode (Metronic KTUI theme switch) -->
                        <div class="flex items-center gap-2 justify-between">
                            <span class="flex items-center gap-2">
                                <i class="ki-filled ki-moon text-base text-muted-foreground"></i>
                                <span class="font-medium text-2sm">{$t('common.actions.dark_mode')}</span>
                            </span>
                            <input class="kt-switch" data-kt-theme-switch-state="dark" data-kt-theme-switch-toggle="true" type="checkbox" value="1" />
                        </div>
                        <button class="kt-btn kt-btn-outline justify-center w-full" onclick={logout}>
                            <i class="ki-filled ki-exit-right"></i>
                            {$t('common.actions.logout')}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
