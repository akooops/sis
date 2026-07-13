<script>
    import { page } from '@inertiajs/svelte';
    import AdminSidebar from './Sidebars/AdminSidebar.svelte';
    import Topbar from './Topbar.svelte';
    import { onMount } from 'svelte';

    // Props for the layout
    export let breadcrumbs = [];
    export let pageTitle = null;

    export let showSidebar = true;
  
    // Global utility functions for admin components
    function getAuthUser() {
        return $page.props.auth.user;
    }

    function getSchool() {
        return $page.props.school;
    }

    function hasPermission(permission) {
        if (!$page.props.auth.enable_permissions) return true;
    
        return $page.props.auth.permissions.some(p => p === permission);
    }
  
    function isActiveRoute(url) {
        const currentUrl = window.location.href.split('?')[0];
        
        // If url is an array, check if any of the urls match
        if (Array.isArray(url)) {
            return url.some(u => currentUrl.startsWith(u));
        }
        
        // Otherwise, check the single url
        return currentUrl.startsWith(url);
    }
    
    function toast(message, variant) {
        KTToast.show({
            icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-info-icon lucide-info"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>`,
            message: message,
            variant: variant,
            position: "bottom-end",
        });
    }

    function formatTimeStamp(timestamp) {
        if (!timestamp) return '';
        const date = new Date(timestamp);
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        
        return `${year}-${month}-${day} ${hours}:${minutes}`;
    }

    function maxUploadSize() {
        return formatFileSize($page.props.files?.max_upload_size || 10485760); // 10MB default
    }

    function getPriorityBadgeClass(priority) {
        switch (priority) {
            case 'urgent':
                return 'kt-badge kt-badge-outline kt-badge-danger text-xs font-medium capitalize';
            case 'high':
                return 'kt-badge kt-badge-outline kt-badge-warning text-xs font-medium capitalize';
            case 'medium':
                return 'kt-badge kt-badge-outline kt-badge-primary text-xs font-medium capitalize';
            case 'low':
                return 'kt-badge kt-badge-outline kt-badge-info text-xs font-medium capitalize';
            case 'none':
                return 'kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize';
            default:
                return 'kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize';
        }
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    function prepareFormData(form, update = false) {
        let formData = new FormData();

        if (update) {
            formData.append('_method', 'PUT');
        }

        if (form) {
            Object.keys(form).forEach(key => {
                const value = form[key];
                
                if (key.includes('_id')) {
                    formData.append(key, value !== null && value !== undefined ? value : '');
                } else if (value === true || value === false) {
                    formData.append(key, value ? '1' : '0');
                } else if (Array.isArray(value)) {
                    formData.append(key, JSON.stringify(value));
                } else if (value !== null && value !== undefined && value !== '') {
                    formData.append(key, value);
                }
            });
        }

        return formData;
    }
  
    function getCurrency() {
        return $page.props.currency || 'SAR';
    }

    function getBalance() {
        if (getAuthUser().type !== 'guardian') return null;
        return $page.props.guardian_balance || 0;
    }

    // Make functions globally available for child components
    window.getAuthUser = getAuthUser;
    window.getSchool = getSchool;
    window.hasPermission = hasPermission;
    window.isActiveRoute = isActiveRoute;
    window.toast = toast;
    window.formatTimeStamp = formatTimeStamp;
    window.getPriorityBadgeClass = getPriorityBadgeClass;
    window.formatFileSize = formatFileSize;
    window.maxUploadSize = maxUploadSize;
    window.prepareFormData = prepareFormData;
    window.getCurrency = getCurrency;
    window.getBalance = getBalance;
</script>
  
<div class="flex grow" style="height: 100vh;">
    {#if showSidebar}
        <!-- Sidebar -->
        {#if getAuthUser().type == 'admin'}
            <AdminSidebar/>
        {/if}
        <!-- End of Sidebar -->
    {/if}
  
    <!-- Wrapper -->
    <div class="kt-wrapper flex grow flex-col">
        <!-- Header -->
        <Topbar {breadcrumbs} {pageTitle} />
        <!-- End of Header -->
  
        <!-- Main -->
        <main class="grow pt-5" id="content" role="content">
            <!-- Container -->
            <div class="kt-container-fixed" id="contentContainer">
            </div>
            <!-- End of Container -->

            <!-- Container -->
            {#if pageTitle}
                <div class="kt-container-fixed">
                    <h1 class="text-xl font-medium leading-none text-mono">{pageTitle}</h1>
                </div>
                <!-- End of Container -->
            {/if}

            <div class="kt-container-fixed mt-4">
                <slot />
            </div>
            <!-- End of Container -->
        </main>
        <!-- End of Main -->
    </div>
</div>