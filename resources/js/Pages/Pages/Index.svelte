<script>
    import AdminLayout from '../Layouts/AdminLayout.svelte';
    import Pagination from '../Components/Pagination.svelte';
    import PageDetailsDrawer from './View.svelte';
    import { onMount } from 'svelte';

    // Define breadcrumbs for this page
    const breadcrumbs = [
        {
            title: 'Pages',
            url: '/pages',
            active: false
        },
        {
            title: 'Index',
            url: '/pages/index',
            active: true
        }
    ];
    
    const pageTitle = 'Pages';

    // Reactive variables
    let pages = [];
    let pagination = {};
    let loading = true;
    let search = '';
    let perPage = 10;
    let currentPage = 1;
    let searchTimeout;

    // Drawer state
    let selectedPage = null;
    let isDrawerOpen = false;

    // Fetch pages data
    async function fetchPages() {
        loading = true;
        try {
            const params = new URLSearchParams({
                page: currentPage,
                perPage: perPage,
                search: search
            });
            
            const response = await fetch(`/admin/pages?${params}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const data = await response.json();
            pages = data.pages;
            pagination = data.pagination;
        } catch (error) {
            console.error('Error fetching pages:', error);
        } finally {
            loading = false;
        }
    }

    // Handle search with debouncing
    function handleSearch() {
        // Clear existing timeout
        if (searchTimeout) {
            clearTimeout(searchTimeout);
        }
        
        // Set new timeout for 500ms
        searchTimeout = setTimeout(() => {
            currentPage = 1;
            fetchPages();
        }, 500);
    }

    // Handle search input change
    function handleSearchInput(event) {
        search = event.target.value;
        handleSearch();
    }

    // Handle pagination
    function goToPage(page) {
        if (page && page !== currentPage) {
            currentPage = page;
            fetchPages();
        }
    }

    // Handle per page change
    function handlePerPageChange(newPerPage) {
        perPage = newPerPage;
        currentPage = 1;
        fetchPages();
    }

    // Get status badge class
    function getStatusBadgeClass(status) {
        switch (status) {
            case 'published':
                return 'kt-badge-success';
            case 'draft':
                return 'kt-badge-info';
            case 'hidden':
                return 'kt-badge-primary';
            default:
                return 'kt-badge-secondary';
        }
    }

    // Get status text
    function getStatusText(status) {
        switch (status) {
            case 'published':
                return 'Published';
            case 'draft':
                return 'Draft';
            case 'hidden':
                return 'Hidden';
            default:
                return status;
        }
    }

    // Open page details drawer
    function openPageDetails(page) {
        selectedPage = page;
        isDrawerOpen = true;
        
        // Use Metronic's drawer toggle system
        const drawerToggle = document.getElementById('page_details_drawer_toggle');
        if (drawerToggle) {
            drawerToggle.click();
        }
    }

    // Close page details drawer
    function closePageDetails() {
        isDrawerOpen = false;
        selectedPage = null;
    }

    // Handle page deleted from drawer
    function handlePageDeleted(event) {
        const { pageId } = event.detail;
        // Remove the deleted page from the list
        pages = pages.filter(page => page.id !== pageId);
        closePageDetails();
    }

    // Delete page
    async function deletePage(pageId) {
        if (!confirm('Are you sure you want to delete this page? This action cannot be undone.')) {
            return;
        }

        try {
            const response = await fetch(`/admin/pages/${pageId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (response.ok) {
                // Refresh the pages list
                fetchPages();
            } else {
                alert('Error deleting page. Please try again.');
            }
        } catch (error) {
            console.error('Error deleting page:', error);
            alert('Error deleting page. Please try again.');
        }
    }

    onMount(() => {
        fetchPages();
    });
</script>

<svelte:head>
    <title>Saud international schools - {pageTitle}</title>
</svelte:head>

<AdminLayout {breadcrumbs} {pageTitle}>
    <!-- Container -->
    <div class="kt-container-fixed">
        <div class="grid gap-5 lg:gap-7.5">
            <!-- Page Header -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <h1 class="text-2xl font-bold text-mono">Pages Management</h1>
                    <p class="text-sm text-secondary-foreground">
                        Manage your website pages and content
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{route('admin.pages.create')}" class="kt-btn kt-btn-primary">
                        <i class="ki-filled ki-plus text-base"></i>
                        Add New Page
                    </a>
                </div>
            </div>

            <!-- Pages Table -->
            <div class="kt-card">
                <div class="kt-card-header">
                    <div class="kt-card-toolbar">
                        <div class="kt-input max-w-64 w-64">
                            <i class="ki-filled ki-magnifier"></i>
                            <input 
                                type="text" 
                                class="kt-input" 
                                placeholder="Search pages..." 
                                bind:value={search}
                                on:input={handleSearchInput}
                            />
                        </div>
                    </div>
                </div>
                
                <div class="kt-card-content p-0">
                    <div class="kt-scrollable-x-auto">
                        <table class="kt-table kt-table-border table-fixed">
                            <thead>
                                <tr>
                                    <th class="w-[50px]">
                                        <input class="kt-checkbox kt-checkbox-sm" type="checkbox"/>
                                    </th>
                                    <th class="w-[80px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">ID</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[200px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Page</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[150px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Slug</span>
                                        </span>
                                    </th>
                                    <th class="w-[120px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">System Page</span>
                                        </span>
                                    </th>
                                    <th class="w-[100px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Status</span>
                                        </span>
                                    </th>
                                    <th class="w-[80px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Actions</span>
                                        </span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {#if loading}
                                    <!-- Loading skeleton rows -->
                                    {#each Array(perPage) as _, i}
                                        <tr>
                                            <td class="p-4">
                                                <div class="kt-skeleton w-4 h-4 rounded"></div>
                                            </td>
                                            <td class="p-4">
                                                <div class="kt-skeleton w-8 h-4 rounded"></div>
                                            </td>
                                            <td class="p-4">
                                                <div class="flex items-center gap-3">
                                                    <div class="kt-skeleton w-10 h-10 rounded-lg"></div>
                                                    <div class="flex flex-col gap-1">
                                                        <div class="kt-skeleton w-24 h-4 rounded"></div>
                                                        <div class="kt-skeleton w-16 h-3 rounded"></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="p-4">
                                                <div class="kt-skeleton w-16 h-6 rounded"></div>
                                            </td>
                                            <td class="p-4">
                                                <div class="kt-skeleton w-12 h-6 rounded"></div>
                                            </td>
                                            <td class="p-4">
                                                <div class="kt-skeleton w-16 h-6 rounded"></div>
                                            </td>
                                            <td class="p-4">
                                                <div class="kt-skeleton w-20 h-4 rounded"></div>
                                            </td>
                                            <td class="p-4">
                                                <div class="kt-skeleton w-8 h-8 rounded"></div>
                                            </td>
                                        </tr>
                                    {/each}
                                {:else if pages.length === 0}
                                    <!-- Empty state -->
                                    <tr>
                                        <td colspan="8" class="p-10">
                                            <div class="flex flex-col items-center justify-center text-center">
                                                <div class="mb-4">
                                                    <i class="ki-filled ki-document text-4xl text-muted-foreground"></i>
                                                </div>
                                                <h3 class="text-lg font-semibold text-mono mb-2">No pages found</h3>
                                                <p class="text-sm text-secondary-foreground mb-4">
                                                    {search ? 'No pages match your search criteria.' : 'Get started by creating your first page.'}
                                                </p>
                                                <a href="{route('admin.pages.create')}" class="kt-btn kt-btn-primary">
                                                    <i class="ki-filled ki-plus text-base"></i>
                                                    Create First Page
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                {:else}
                                    <!-- Actual data rows -->
                                    {#each pages as page}
                                        <tr class="cursor-pointer hover:bg-muted/50" on:click={() => openPageDetails(page)}>
                                            <td>
                                                <input class="kt-checkbox kt-checkbox-sm" type="checkbox" value={page.id}/>
                                            </td>
                                            <td>
                                                <span class="text-sm font-medium text-mono">#{page.id}</span>
                                            </td>
                                            <td>
                                                <div class="flex items-center gap-3">
                                                    <div class="flex-shrink-0">
                                                        <img 
                                                            src={page.thumbnailUrl} 
                                                            alt={page.name}
                                                            class="w-10 h-10 rounded-lg object-cover"
                                                        />
                                                    </div>
                                                    <div class="flex flex-col gap-1">
                                                        <span class="text-sm font-medium text-mono hover:text-primary">
                                                            {page.name}
                                                        </span>
                                                        <span class="text-xs text-secondary-foreground">
                                                            {page.title || 'No title'}
                                                        </span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="kt-badge kt-badge-outline kt-badge-primary">
                                                    {page.slug}
                                                </span>
                                            </td>
                                            <td>
                                                {#if page.is_system_page}
                                                    <span class="kt-badge kt-badge-success">Yes</span>
                                                {:else}
                                                    <span class="kt-badge kt-badge-secondary">No</span>
                                                {/if}
                                            </td>
                                            <td>
                                                <span class="kt-badge {getStatusBadgeClass(page.status)}">
                                                    {getStatusText(page.status)}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="kt-menu" data-kt-menu="true">
                                                    <button class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" data-kt-menu-toggle="true" on:click|stopPropagation={() => {}}>
                                                        <i class="ki-filled ki-dots-vertical text-lg"></i>
                                                    </button>
                                                    <div class="kt-menu-dropdown kt-menu-default w-full max-w-[175px]" data-kt-menu-dismiss="true">
                                                        <div class="kt-menu-item">
                                                            <button class="kt-menu-link" on:click={() => openPageDetails(page)}>
                                                                <span class="kt-menu-icon">
                                                                    <i class="ki-filled ki-eye"></i>
                                                                </span>
                                                                <span class="kt-menu-title">View</span>
                                                            </button>
                                                        </div>
                                                        <div class="kt-menu-item">
                                                            <a class="kt-menu-link" href={`/admin/pages/${page.id}/edit`}>
                                                                <span class="kt-menu-icon">
                                                                    <i class="ki-filled ki-pencil"></i>
                                                                </span>
                                                                <span class="kt-menu-title">Edit</span>
                                                            </a>
                                                        </div>
                                                        {#if !page.is_system_page}
                                                            <div class="kt-menu-separator"></div>
                                                            <div class="kt-menu-item">
                                                                <button class="kt-menu-link text-destructive" on:click={() => deletePage(page.id)}>
                                                                    <span class="kt-menu-icon">
                                                                        <i class="ki-filled ki-trash"></i>
                                                                    </span>
                                                                    <span class="kt-menu-title">Delete</span>
                                                                </button>
                                                            </div>
                                                        {/if}
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    {/each}
                                {/if}
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    {#if pagination && pagination.total > 0}
                        <Pagination 
                            {pagination} 
                            {perPage}
                            onPageChange={goToPage} 
                            onPerPageChange={handlePerPageChange}
                        />
                    {/if}
                </div>
            </div>
        </div>
    </div>
    <!-- End of Container -->

    <!-- Page Details Drawer -->
    <PageDetailsDrawer 
        page={selectedPage} 
        isOpen={isDrawerOpen} 
        on:close={closePageDetails}
        on:pageDeleted={handlePageDeleted}
    />

    <!-- Hidden drawer toggle button for Metronic -->
    <button 
        class="hidden" 
        data-kt-drawer-toggle="#page_details_drawer" 
        id="page_details_drawer_toggle"
    ></button>
</AdminLayout>