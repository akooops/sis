<script>
    import { page } from '@inertiajs/svelte'
    import Notifications from './Notifications.svelte';
    
    function handleLogout() {
        // Create a form element to submit the logout request
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = route('admin.auth.logout');
        
        // Add CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (csrfToken) {
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = csrfToken;
            form.appendChild(csrfInput);
        }
        
        // Submit the form
        document.body.appendChild(form);
        form.submit();
    }

    // Helper function to check if user has permission
    function hasPermission(permission) {
        if (!$page.props.auth.permissions) return false;
        return $page.props.auth.permissions.some(p => p === permission);
    }

    // Helper function to check if current route matches
    function isActiveRoute(routePattern) {
        return $page.url.startsWith(route(routePattern));
    }
</script>

<!-- Sidebar -->
<div class="flex-col fixed top-0 bottom-0 z-20 hidden lg:flex items-stretch shrink-0 w-(--sidebar-width) dark [--kt-drawer-enable:true] lg:[--kt-drawer-enable:false]" data-kt-drawer="true" data-kt-drawer-class="kt-drawer kt-drawer-start flex top-0 bottom-0" id="sidebar">
    <!-- Sidebar Header -->
    <div class="flex flex-col gap-2.5" id="sidebar_header">
        <div class="flex items-center gap-2.5 px-3.5 pt-3.5 h-[90px]">
            <a href={route('admin.dashboard')}>
                <img class="h-[60px]" src="/assets/admin/images/logo.png"/>
            </a>
            <div class="kt-menu kt-menu-default grow" data-kt-menu="true">
                <div class="kt-menu-item grow">
                    <div class="kt-menu-label cursor-pointer text-mono font-medium grow justify-between">
                        <span class="text-lg font-medium text-inverse grow">
                            SIS
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End of Sidebar Header -->

    <!-- Sidebar menu -->
    <div class="flex items-stretch grow shrink-0 justify-center my-5" id="sidebar_menu">
        <div class="kt-scrollable-y-auto grow" data-kt-scrollable="true" data-kt-scrollable-dependencies="#sidebar_header, #sidebar_footer" data-kt-scrollable-height="auto" data-kt-scrollable-offset="0px" data-kt-scrollable-wrappers="#sidebar_menu">
            <!-- Dashboard -->
            <div class="mb-5">
                <h3 class="text-sm text-muted-foreground uppercase ps-5 inline-block mb-3">
                    Dashboard
                </h3>
                <div class="kt-menu flex flex-col w-full gap-1.5 px-3.5" data-kt-menu="true" data-kt-menu-accordion-expand-all="false" id="sidebar_primary_menu">
                    {#if hasPermission('admin.dashboard.index')}
                    <div class="kt-menu-item">
                        <a class="kt-menu-link gap-2.5 py-2 px-2.5 rounded-md {isActiveRoute('admin.dashboard') ? 'kt-menu-item-active:bg-accent/60' : ''} kt-menu-link-hover:bg-accent/60" href={route('admin.dashboard')}>
                            <span class="kt-menu-icon items-start text-lg text-secondary-foreground kt-menu-item-active:text-mono kt-menu-item-here:text-mono">
                                <i class="ki-outline ki-home-3"></i>
                            </span>
                            <span class="kt-menu-title text-sm text-foreground font-medium kt-menu-item-here:text-mono kt-menu-item-active:text-mono kt-menu-link-hover:text-mono">
                                Dashboard
                            </span>
                        </a>
                    </div>
                    {/if}
                </div>
            </div>

            <!-- Content Management -->
            <div class="mb-5">
                <h3 class="text-sm text-muted-foreground uppercase ps-5 inline-block mb-3">
                    Content Management
                </h3>
                <div class="kt-menu flex flex-col w-full gap-1.5 px-3.5" data-kt-menu="true" data-kt-menu-accordion-expand-all="false" id="sidebar_primary_menu">
                    {#if hasPermission('admin.pages.index')}
                    <div class="kt-menu-item">
                        <a class="kt-menu-link gap-2.5 py-2 px-2.5 rounded-md {isActiveRoute('admin.pages.index') ? 'kt-menu-item-active:bg-accent/60' : ''} kt-menu-link-hover:bg-accent/60" href={route('admin.pages.index')}>
                            <span class="kt-menu-icon items-start text-lg text-secondary-foreground kt-menu-item-active:text-mono kt-menu-item-here:text-mono">
                                <i class="ki-outline ki-files"></i>
                            </span>
                            <span class="kt-menu-title text-sm text-foreground font-medium kt-menu-item-here:text-mono kt-menu-item-active:text-mono kt-menu-link-hover:text-mono">
                                Pages
                            </span>
                        </a>
                    </div>
                    {/if}

                    {#if hasPermission('admin.albums.index')}
                    <div class="kt-menu-item">
                        <a class="kt-menu-link gap-2.5 py-2 px-2.5 rounded-md {isActiveRoute('admin.albums.index') ? 'kt-menu-item-active:bg-accent/60' : ''} kt-menu-link-hover:bg-accent/60" href={route('admin.albums.index')}>
                            <span class="kt-menu-icon items-start text-lg text-secondary-foreground kt-menu-item-active:text-mono kt-menu-item-here:text-mono">
                                <i class="ki-outline ki-picture"></i>
                            </span>
                            <span class="kt-menu-title text-sm text-foreground font-medium kt-menu-item-here:text-mono kt-menu-item-active:text-mono kt-menu-link-hover:text-mono">
                                Albums
                            </span>
                        </a>
                    </div>
                    {/if}

                    {#if hasPermission('admin.articles.index')}
                    <div class="kt-menu-item">
                        <a class="kt-menu-link gap-2.5 py-2 px-2.5 rounded-md {isActiveRoute('admin.articles.index') ? 'kt-menu-item-active:bg-accent/60' : ''} kt-menu-link-hover:bg-accent/60" href={route('admin.articles.index')}>
                            <span class="kt-menu-icon items-start text-lg text-secondary-foreground kt-menu-item-active:text-mono kt-menu-item-here:text-mono">
                                <i class="ki-outline ki-document"></i>
                            </span>
                            <span class="kt-menu-title text-sm text-foreground font-medium kt-menu-item-here:text-mono kt-menu-item-active:text-mono kt-menu-link-hover:text-mono">
                                Articles
                            </span>
                        </a>
                    </div>
                    {/if}

                    {#if hasPermission('admin.events.index')}
                    <div class="kt-menu-item">
                        <a class="kt-menu-link gap-2.5 py-2 px-2.5 rounded-md {isActiveRoute('admin.events.index') ? 'kt-menu-item-active:bg-accent/60' : ''} kt-menu-link-hover:bg-accent/60" href={route('admin.events.index')}>
                            <span class="kt-menu-icon items-start text-lg text-secondary-foreground kt-menu-item-active:text-mono kt-menu-item-here:text-mono">
                                <i class="ki-outline ki-calendar-8"></i>
                            </span>
                            <span class="kt-menu-title text-sm text-foreground font-medium kt-menu-item-here:text-mono kt-menu-item-active:text-mono kt-menu-link-hover:text-mono">
                                Events
                            </span>
                        </a>
                    </div>
                    {/if}

                    {#if hasPermission('admin.banners.index')}
                    <div class="kt-menu-item">
                        <a class="kt-menu-link gap-2.5 py-2 px-2.5 rounded-md {isActiveRoute('admin.banners.index') ? 'kt-menu-item-active:bg-accent/60' : ''} kt-menu-link-hover:bg-accent/60" href={route('admin.banners.index')}>
                            <span class="kt-menu-icon items-start text-lg text-secondary-foreground kt-menu-item-active:text-mono kt-menu-item-here:text-mono">
                                <i class="ki-outline ki-picture"></i>
                            </span>
                            <span class="kt-menu-title text-sm text-foreground font-medium kt-menu-item-here:text-mono kt-menu-item-active:text-mono kt-menu-link-hover:text-mono">
                                Banners
                            </span>
                        </a>
                    </div>
                    {/if}

                    {#if hasPermission('admin.media.index')}
                    <div class="kt-menu-item">
                        <a class="kt-menu-link gap-2.5 py-2 px-2.5 rounded-md {isActiveRoute('admin.media.index') ? 'kt-menu-item-active:bg-accent/60' : ''} kt-menu-link-hover:bg-accent/60" href={route('admin.media.index')}>
                            <span class="kt-menu-icon items-start text-lg text-secondary-foreground kt-menu-item-active:text-mono kt-menu-item-here:text-mono">
                                <i class="ki-outline ki-folder"></i>
                            </span>
                            <span class="kt-menu-title text-sm text-foreground font-medium kt-menu-item-here:text-mono kt-menu-item-active:text-mono kt-menu-link-hover:text-mono">
                                Media
                            </span>
                        </a>
                    </div>
                    {/if}

                    {#if hasPermission('admin.documents.index')}
                    <div class="kt-menu-item">
                        <a class="kt-menu-link gap-2.5 py-2 px-2.5 rounded-md {isActiveRoute('admin.documents.index') ? 'kt-menu-item-active:bg-accent/60' : ''} kt-menu-link-hover:bg-accent/60" href={route('admin.documents.index')}>
                            <span class="kt-menu-icon items-start text-lg text-secondary-foreground kt-menu-item-active:text-mono kt-menu-item-here:text-mono">
                                <i class="ki-outline ki-document"></i>
                            </span>
                            <span class="kt-menu-title text-sm text-foreground font-medium kt-menu-item-here:text-mono kt-menu-item-active:text-mono kt-menu-link-hover:text-mono">
                                Documents
                            </span>
                        </a>
                    </div>
                    {/if}
                </div>
            </div>

            <!-- Jobs Management -->
            <div class="mb-5">
                <h3 class="text-sm text-muted-foreground uppercase ps-5 inline-block mb-3">
                    Jobs Management
                </h3>
                <div class="kt-menu flex flex-col w-full gap-1.5 px-3.5" data-kt-menu="true" data-kt-menu-accordion-expand-all="false" id="sidebar_primary_menu">
                    {#if hasPermission('admin.job-postings.index')}
                    <div class="kt-menu-item">
                        <a class="kt-menu-link gap-2.5 py-2 px-2.5 rounded-md {isActiveRoute('admin.job-postings.index') ? 'kt-menu-item-active:bg-accent/60' : ''} kt-menu-link-hover:bg-accent/60" href={route('admin.job-postings.index')}>
                            <span class="kt-menu-icon items-start text-lg text-secondary-foreground kt-menu-item-active:text-mono kt-menu-item-here:text-mono">
                                <i class="ki-outline ki-briefcase"></i>
                            </span>
                            <span class="kt-menu-title text-sm text-foreground font-medium kt-menu-item-here:text-mono kt-menu-item-active:text-mono kt-menu-link-hover:text-mono">
                                Job Postings
                            </span>
                        </a>
                    </div>
                    {/if}
                </div>
            </div>

            <!-- Academic Management -->
            <div class="mb-5">
                <h3 class="text-sm text-muted-foreground uppercase ps-5 inline-block mb-3">
                    Academic Management
                </h3>
                <div class="kt-menu flex flex-col w-full gap-1.5 px-3.5" data-kt-menu="true" data-kt-menu-accordion-expand-all="false" id="sidebar_primary_menu">
                    {#if hasPermission('admin.programs.index')}
                    <div class="kt-menu-item">
                        <a class="kt-menu-link gap-2.5 py-2 px-2.5 rounded-md {isActiveRoute('admin.programs.index') ? 'kt-menu-item-active:bg-accent/60' : ''} kt-menu-link-hover:bg-accent/60" href={route('admin.programs.index')}>
                            <span class="kt-menu-icon items-start text-lg text-secondary-foreground kt-menu-item-active:text-mono kt-menu-item-here:text-mono">
                                <i class="ki-outline ki-book"></i>
                            </span>
                            <span class="kt-menu-title text-sm text-foreground font-medium kt-menu-item-here:text-mono kt-menu-item-active:text-mono kt-menu-link-hover:text-mono">
                                Programs
                            </span>
                        </a>
                    </div>
                    {/if}

                    {#if hasPermission('admin.grades.index')}
                    <div class="kt-menu-item">
                        <a class="kt-menu-link gap-2.5 py-2 px-2.5 rounded-md {isActiveRoute('admin.grades.index') ? 'kt-menu-item-active:bg-accent/60' : ''} kt-menu-link-hover:bg-accent/60" href={route('admin.grades.index')}>
                            <span class="kt-menu-icon items-start text-lg text-secondary-foreground kt-menu-item-active:text-mono kt-menu-item-here:text-mono">
                                <i class="ki-outline ki-abstract-26"></i>
                            </span>
                            <span class="kt-menu-title text-sm text-foreground font-medium kt-menu-item-here:text-mono kt-menu-item-active:text-mono kt-menu-link-hover:text-mono">
                                Grades
                            </span>
                        </a>
                    </div>
                    {/if}
                </div>
            </div>

            <!-- Navigation Management -->
            <div class="mb-5">
                <h3 class="text-sm text-muted-foreground uppercase ps-5 inline-block mb-3">
                    Navigation
                </h3>
                <div class="kt-menu flex flex-col w-full gap-1.5 px-3.5" data-kt-menu="true" data-kt-menu-accordion-expand-all="false" id="sidebar_primary_menu">
                    {#if hasPermission('admin.menus.index')}
                    <div class="kt-menu-item">
                        <a class="kt-menu-link gap-2.5 py-2 px-2.5 rounded-md {isActiveRoute('admin.menus.index') ? 'kt-menu-item-active:bg-accent/60' : ''} kt-menu-link-hover:bg-accent/60" href={route('admin.menus.index')}>
                            <span class="kt-menu-icon items-start text-lg text-secondary-foreground kt-menu-item-active:text-mono kt-menu-item-here:text-mono">
                                <i class="ki-outline ki-menu"></i>
                            </span>
                            <span class="kt-menu-title text-sm text-foreground font-medium kt-menu-item-here:text-mono kt-menu-item-active:text-mono kt-menu-link-hover:text-mono">
                                Menus
                            </span>
                        </a>
                    </div>
                    {/if}
                </div>
            </div>

            <!-- Localization -->
            <div class="mb-5">
                <h3 class="text-sm text-muted-foreground uppercase ps-5 inline-block mb-3">
                    Localization
                </h3>
                <div class="kt-menu flex flex-col w-full gap-1.5 px-3.5" data-kt-menu="true" data-kt-menu-accordion-expand-all="false" id="sidebar_primary_menu">
                    {#if hasPermission('admin.languages.index')}
                    <div class="kt-menu-item">
                        <a class="kt-menu-link gap-2.5 py-2 px-2.5 rounded-md {isActiveRoute('admin.languages.index') ? 'kt-menu-item-active:bg-accent/60' : ''} kt-menu-link-hover:bg-accent/60" href={route('admin.languages.index')}>
                            <span class="kt-menu-icon items-start text-lg text-secondary-foreground kt-menu-item-active:text-mono kt-menu-item-here:text-mono">
                                <i class="ki-outline ki-geolocation"></i>
                            </span>
                            <span class="kt-menu-title text-sm text-foreground font-medium kt-menu-item-here:text-mono kt-menu-item-active:text-mono kt-menu-link-hover:text-mono">
                                Languages
                            </span>
                        </a>
                    </div>
                    {/if}

                    {#if hasPermission('admin.language-keys.index')}
                    <div class="kt-menu-item">
                        <a class="kt-menu-link gap-2.5 py-2 px-2.5 rounded-md {isActiveRoute('admin.language-keys.index') ? 'kt-menu-item-active:bg-accent/60' : ''} kt-menu-link-hover:bg-accent/60" href={route('admin.language-keys.index')}>
                            <span class="kt-menu-icon items-start text-lg text-secondary-foreground kt-menu-item-active:text-mono kt-menu-item-here:text-mono">
                                <i class="ki-outline ki-key"></i>
                            </span>
                            <span class="kt-menu-title text-sm text-foreground font-medium kt-menu-item-here:text-mono kt-menu-item-active:text-mono kt-menu-link-hover:text-mono">
                                Language Keys
                            </span>
                        </a>
                    </div>
                    {/if}
                </div>
            </div>

            <!-- Visits & Communications -->
            <div class="mb-5">
                <h3 class="text-sm text-muted-foreground uppercase ps-5 inline-block mb-3">
                    Visits & Communications
                </h3>
                <div class="kt-menu flex flex-col w-full gap-1.5 px-3.5" data-kt-menu="true" data-kt-menu-accordion-expand-all="false" id="sidebar_primary_menu">
                    {#if hasPermission('admin.visit-services.index')}
                    <div class="kt-menu-item">
                        <a class="kt-menu-link gap-2.5 py-2 px-2.5 rounded-md {isActiveRoute('admin.visit-services.index') ? 'kt-menu-item-active:bg-accent/60' : ''} kt-menu-link-hover:bg-accent/60" href={route('admin.visit-services.index')}>
                            <span class="kt-menu-icon items-start text-lg text-secondary-foreground kt-menu-item-active:text-mono kt-menu-item-here:text-mono">
                                <i class="ki-outline ki-bookmark"></i>
                            </span>
                            <span class="kt-menu-title text-sm text-foreground font-medium kt-menu-item-here:text-mono kt-menu-item-active:text-mono kt-menu-link-hover:text-mono">
                                Visit Services
                            </span>
                        </a>
                    </div>
                    {/if}

                    {#if hasPermission('admin.inquiries.index')}
                    <div class="kt-menu-item">
                        <a class="kt-menu-link gap-2.5 py-2 px-2.5 rounded-md {isActiveRoute('admin.inquiries.index') ? 'kt-menu-item-active:bg-accent/60' : ''} kt-menu-link-hover:bg-accent/60" href={route('admin.inquiries.index')}>
                            <span class="kt-menu-icon items-start text-lg text-secondary-foreground kt-menu-item-active:text-mono kt-menu-item-here:text-mono">
                                <i class="ki-outline ki-question"></i>
                            </span>
                            <span class="kt-menu-title text-sm text-foreground font-medium kt-menu-item-here:text-mono kt-menu-item-active:text-mono kt-menu-link-hover:text-mono">
                                Student Inquiries
                            </span>
                        </a>
                    </div>
                    {/if}

                    {#if hasPermission('admin.contact-submissions.index')}
                    <div class="kt-menu-item">
                        <a class="kt-menu-link gap-2.5 py-2 px-2.5 rounded-md {isActiveRoute('admin.contact-submissions.index') ? 'kt-menu-item-active:bg-accent/60' : ''} kt-menu-link-hover:bg-accent/60" href={route('admin.contact-submissions.index')}>
                            <span class="kt-menu-icon items-start text-lg text-secondary-foreground kt-menu-item-active:text-mono kt-menu-item-here:text-mono">
                                <i class="ki-outline ki-sms"></i>
                            </span>
                            <span class="kt-menu-title text-sm text-foreground font-medium kt-menu-item-here:text-mono kt-menu-item-active:text-mono kt-menu-link-hover:text-mono">
                                Contact Messages
                            </span>
                        </a>
                    </div>
                    {/if}
                </div>
            </div>

            <!-- User Management -->
            <div class="mb-5">
                <h3 class="text-sm text-muted-foreground uppercase ps-5 inline-block mb-3">
                    User Management
                </h3>
                <div class="kt-menu flex flex-col w-full gap-1.5 px-3.5" data-kt-menu="true" data-kt-menu-accordion-expand-all="false" id="sidebar_primary_menu">
                    {#if hasPermission('admin.users.index')}
                    <div class="kt-menu-item">
                        <a class="kt-menu-link gap-2.5 py-2 px-2.5 rounded-md {isActiveRoute('admin.users.index') ? 'kt-menu-item-active:bg-accent/60' : ''} kt-menu-link-hover:bg-accent/60" href={route('admin.users.index')}>
                            <span class="kt-menu-icon items-start text-lg text-secondary-foreground kt-menu-item-active:text-mono kt-menu-item-here:text-mono">
                                <i class="ki-outline ki-users"></i>
                            </span>
                            <span class="kt-menu-title text-sm text-foreground font-medium kt-menu-item-here:text-mono kt-menu-item-active:text-mono kt-menu-link-hover:text-mono">
                                Users
                            </span>
                        </a>
                    </div>
                    {/if}

                    {#if hasPermission('admin.roles.index')}
                    <div class="kt-menu-item">
                        <a class="kt-menu-link gap-2.5 py-2 px-2.5 rounded-md {isActiveRoute('admin.roles.index') ? 'kt-menu-item-active:bg-accent/60' : ''} kt-menu-link-hover:bg-accent/60" href={route('admin.roles.index')}>
                            <span class="kt-menu-icon items-start text-lg text-secondary-foreground kt-menu-item-active:text-mono kt-menu-item-here:text-mono">
                                <i class="ki-outline ki-shield-tick"></i>
                            </span>
                            <span class="kt-menu-title text-sm text-foreground font-medium kt-menu-item-here:text-mono kt-menu-item-active:text-mono kt-menu-link-hover:text-mono">
                                Roles
                            </span>
                        </a>
                    </div>
                    {/if}

                    {#if hasPermission('admin.permissions.index')}
                    <div class="kt-menu-item">
                        <a class="kt-menu-link gap-2.5 py-2 px-2.5 rounded-md {isActiveRoute('admin.permissions.index') ? 'kt-menu-item-active:bg-accent/60' : ''} kt-menu-link-hover:bg-accent/60" href={route('admin.permissions.index')}>
                            <span class="kt-menu-icon items-start text-lg text-secondary-foreground kt-menu-item-active:text-mono kt-menu-item-here:text-mono">
                                <i class="ki-outline ki-lock"></i>
                            </span>
                            <span class="kt-menu-title text-sm text-foreground font-medium kt-menu-item-here:text-mono kt-menu-item-active:text-mono kt-menu-link-hover:text-mono">
                                Permissions
                            </span>
                        </a>
                    </div>
                    {/if}
                </div>
            </div>

            <!-- System Settings -->
            <div class="mb-5">
                <h3 class="text-sm text-muted-foreground uppercase ps-5 inline-block mb-3">
                    System
                </h3>
                <div class="kt-menu flex flex-col w-full gap-1.5 px-3.5" data-kt-menu="true" data-kt-menu-accordion-expand-all="false" id="sidebar_primary_menu">
                    {#if hasPermission('admin.settings.index')}
                    <div class="kt-menu-item">
                        <a class="kt-menu-link gap-2.5 py-2 px-2.5 rounded-md {isActiveRoute('admin.settings.index') ? 'kt-menu-item-active:bg-accent/60' : ''} kt-menu-link-hover:bg-accent/60" href={route('admin.settings.index')}>
                            <span class="kt-menu-icon items-start text-lg text-secondary-foreground kt-menu-item-active:text-mono kt-menu-item-here:text-mono">
                                <i class="ki-outline ki-setting-2"></i>
                            </span>
                            <span class="kt-menu-title text-sm text-foreground font-medium kt-menu-item-here:text-mono kt-menu-item-active:text-mono kt-menu-link-hover:text-mono">
                                Settings
                            </span>
                        </a>
                    </div>
                    {/if}
                </div>
            </div>
        </div>
    </div>
    <!-- End of Sidebar kt-menu-->
    <!-- Footer -->
    <div class="flex flex-center justify-between shrink-0 ps-4 pe-3.5 mb-3.5" id="sidebar_footer">
        <!-- User -->
        <div data-kt-dropdown="true" data-kt-dropdown-offset="10px, 10px" data-kt-dropdown-offset-rtl="-20px, 10px" data-kt-dropdown-placement="bottom-start" data-kt-dropdown-placement-rtl="bottom-end" data-kt-dropdown-trigger="click">
            <div class="cursor-pointer shrink-0" data-kt-dropdown-toggle="true">
                <img alt="" class="size-9 rounded-full border-2 border-mono/25 shrink-0 cursor-pointer" src="{$page.props.auth.user.avatarUrl}"/>
            </div>
            <div class="kt-dropdown-menu w-[250px]" data-kt-dropdown-menu="true">
                <div class="flex items-center justify-between px-2.5 py-1.5 gap-1.5">
                    <div class="flex items-center gap-2">
                        <img alt="" class="size-9 shrink-0 rounded-full border-2 border-green-500" src="{$page.props.auth.user.avatarUrl}"/>
                        <div class="flex flex-col gap-1.5">
                            <span class="text-sm text-foreground font-semibold leading-none">
                                {$page.props.auth.user.fullname}
                            </span>
                            <a class="text-xs text-secondary-foreground hover:text-primary font-medium leading-none" href="html/demo10/account/home/get-started.html">
                                {$page.props.auth.user.email}
                            </a>
                        </div>
                    </div>
                </div>
                <ul class="kt-dropdown-menu-sub">
                    <li>
                        <div class="kt-dropdown-menu-separator">
                        </div>
                    </li>
                </ul>
                <div class="px-2.5 pt-1.5 mb-2.5 flex flex-col gap-3.5">
                    <div class="flex items-center gap-2 justify-between">
                        <span class="flex items-center gap-2">
                            <i class="ki-filled ki-moon text-base text-muted-foreground"></i>
                            <span class="font-medium text-2sm">
                                Dark Mode
                            </span>
                        </span>
                        
                        <input class="kt-switch" data-kt-theme-switch-state="dark" data-kt-theme-switch-toggle="true" name="check" type="checkbox" value="1"/>
                    </div>
                    
                    <a class="kt-btn kt-btn-outline justify-center w-full" on:click={handleLogout}>
                        Log out
                    </a>
                </div>
            </div>
        </div>
        <!-- End of User -->
        <div class="flex items-center gap-1.5">
            <Notifications />
        </div>
    </div>
    <!-- End of Footer -->
</div>
<!-- End of Sidebar -->