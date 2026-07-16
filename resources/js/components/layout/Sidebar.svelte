<script>
    /**
     * Sidebar — Metronic demo1 sidebar, config-driven (lib/menu.js), Svelte 5.
     *
     * - Accordion expand is Svelte-driven (npm @keenthemes/ktui has no KTMenu).
     * - Uses the exact Metronic class names (kt-menu-title/arrow/accordion) so the
     *   demo1.css collapse rules hide sub-items in the narrow (collapsed) state and
     *   reveal them on hover — no JS needed for that.
     * - Light/dark sidebar theme is INDEPENDENT of the page theme: a scoped `dark`
     *   class on the root flips the theme tokens inside the sidebar only.
     */
    import { onMount } from 'svelte';
    import { inertia, page } from '@inertiajs/svelte';
    import { slide } from 'svelte/transition';
    import { adminMenu } from '@/lib/menu';
    import { hasPermission } from '@/lib/permissions';
    import { sidebarTheme, sidebarExpanded } from '@/lib/sidebar';

    const isDark = $derived($sidebarTheme === 'dark');

    // Suppress the accordion slide on (re)mount — the sidebar is rebuilt on every
    // Inertia visit, so only animate genuine user toggles, not each navigation.
    let mounted = $state(false);
    onMount(() => {
        mounted = true;
    });

    function hasRoute(name) {
        try {
            return route().has(name);
        } catch {
            return false;
        }
    }
    function href(name, params) {
        return hasRoute(name) ? route(name, params) : null;
    }
    function allowed(node) {
        return !node.permission || hasPermission(node.permission);
    }
    function isActive(name) {
        void $page.url;
        try {
            return hasRoute(name) && (route().current(name) || route().current(`${name}.*`));
        } catch {
            return false;
        }
    }
    function groupActive(group) {
        return (group.children ?? []).some((c) => isActive(c.route));
    }

    const visibleMenu = $derived(
        adminMenu
            .map((node) => (node.children ? { ...node, children: node.children.filter(allowed) } : node))
            .filter((node) => (node.children ? node.children.length > 0 : allowed(node))),
    );

    // A group is open when explicitly toggled (stored), else it defaults to open
    // while it's the active section. State lives in the module store so it
    // persists across the sidebar's per-navigation remounts (no re-animation).
    function isOpen(node) {
        const stored = $sidebarExpanded[node.label];
        return stored === undefined ? groupActive(node) : stored;
    }
    function toggle(node) {
        const next = !isOpen(node);
        sidebarExpanded.update((e) => ({ ...e, [node.label]: next }));
    }

    // Collapse the whole sidebar (body class drives the demo1 layout width + the
    // collapsed-state CSS that hides titles/arrows/accordions until hover).
    let collapsed = $state(false);
    function toggleCollapse() {
        collapsed = !collapsed;
        document.body.classList.toggle('kt-sidebar-collapse', collapsed);
    }
</script>

<div
    class="kt-sidebar {isDark ? 'dark' : ''} bg-background border-e border-border fixed top-0 bottom-0 z-20 hidden lg:flex flex-col items-stretch shrink-0 h-screen [--kt-drawer-enable:true] lg:[--kt-drawer-enable:false]"
    data-kt-drawer="true"
    data-kt-drawer-class="kt-drawer kt-drawer-start top-0 bottom-0"
    id="sidebar"
>
    <div class="kt-sidebar-header hidden lg:flex items-center relative justify-between px-3 lg:px-6 shrink-0" id="sidebar_header">
        <a href={href('web.home')} use:inertia>
            {#if isDark}
                <img class="default-logo h-[42px] max-w-none" src="/assets/media/app/logo-dark.svg" alt="Logo" />
                <img class="small-logo h-[22px] max-w-none" src="/assets/media/app/mini-logo-dark.svg" alt="Logo" />
            {:else}
                <img class="default-logo h-[42px] max-w-none" src="/assets/media/app/logo.svg" alt="Logo" />
                <img class="small-logo h-[22px] max-w-none" src="/assets/media/app/mini-logo.svg" alt="Logo" />
            {/if}
        </a>
        <button
            class="kt-btn kt-btn-outline kt-btn-icon size-[30px] absolute start-full top-2/4 z-40 -translate-x-2/4 -translate-y-2/4 rtl:translate-x-2/4"
            onclick={toggleCollapse}
            aria-label="Toggle sidebar"
        >
            <i class="ki-filled ki-black-left-line transition-all duration-300 {collapsed ? 'rotate-180' : ''} rtl:rotate-180"></i>
        </button>
    </div>

    <div class="kt-sidebar-content flex grow shrink-0 py-5 pe-2 overflow-hidden" id="sidebar_content">
        <div class="grow shrink-0 flex flex-col ps-2 lg:ps-5 pe-1 lg:pe-3 overflow-y-auto" id="sidebar_scrollable">
            <div class="kt-menu flex flex-col grow gap-1" id="sidebar_menu">
                {#each visibleMenu as node (node.label)}
                    {#if node.children}
                        <!-- Accordion group -->
                        <div class="kt-menu-item {isOpen(node) ? 'show' : ''}">
                            <div
                                class="kt-menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[8px] rounded-lg hover:bg-accent"
                                onclick={() => toggle(node)}
                                role="button"
                                tabindex="0"
                            >
                                <span class="kt-menu-icon items-start text-muted-foreground w-[20px]"><i class="{node.icon} text-lg"></i></span>
                                <span class="kt-menu-title text-sm font-medium text-foreground grow">{node.label}</span>
                                <span class="kt-menu-arrow text-muted-foreground w-[20px] shrink-0 flex justify-end">
                                    <i class="ki-filled {isOpen(node) ? 'ki-minus' : 'ki-plus'} text-[11px]"></i>
                                </span>
                            </div>
                            {#if isOpen(node)}
                                <div
                                    class="kt-menu-accordion flex flex-col gap-1 ps-[10px] relative before:absolute before:start-[20px] before:top-0 before:bottom-0 before:border-s before:border-border"
                                    transition:slide={{ duration: mounted ? 250 : 0 }}
                                >
                                    {#each node.children as child (child.route)}
                                        <div class="kt-menu-item">
                                            <a
                                                class="kt-menu-link flex border border-transparent items-center grow rounded-lg hover:bg-accent gap-[14px] ps-[10px] pe-[10px] py-[8px] {isActive(child.route) ? 'bg-accent' : ''}"
                                                href={href(child.route, child.params)}
                                                use:inertia
                                                tabindex="0"
                                            >
                                                <span class="kt-menu-bullet w-[6px] flex shrink-0"><span class="size-[6px] rounded-full {isActive(child.route) ? 'bg-primary' : 'bg-muted-foreground/40'}"></span></span>
                                                <span class="kt-menu-title text-2sm {isActive(child.route) ? 'font-semibold text-primary' : 'font-normal text-foreground'}">{child.label}</span>
                                            </a>
                                        </div>
                                    {/each}
                                </div>
                            {/if}
                        </div>
                    {:else}
                        <!-- Single link -->
                        <div class="kt-menu-item">
                            <a
                                class="kt-menu-link flex items-center gap-[10px] ps-[10px] pe-[10px] py-[8px] border border-transparent rounded-lg hover:bg-accent {isActive(node.route) ? 'bg-accent' : ''}"
                                href={href(node.route, node.params)}
                                use:inertia
                                tabindex="0"
                            >
                                <span class="kt-menu-icon items-start text-muted-foreground w-[20px]"><i class="{node.icon} text-lg"></i></span>
                                <span class="kt-menu-title text-sm font-medium {isActive(node.route) ? 'text-primary' : 'text-foreground'}">{node.label}</span>
                            </a>
                        </div>
                    {/if}
                {/each}
            </div>
        </div>
    </div>
</div>
