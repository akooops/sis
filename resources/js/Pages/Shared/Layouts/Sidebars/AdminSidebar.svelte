<script>
    import { inertia } from '@inertiajs/svelte';
</script>

<div class="kt-sidebar bg-background border-e border-e-border fixed top-0 bottom-0 z-20 hidden lg:flex flex-col items-stretch shrink-0 [--kt-drawer-enable:true] lg:[--kt-drawer-enable:false]" 
    data-kt-drawer="true" 
    data-kt-drawer-class="kt-drawer kt-drawer-start top-0 bottom-0" 
    id="sidebar"
    
    style="height: 100vh;">
    <div class="kt-sidebar-header hidden lg:flex items-center relative justify-between px-3 lg:px-6 shrink-0" id="sidebar_header">
        <a class="dark:hidden" href="{route('web.admin.dashboard.index')}">
            <img class="default-logo h-[50px] max-w-none" src="/assets/media/app/logo.svg"/>
            <img class="small-logo h-[22px] max-w-none" src="/assets/media/app/mini-logo.svg"/>
        </a>
        <a class="hidden dark:block" href="{route('web.admin.dashboard.index')}">
            <img class="default-logo h-[50px] max-w-none" src="/assets/media/app/logo-dark.svg"/>
            <img class="small-logo h-[22px] max-w-none" src="/assets/media/app/mini-logo-dark.svg"/>
        </a>
        <button class="kt-btn kt-btn-outline kt-btn-icon size-[30px] absolute start-full top-2/4 -translate-x-2/4 -translate-y-2/4 rtl:translate-x-2/4" data-kt-toggle="body" data-kt-toggle-class="kt-sidebar-collapse" id="sidebar_toggle">
            <i class="fa-solid fa-chevron-left kt-toggle-active:rotate-180 transition-all duration-300 rtl:translate rtl:rotate-180 rtl:kt-toggle-active:rotate-0">
            </i>
        </button>
    </div>
    <div class="kt-sidebar-content flex grow shrink-0 py-5 pe-2" id="sidebar_content">
        <div class="kt-scrollable-y-hover grow shrink-0 flex ps-2 lg:ps-5 pe-1 lg:pe-3" data-kt-scrollable="true" data-kt-scrollable-dependencies="#sidebar_header" data-kt-scrollable-height="auto" data-kt-scrollable-offset="0px" data-kt-scrollable-wrappers="#sidebar_content" id="sidebar_scrollable">
            <!-- Sidebar Menu -->
            <div class="kt-menu flex flex-col grow gap-1" data-kt-menu="true" data-kt-menu-accordion-expand-all="true" id="sidebar_menu">
                <div class="kt-menu-item" 
                    data-kt-menu-item-toggle="accordion" 
                    data-kt-menu-item-trigger="click"
                    class:show={isActiveRoute([
                        route('web.admin.suppliers.index'),
                        route('web.admin.products.index'),
                        route('web.admin.production-sites.index'),
                        route('web.admin.production-lines.index'),
                    ])}>
                    <div class="kt-menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]" tabindex="0">
                        <span class="kt-menu-icon items-start text-muted-foreground w-[20px]">
                            <i class="fa-solid fa-book text-lg">
                            </i>
                        </span>
                        
                        <span class="kt-menu-title text-sm font-medium text-foreground kt-menu-item-active:text-primary kt-menu-link-hover:!text-primary">
                            Knowledge Base
                        </span>

                        <span class="kt-menu-arrow text-muted-foreground w-[20px] shrink-0 justify-end ms-1 me-[-10px]">
                            <span class="inline-flex kt-menu-item-show:hidden">
                                <i class="fa-solid fa-plus text-[11px]">
                                </i>
                            </span>

                            <span class="hidden kt-menu-item-show:inline-flex">
                                <i class="fa-solid fa-minus text-[11px]">
                                </i>
                            </span>
                        </span>
                    </div>

                    <div class="kt-menu-accordion gap-1 ps-[10px] relative before:absolute before:start-[20px] before:top-0 before:bottom-0 before:border-s before:border-border">
                        {#if hasPermission('suppliers.index')}
                            <div class="kt-menu-item" class:active={isActiveRoute(route('web.admin.suppliers.index'))}>
                                <a class="kt-menu-link border border-transparent items-center grow kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]" 
                                    href="{route('web.admin.suppliers.index')}"
                                    use:inertia
                                    tabindex="0">

                                    <span class="kt-menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary">
                                    </span>
                                    
                                    <span class="kt-menu-title text-2sm font-normal text-foreground kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary">
                                        Suppliers
                                    </span>
                                </a>
                            </div>
                        {/if}

                        {#if hasPermission('products.index')}
                            <div class="kt-menu-item" class:active={isActiveRoute(route('web.admin.products.index'))}>
                                <a class="kt-menu-link border border-transparent items-center grow kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                    href="{route('web.admin.products.index')}"
                                    use:inertia
                                    tabindex="0">

                                    <span class="kt-menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary">
                                    </span>

                                    <span class="kt-menu-title text-2sm font-normal text-foreground kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary">
                                        Products
                                    </span>
                                </a>
                            </div>
                        {/if}

                        {#if hasPermission('production-sites.index')}
                            <div class="kt-menu-item" class:active={isActiveRoute(route('web.admin.production-sites.index'))}>
                                <a class="kt-menu-link border border-transparent items-center grow kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                    href="{route('web.admin.production-sites.index')}"
                                    use:inertia
                                    tabindex="0">

                                    <span class="kt-menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary">
                                    </span>

                                    <span class="kt-menu-title text-2sm font-normal text-foreground kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary">
                                        Production Sites
                                    </span>
                                </a>
                            </div>
                        {/if}

                        {#if hasPermission('production-lines.index')}
                            <div class="kt-menu-item" class:active={isActiveRoute(route('web.admin.production-lines.index'))}>
                                <a class="kt-menu-link border border-transparent items-center grow kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                    href="{route('web.admin.production-lines.index')}"
                                    use:inertia
                                    tabindex="0">

                                    <span class="kt-menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary">
                                    </span>

                                    <span class="kt-menu-title text-2sm font-normal text-foreground kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary">
                                        Production Lines
                                    </span>
                                </a>
                            </div>
                        {/if}
                    </div>
                </div>

                <div class="kt-menu-item"
                    data-kt-menu-item-toggle="accordion"
                    data-kt-menu-item-trigger="click"
                    class:show={isActiveRoute([
                        route('web.admin.import-quotas.index'),
                        route('web.admin.purchase-orders.index'),
                        route('web.admin.purchase-order-lines.index'),
                        route('web.admin.shippings.index'),
                    ])}>
                    <div class="kt-menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]" tabindex="0">
                        <span class="kt-menu-icon items-start text-muted-foreground w-[20px]">
                            <i class="fa-solid fa-cart-shopping text-lg">
                            </i>
                        </span>

                        <span class="kt-menu-title text-sm font-medium text-foreground kt-menu-item-active:text-primary kt-menu-link-hover:!text-primary">
                            Purchasing Management
                        </span>

                        <span class="kt-menu-arrow text-muted-foreground w-[20px] shrink-0 justify-end ms-1 me-[-10px]">
                            <span class="inline-flex kt-menu-item-show:hidden">
                                <i class="fa-solid fa-plus text-[11px]">
                                </i>
                            </span>

                            <span class="hidden kt-menu-item-show:inline-flex">
                                <i class="fa-solid fa-minus text-[11px]">
                                </i>
                            </span>
                        </span>
                    </div>

                    <div class="kt-menu-accordion gap-1 ps-[10px] relative before:absolute before:start-[20px] before:top-0 before:bottom-0 before:border-s before:border-border">
                        {#if hasPermission('import-quotas.index')}
                            <div class="kt-menu-item" class:active={isActiveRoute(route('web.admin.import-quotas.index'))}>
                                <a class="kt-menu-link border border-transparent items-center grow kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                    href="{route('web.admin.import-quotas.index')}"
                                    use:inertia
                                    tabindex="0">

                                    <span class="kt-menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary">
                                    </span>

                                    <span class="kt-menu-title text-2sm font-normal text-foreground kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary">
                                        Import Quotas
                                    </span>
                                </a>
                            </div>
                        {/if}

                        {#if hasPermission('purchase-orders.index')}
                            <div class="kt-menu-item" class:active={isActiveRoute(route('web.admin.purchase-orders.index'))}>
                                <a class="kt-menu-link border border-transparent items-center grow kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                    href="{route('web.admin.purchase-orders.index')}"
                                    use:inertia
                                    tabindex="0">

                                    <span class="kt-menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary">
                                    </span>

                                    <span class="kt-menu-title text-2sm font-normal text-foreground kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary">
                                        Purchase Orders
                                    </span>
                                </a>
                            </div>
                        {/if}

                        {#if hasPermission('purchase-order-lines.index')}
                            <div class="kt-menu-item" class:active={isActiveRoute(route('web.admin.purchase-order-lines.index'))}>
                                <a class="kt-menu-link border border-transparent items-center grow kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                    href="{route('web.admin.purchase-order-lines.index')}"
                                    use:inertia
                                    tabindex="0">

                                    <span class="kt-menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary">
                                    </span>

                                    <span class="kt-menu-title text-2sm font-normal text-foreground kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary">
                                        Purchase Order Lines
                                    </span>
                                </a>
                            </div>
                        {/if}

                        {#if hasPermission('shippings.index')}
                            <div class="kt-menu-item" class:active={isActiveRoute(route('web.admin.shippings.index'))}>
                                <a class="kt-menu-link border border-transparent items-center grow kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                    href="{route('web.admin.shippings.index')}"
                                    use:inertia
                                    tabindex="0">

                                    <span class="kt-menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary">
                                    </span>

                                    <span class="kt-menu-title text-2sm font-normal text-foreground kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary">
                                        Shippings
                                    </span>
                                </a>
                            </div>
                        {/if}
                    </div>
                </div>

                <div class="kt-menu-item"
                    data-kt-menu-item-toggle="accordion"
                    data-kt-menu-item-trigger="click"
                    class:show={isActiveRoute([
                        route('web.admin.wh-good-receipts.index'),
                        route('web.admin.qc-records.index'),
                        route('web.admin.qa-records.index'),
                        route('web.admin.qp-records.index'),
                        route('web.admin.inventory-records.index'),
                    ])}>
                    <div class="kt-menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]" tabindex="0">
                        <span class="kt-menu-icon items-start text-muted-foreground w-[20px]">
                            <i class="fa-solid fa-warehouse text-lg">
                            </i>
                        </span>

                        <span class="kt-menu-title text-sm font-medium text-foreground kt-menu-item-active:text-primary kt-menu-link-hover:!text-primary">
                            Warehouse Management
                        </span>

                        <span class="kt-menu-arrow text-muted-foreground w-[20px] shrink-0 justify-end ms-1 me-[-10px]">
                            <span class="inline-flex kt-menu-item-show:hidden">
                                <i class="fa-solid fa-plus text-[11px]">
                                </i>
                            </span>

                            <span class="hidden kt-menu-item-show:inline-flex">
                                <i class="fa-solid fa-minus text-[11px]">
                                </i>
                            </span>
                        </span>
                    </div>

                    <div class="kt-menu-accordion gap-1 ps-[10px] relative before:absolute before:start-[20px] before:top-0 before:bottom-0 before:border-s before:border-border">
                        {#if hasPermission('wh-good-receipts.index')}
                            <div class="kt-menu-item" class:active={isActiveRoute(route('web.admin.wh-good-receipts.index'))}>
                                <a class="kt-menu-link border border-transparent items-center grow kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                    href="{route('web.admin.wh-good-receipts.index')}"
                                    use:inertia
                                    tabindex="0">

                                    <span class="kt-menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary">
                                    </span>

                                    <span class="kt-menu-title text-2sm font-normal text-foreground kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary">
                                        WH Good Receipts
                                    </span>
                                </a>
                            </div>
                        {/if}

                        {#if hasPermission('qc-records.index')}
                            <div class="kt-menu-item" class:active={isActiveRoute(route('web.admin.qc-records.index'))}>
                                <a class="kt-menu-link border border-transparent items-center grow kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                    href="{route('web.admin.qc-records.index')}"
                                    use:inertia
                                    tabindex="0">

                                    <span class="kt-menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary">
                                    </span>

                                    <span class="kt-menu-title text-2sm font-normal text-foreground kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary">
                                        QC Records
                                    </span>
                                </a>
                            </div>
                        {/if}

                        {#if hasPermission('qa-records.index')}
                            <div class="kt-menu-item" class:active={isActiveRoute(route('web.admin.qa-records.index'))}>
                                <a class="kt-menu-link border border-transparent items-center grow kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                    href="{route('web.admin.qa-records.index')}"
                                    use:inertia
                                    tabindex="0">

                                    <span class="kt-menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary">
                                    </span>

                                    <span class="kt-menu-title text-2sm font-normal text-foreground kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary">
                                        QA Records
                                    </span>
                                </a>
                            </div>
                        {/if}

                        {#if hasPermission('qp-records.index')}
                            <div class="kt-menu-item" class:active={isActiveRoute(route('web.admin.qp-records.index'))}>
                                <a class="kt-menu-link border border-transparent items-center grow kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                    href="{route('web.admin.qp-records.index')}"
                                    use:inertia
                                    tabindex="0">

                                    <span class="kt-menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary">
                                    </span>

                                    <span class="kt-menu-title text-2sm font-normal text-foreground kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary">
                                        QP Records
                                    </span>
                                </a>
                            </div>
                        {/if}

                        {#if hasPermission('inventory-records.index')}
                            <div class="kt-menu-item" class:active={isActiveRoute(route('web.admin.inventory-records.index'))}>
                                <a class="kt-menu-link border border-transparent items-center grow kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                    href="{route('web.admin.inventory-records.index')}"
                                    use:inertia
                                    tabindex="0">

                                    <span class="kt-menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary">
                                    </span>

                                    <span class="kt-menu-title text-2sm font-normal text-foreground kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary">
                                        Inventory Records
                                    </span>
                                </a>
                            </div>
                        {/if}
                    </div>
                </div>

                <div class="kt-menu-item" 
                    data-kt-menu-item-toggle="accordion" 
                    data-kt-menu-item-trigger="click"
                    class:show={isActiveRoute([
                        route('web.admin.production-orders.index'),
                    ])}>
                    <div class="kt-menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]" tabindex="0">
                        <span class="kt-menu-icon items-start text-muted-foreground w-[20px]">
                            <i class="fa-solid fa-industry text-lg">
                            </i>
                        </span>
                        
                        <span class="kt-menu-title text-sm font-medium text-foreground kt-menu-item-active:text-primary kt-menu-link-hover:!text-primary">
                            Production Management
                        </span>

                        <span class="kt-menu-arrow text-muted-foreground w-[20px] shrink-0 justify-end ms-1 me-[-10px]">
                            <span class="inline-flex kt-menu-item-show:hidden">
                                <i class="fa-solid fa-plus text-[11px]">
                                </i>
                            </span>

                            <span class="hidden kt-menu-item-show:inline-flex">
                                <i class="fa-solid fa-minus text-[11px]">
                                </i>
                            </span>
                        </span>
                    </div>

                    <div class="kt-menu-accordion gap-1 ps-[10px] relative before:absolute before:start-[20px] before:top-0 before:bottom-0 before:border-s before:border-border">
                        {#if hasPermission('production-orders.index')}
                            <div class="kt-menu-item" class:active={isActiveRoute(route('web.admin.production-orders.index'))}>
                                <a class="kt-menu-link border border-transparent items-center grow kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                    href="{route('web.admin.production-orders.index')}"
                                    use:inertia
                                    tabindex="0">

                                    <span class="kt-menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary">
                                    </span>

                                    <span class="kt-menu-title text-2sm font-normal text-foreground kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary">
                                        Production Orders
                                    </span>
                                </a>
                            </div>
                        {/if}
                    </div>
                </div>

                <div class="kt-menu-item" 
                    data-kt-menu-item-toggle="accordion" 
                    data-kt-menu-item-trigger="click"
                    class:show={isActiveRoute([
                        route('web.admin.line-daily-shift-overrides.index'),
                    ])}>
                    <div class="kt-menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]" tabindex="0">
                        <span class="kt-menu-icon items-start text-muted-foreground w-[20px]">
                            <i class="fa-solid fa-calendar-days text-lg">
                            </i>
                        </span>
                        
                        <span class="kt-menu-title text-sm font-medium text-foreground kt-menu-item-active:text-primary kt-menu-link-hover:!text-primary">
                            Planning Management
                        </span>

                        <span class="kt-menu-arrow text-muted-foreground w-[20px] shrink-0 justify-end ms-1 me-[-10px]">
                            <span class="inline-flex kt-menu-item-show:hidden">
                                <i class="fa-solid fa-plus text-[11px]">
                                </i>
                            </span>

                            <span class="hidden kt-menu-item-show:inline-flex">
                                <i class="fa-solid fa-minus text-[11px]">
                                </i>
                            </span>
                        </span>
                    </div>

                    <div class="kt-menu-accordion gap-1 ps-[10px] relative before:absolute before:start-[20px] before:top-0 before:bottom-0 before:border-s before:border-border">
                        {#if hasPermission('line-daily-shift-overrides.index')}
                            <div class="kt-menu-item" class:active={isActiveRoute(route('web.admin.line-daily-shift-overrides.index'))}>
                                <a class="kt-menu-link border border-transparent items-center grow kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]"
                                    href="{route('web.admin.line-daily-shift-overrides.index')}"
                                    use:inertia
                                    tabindex="0">

                                    <span class="kt-menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary">
                                    </span>

                                    <span class="kt-menu-title text-2sm font-normal text-foreground kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary">
                                        Daily Shift Overrides
                                    </span>
                                </a>
                            </div>
                        {/if}
                    </div>
                </div>

                <div class="kt-menu-item" 
                    data-kt-menu-item-toggle="accordion" 
                    data-kt-menu-item-trigger="click"
                    class:show={isActiveRoute([
                        route('web.admin.users.index'),
                        route('web.admin.api-keys.index'),
                        route('web.admin.roles.index'),
                        route('web.admin.permissions.index'),
                    ])}>
                    <div class="kt-menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]" tabindex="0">
                        <span class="kt-menu-icon items-start text-muted-foreground w-[20px]">
                            <i class="fa-solid fa-users text-lg">
                            </i>
                        </span>
                        
                        <span class="kt-menu-title text-sm font-medium text-foreground kt-menu-item-active:text-primary kt-menu-link-hover:!text-primary">
                            Users Management
                        </span>

                        <span class="kt-menu-arrow text-muted-foreground w-[20px] shrink-0 justify-end ms-1 me-[-10px]">
                            <span class="inline-flex kt-menu-item-show:hidden">
                                <i class="fa-solid fa-plus text-[11px]">
                                </i>
                            </span>

                            <span class="hidden kt-menu-item-show:inline-flex">
                                <i class="fa-solid fa-minus text-[11px]">
                                </i>
                            </span>
                        </span>
                    </div>

                    <div class="kt-menu-accordion gap-1 ps-[10px] relative before:absolute before:start-[20px] before:top-0 before:bottom-0 before:border-s before:border-border">
                        {#if hasPermission('users.index')}
                            <div class="kt-menu-item" class:active={isActiveRoute(route('web.admin.users.index'))}>
                                <a class="kt-menu-link border border-transparent items-center grow kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]" 
                                    href="{route('web.admin.users.index')}"
                                    use:inertia
                                    tabindex="0">

                                    <span class="kt-menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary">
                                    </span>
                                    
                                    <span class="kt-menu-title text-2sm font-normal text-foreground kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary">
                                        Users
                                    </span>
                                </a>
                            </div>
                        {/if}

                        {#if hasPermission('api-keys.index')}
                        <div class="kt-menu-item" class:active={isActiveRoute(route('web.admin.api-keys.index'))}>
                            <a class="kt-menu-link border border-transparent items-center grow kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]" 
                                href="{route('web.admin.api-keys.index')}"
                                use:inertia
                                tabindex="0">

                                <span class="kt-menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary">
                                </span>
                                
                                <span class="kt-menu-title text-2sm font-normal text-foreground kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary">
                                    API Keys
                                </span>
                            </a>
                        </div>
                        {/if}

                        {#if hasPermission('roles.index')}
                            <div class="kt-menu-item" class:active={isActiveRoute(route('web.admin.roles.index'))}>
                                <a class="kt-menu-link border border-transparent items-center grow kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]" 
                                    href="{route('web.admin.roles.index')}"
                                    use:inertia
                                    tabindex="0">

                                    <span class="kt-menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary">
                                    </span>
                                    
                                    <span class="kt-menu-title text-2sm font-normal text-foreground kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary">
                                        Roles
                                    </span>
                                </a>
                            </div>
                        {/if}

                        {#if hasPermission('permissions.index')}
                            <div class="kt-menu-item" class:active={isActiveRoute(route('web.admin.permissions.index'))}>
                                <a class="kt-menu-link border border-transparent items-center grow kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]" 
                                    href="{route('web.admin.permissions.index')}"
                                    use:inertia
                                    tabindex="0">

                                    <span class="kt-menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary">
                                    </span>
                                    
                                    <span class="kt-menu-title text-2sm font-normal text-foreground kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary">
                                        Permissions
                                    </span>
                                </a>
                            </div>
                        {/if}
                    </div>
                </div>

                <div class="kt-menu-item" 
                    data-kt-menu-item-toggle="accordion" 
                    data-kt-menu-item-trigger="click"
                    class:show={isActiveRoute([
                        route('web.admin.settings.index'),
                    ])}>
                    <div class="kt-menu-link flex items-center grow cursor-pointer border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px]" tabindex="0">
                        <span class="kt-menu-icon items-start text-muted-foreground w-[20px]">
                            <i class="fa-solid fa-cog text-lg">
                            </i>
                        </span>
                        
                        <span class="kt-menu-title text-sm font-medium text-foreground kt-menu-item-active:text-primary kt-menu-link-hover:!text-primary">
                            Settings Management
                        </span>

                        <span class="kt-menu-arrow text-muted-foreground w-[20px] shrink-0 justify-end ms-1 me-[-10px]">
                            <span class="inline-flex kt-menu-item-show:hidden">
                                <i class="fa-solid fa-plus text-[11px]">
                                </i>
                            </span>

                            <span class="hidden kt-menu-item-show:inline-flex">
                                <i class="fa-solid fa-minus text-[11px]">
                                </i>
                            </span>
                        </span>
                    </div>

                    <div class="kt-menu-accordion gap-1 ps-[10px] relative before:absolute before:start-[20px] before:top-0 before:bottom-0 before:border-s before:border-border">
                        {#if hasPermission('settings.index')}
                            <div class="kt-menu-item" class:active={isActiveRoute(route('web.admin.settings.index'))}>
                                <a class="kt-menu-link border border-transparent items-center grow kt-menu-item-active:bg-accent/60 dark:menu-item-active:border-border kt-menu-item-active:rounded-lg hover:bg-accent/60 hover:rounded-lg gap-[14px] ps-[10px] pe-[10px] py-[8px]" 
                                    href="{route('web.admin.settings.index')}"
                                    use:inertia
                                    tabindex="0">

                                    <span class="kt-menu-bullet flex w-[6px] -start-[3px] rtl:start-0 relative before:absolute before:top-0 before:size-[6px] before:rounded-full rtl:before:translate-x-1/2 before:-translate-y-1/2 kt-menu-item-active:before:bg-primary kt-menu-item-hover:before:bg-primary">
                                    </span>
                                    
                                    <span class="kt-menu-title text-2sm font-normal text-foreground kt-menu-item-active:text-primary kt-menu-item-active:font-semibold kt-menu-link-hover:!text-primary">
                                        Settings
                                    </span>
                                </a>
                            </div>
                        {/if}
                    </div>
                </div>
            </div>
            <!-- End of Sidebar Menu -->
        </div>
    </div>
</div>
