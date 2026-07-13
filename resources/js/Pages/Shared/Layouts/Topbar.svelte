<script>
    import { inertia } from '@inertiajs/svelte';
    import Breadcrumbs from '../Utils/Breadcrumbs.svelte';
    import Notifications from '../Utils/Notifications.svelte';
    
    // Props
    export let breadcrumbs = [];
    export let pageTitle = 'Dashboard';
    
    // Loading state for logout
    let loggingOut = false;
    
    async function handleLogout() {
        loggingOut = true;

        try {
            const response = await fetch(route("api.v1.auth.logout"), {
                method: "POST",
                credentials: "same-origin",
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    "X-CSRF-TOKEN": document
                        .querySelector('meta[name="csrf-token"]')
                        .getAttribute("content"),
                },
            });

            const data = await response.json();

            if (response.ok) {
                window.location.replace(data.data.logout_url);
            }
        } catch (error) {
            console.error("Network error:", error);
        } finally {
            loggingOut = false;
        }
    }
</script>

<!-- Header -->
<header class="kt-header fixed top-0 z-10 start-0 end-0 flex items-stretch shrink-0 bg-background border-b border-border" data-kt-sticky="true" data-kt-sticky-class="border-b border-border" data-kt-sticky-name="header" id="header">
  <!-- Container -->
  <div class="kt-container-fixed flex justify-between items-stretch lg:gap-4" id="headerContainer">
    {#if getAuthUser().type == 'admin'}
      <!-- Mobile Logo -->
      <div class="flex gap-2.5 lg:hidden items-center -ms-1">
          <a class="shrink-0" href="{route('web.admin.dashboard.index')}">
          <img class="max-h-[25px] w-full" src="/assets/media/app/mini-logo.svg"/>
          </a>
          <div class="flex items-center">
              <button class="kt-btn kt-btn-icon kt-btn-ghost" data-kt-drawer-toggle="#sidebar">
                <i class="ki-filled ki-menu">
                </i>
              </button>
              <button class="kt-btn kt-btn-icon kt-btn-ghost" data-kt-drawer-toggle="#mega_menu_wrapper">
                <i class="ki-filled ki-burger-menu-2">
                </i>
              </button>
          </div>
      </div>
      <!-- End of Mobile Logo -->
    {:else if getAuthUser().type == 'guardian'}
      <!-- Mobile Logo -->
        <div class="flex gap-2.5 lg:hidden items-center -ms-1">
            <a class="shrink-0" href="{route('web.guardian.dashboard.index')}">
            <img class="max-h-[25px] w-full" src="/assets/media/app/mini-logo.svg"/>
            </a>
            <div class="flex items-center">
                <button class="kt-btn kt-btn-icon kt-btn-ghost" data-kt-drawer-toggle="#sidebar">
                <i class="ki-filled ki-menu">
                </i>
                </button>
                <button class="kt-btn kt-btn-icon kt-btn-ghost" data-kt-drawer-toggle="#mega_menu_wrapper">
                <i class="ki-filled ki-burger-menu-2">
                </i>
                </button>
            </div>
        </div>
        <!-- End of Mobile Logo -->
    {/if}

      <!-- Breadcrumbs -->
      <Breadcrumbs {breadcrumbs} {pageTitle} />
      <!-- End of Breadcrumbs -->

      <!-- Topbar -->
      <div class="flex items-center gap-2.5">
        {#if getAuthUser().type == 'admin'}
          <!-- School -->
          {#if getSchool()}
              <a href="{route('web.admin.schools.index')}" 
                 use:inertia
                 class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-accent/60 transition-colors cursor-pointer">
                  <img alt="{getSchool().name}" 
                      class="size-9 rounded-full shrink-0" 
                      src="{getSchool().logo_url}"/>
                  <div class="flex flex-col hidden lg:flex">
                      <span class="text-sm text-foreground font-semibold leading-none">
                          {getSchool().name}
                      </span>
                  </div>
              </a>
          {/if}
          <!-- End of School -->
        {:else if getAuthUser().type == 'guardian'}
        <!-- School -->
        {#if getSchool()}
            <a href="#" 
                class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-accent/60 transition-colors cursor-pointer">
                    <img alt="{getSchool().name}" 
                        class="size-9 rounded-full shrink-0" 
                        src="{getSchool().logo_url}"/>
                    <div class="flex flex-col hidden lg:flex">
                        <span class="text-sm text-foreground font-semibold leading-none">
                            {getSchool().name}
                        </span>
                    </div>
                </a>
            {/if}
            <!-- End of School -->
        {/if}

          <!-- Notifications -->
          <Notifications />
          <!-- End of Notifications -->

          <!-- AI Agents -->
          <a
              href={route('web.admin.chats.index')}
              use:inertia
              class="kt-btn kt-btn-icon kt-btn-ghost rounded-full size-9 border-2 border-transparent hover:border-primary hover:bg-primary/10 relative"
              aria-label="AI Agents"
              title="AI Agents"
          >
              <i class="fa-solid fa-robot text-sm"></i>
          </a>
          <!-- End of AI Agents -->

          <!-- Mail Simulator -->
          <a
              href={route('web.admin.mail-simulator.index')}
              use:inertia
              class="kt-btn kt-btn-icon kt-btn-ghost rounded-full size-9 border-2 border-transparent hover:border-amber-500 hover:bg-amber-500/10 relative"
              aria-label="Emails"
              title="Emails"
          >
              <i class="fa-solid fa-envelope text-sm"></i>
          </a>
          <!-- End of Emails -->
              
          <!-- User -->
          <div class="shrink-0" data-kt-dropdown="true" data-kt-dropdown-offset="10px, 10px" data-kt-dropdown-offset-rtl="-20px, 10px" data-kt-dropdown-placement="bottom-end" data-kt-dropdown-placement-rtl="bottom-start" data-kt-dropdown-trigger="click">
              <div class="cursor-pointer shrink-0" data-kt-dropdown-toggle="true">
                  <img alt="{getAuthUser().name}" 
                      class="size-9 rounded-full border-2 border-green-500 shrink-0" 
                      src="{getAuthUser().avatar_url}"/>
              </div>
              <div class="kt-dropdown-menu w-[250px]" data-kt-dropdown-menu="true">
                  <div class="flex items-center justify-between px-2.5 py-1.5 gap-1.5">
                      <div class="flex items-center gap-2">
                          <img alt="{getAuthUser().name}" 
                              class="size-9 shrink-0 rounded-full border-2 border-green-500" 
                              src="{getAuthUser().avatar_url}"/>

                          <div class="flex flex-col gap-1.5">
                              <span class="text-sm text-foreground font-semibold leading-none">
                                  {getAuthUser().name}
                              </span>
                              <span class="text-xs text-secondary-foreground hover:text-primary font-medium leading-none">
                                  {getAuthUser().email}
                              </span>
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
                        {#if getAuthUser().type === 'guardian'}
                            <a 
                                href={route('web.guardian.payments.topup')}
                                use:inertia
                                class="flex items-center gap-2 justify-between p-2 rounded-lg hover:bg-accent/60 transition-colors"
                            >
                                <span class="flex items-center gap-2">
                                    <i class="fa-solid fa-wallet text-base text-primary"></i>
                                    <span class="font-medium text-2sm">Balance</span>
                                </span>
                                <span class="font-semibold text-sm text-primary">
                                    {getBalance()} {getCurrency()}
                                </span>
                            </a>
                            <div class="border-b border-border"></div>
                        {/if}
                        <div class="flex items-center gap-2 justify-between">
                            <span class="flex items-center gap-2">
                                <i class="fa-solid fa-moon text-base text-muted-foreground"></i>

                                <span class="font-medium text-2sm">
                                    Dark Mode
                                </span>
                            </span>
                            <input class="kt-switch" data-kt-theme-switch-state="dark" data-kt-theme-switch-toggle="true" name="check" type="checkbox" value="1"/>
                        </div>
                      <button 
                          class="kt-btn kt-btn-outline justify-center w-full"
                          on:click={handleLogout}
                          disabled={loggingOut}
                          >
                      {#if loggingOut}
                      <i class="ki-outline ki-loading text-base animate-spin me-2"></i>
                        Logging out...
                      {:else}
                        Log out
                      {/if}
                      </button>
                  </div>
              </div>
          </div>
          <!-- End of User -->
      </div>
      <!-- End of Topbar -->
  </div>
  <!-- End of Container -->
</header>
<!-- End of Header -->