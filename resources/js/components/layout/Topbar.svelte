<script>
    /**
     * Topbar — Metronic demo1 header, Svelte 5. Chrome (dropdowns, theme switch)
     * is KTUI-driven; logout hits the app.
     */
    import { inertia } from '@inertiajs/svelte';
    import Breadcrumbs from './Breadcrumbs.svelte';
    import { authUser } from '@/lib/permissions';
    import { api } from '@/lib/api/client';
    import { sidebarTheme, toggleSidebarTheme } from '@/lib/sidebar';

    let { breadcrumbs = [] } = $props();

    const user = authUser();

    async function logout() {
        try {
            await api.post(route('api.v1.admin.auth.logout'));
        } finally {
            window.location.assign(route('web.admin.auth.login'));
        }
    }
</script>

<header
    class="kt-header fixed top-0 z-10 start-0 end-0 flex items-stretch shrink-0 bg-background"
    data-kt-sticky="true"
    data-kt-sticky-class="shadow-sm"
    data-kt-sticky-name="header"
    id="header"
>
    <div class="kt-container-fixed flex justify-between items-stretch lg:gap-4" id="headerContainer">
        <!-- Mobile logo + toggles -->
        <div class="flex gap-2.5 lg:hidden items-center -ms-1">
            <a class="shrink-0" href={route('web.admin.dashboard')} use:inertia>
                <img class="max-h-[25px] w-full" src="/assets/media/app/mini-logo.png" alt="Logo" />
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
            <!-- User -->
            <div
                class="shrink-0"
                data-kt-dropdown="true"
                data-kt-dropdown-offset="10px, 10px"
                data-kt-dropdown-placement="bottom-end"
                data-kt-dropdown-trigger="click"
            >
                <div class="cursor-pointer shrink-0" data-kt-dropdown-toggle="true">
                    <img class="size-9 rounded-full border-2 border-green-500 shrink-0" src={user?.avatar_url || '/assets/media/app/mini-logo.png'} alt={user?.username} />
                </div>
                <div class="kt-dropdown-menu w-[250px]" data-kt-dropdown-menu="true">
                    <div class="flex items-center justify-between px-2.5 py-1.5 gap-1.5">
                        <div class="flex items-center gap-2">
                            <img class="size-9 shrink-0 rounded-full border-2 border-green-500" src={user?.avatar_url || '/assets/media/app/mini-logo.png'} alt={user?.username} />
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
                                <span class="font-medium text-2sm">Dark Sidebar</span>
                            </span>
                            <input class="kt-switch" type="checkbox" checked={$sidebarTheme === 'dark'} onchange={toggleSidebarTheme} />
                        </div>
                        <!-- Dark mode (Metronic KTUI theme switch) -->
                        <div class="flex items-center gap-2 justify-between">
                            <span class="flex items-center gap-2">
                                <i class="ki-filled ki-moon text-base text-muted-foreground"></i>
                                <span class="font-medium text-2sm">Dark Mode</span>
                            </span>
                            <input class="kt-switch" data-kt-theme-switch-state="dark" data-kt-theme-switch-toggle="true" type="checkbox" value="1" />
                        </div>
                        <button class="kt-btn kt-btn-outline justify-center w-full" onclick={logout}>
                            <i class="ki-filled ki-exit-right"></i>
                            Log out
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
