<script>
    import AdminLayout from '../Layouts/AdminLayout.svelte';
    import Pagination from '../Components/Pagination.svelte';
    import { onMount, tick } from 'svelte';

    // Define breadcrumbs for this page
    const breadcrumbs = [
        {
            title: 'Brands',
            url: route('admin.brands.index'),
            active: false
        },
        {
            title: 'Index',
            url: route('admin.brands.index'),
            active: true
        }
    ];

    const pageTitle = 'Brands';

    // Reactive variables
    let brands = [];
    let pagination = {};
    let loading = true;
    let search = '';
    let perPage = 10;
    let currentPage = 1;
    let searchTimeout;

    // Fetch brands data
    async function fetchBrands() {
        loading = true;
        try {
            const response = await fetch(route('admin.brands.index', {
                page: currentPage,
                perPage: perPage,
                search: search
            }), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();
            brands = data.brands;
            pagination = data.pagination;

            // Wait for DOM to update, then initialize menus
            await tick();
            if (window.KTMenu) {
                window.KTMenu.init();
            }
        } catch (error) {
            console.error('Error fetching brands:', error);
        } finally {
            loading = false;
        }
    }

    // Handle search with debouncing
    function handleSearch() {
        if (searchTimeout) {
            clearTimeout(searchTimeout);
        }

        searchTimeout = setTimeout(() => {
            currentPage = 1;
            fetchBrands();
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
            fetchBrands();
        }
    }

    // Handle per page change
    function handlePerPageChange(newPerPage) {
        perPage = newPerPage;
        currentPage = 1;
        fetchBrands();
    }

    // Delete brand
    async function deleteBrand(brandId) {
        if (!confirm('Are you sure you want to delete this brand? Its assets and files will be removed too. This action cannot be undone.')) {
            return;
        }

        try {
            const formData = new FormData();
            formData.append('_method', 'DELETE');
            formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'));

            const response = await fetch(route('admin.brands.destroy', { brand: brandId }), {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });

            if (response.ok) {
                KTToast.show({
                    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-info-icon lucide-info"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>`,
                    message: "Brand deleted successfully!",
                    variant: "success",
                    position: "bottom-right",
                });

                fetchBrands();
            } else {
                const errorData = await response.json().catch(() => ({}));
                const errorMessage = errorData.message || 'Error deleting brand. Please try again.';

                KTToast.show({
                    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-info-icon lucide-info"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>`,
                    message: errorMessage,
                    variant: "destructive",
                    position: "bottom-right",
                });
            }
        } catch (error) {
            console.error('Error deleting brand:', error);

            KTToast.show({
                    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-info-icon lucide-info"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>`,
                    message: "Network error. Please check your connection and try again.",
                    variant: "destructive",
                    position: "bottom-right",
            });
        }
    }

    onMount(() => {
        fetchBrands();
    });

    // Flash message handling
    export let success;

    $: if (success) {
        KTToast.show({
            icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-info-icon lucide-info"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>`,
            message: success,
            variant: "success",
            position: "bottom-right",
        });
    }
</script>

<svelte:head>
    <title>Saud international schools - {pageTitle}</title>
</svelte:head>

<AdminLayout {breadcrumbs} {pageTitle}>
    <!-- Container -->
    <div class="kt-container-fixed">
        <div class="grid gap-5 lg:gap-7.5">
            <!-- Brands Header -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <h1 class="text-2xl font-bold text-mono">Brands Management</h1>
                    <p class="text-sm text-secondary-foreground">
                        Manage the company identities (visual, audio, scent) and their asset libraries
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    {#if hasPermission('admin.brands.store')}
                    <a href="{route('admin.brands.create')}" class="kt-btn kt-btn-primary">
                        <i class="ki-filled ki-plus text-base"></i>
                        Add New Brand
                    </a>
                    {/if}
                </div>
            </div>

            <!-- Brands Table -->
            <div class="kt-card">
                <div class="kt-card-header">
                    <div class="kt-card-toolbar">
                        <div class="kt-input max-w-64 w-64">
                            <i class="ki-filled ki-magnifier"></i>
                            <input
                                type="text"
                                class="kt-input"
                                placeholder="Search brands..."
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
                                            <span class="kt-table-col-label">Brand</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[140px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Slug</span>
                                        </span>
                                    </th>
                                    <th class="w-[110px]">
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
                                                <div class="kt-skeleton w-20 h-4 rounded"></div>
                                            </td>
                                            <td class="p-4">
                                                <div class="kt-skeleton w-16 h-6 rounded"></div>
                                            </td>
                                            <td class="p-4">
                                                <div class="kt-skeleton w-8 h-8 rounded"></div>
                                            </td>
                                        </tr>
                                    {/each}
                                {:else if brands.length === 0}
                                    <!-- Empty state -->
                                    <tr>
                                        <td colspan="8" class="p-10">
                                            <div class="flex flex-col items-center justify-center text-center">
                                                <div class="mb-4">
                                                    <i class="ki-filled ki-home-3 text-4xl text-muted-foreground"></i>
                                                </div>
                                                <h3 class="text-lg font-semibold text-mono mb-2">No brands found</h3>
                                                <p class="text-sm text-secondary-foreground mb-4">
                                                    {search ? 'No brands match your search criteria.' : 'Get started by creating your first brand.'}
                                                </p>
                                                {#if hasPermission('admin.brands.store')}
                                                <a href="{route('admin.brands.create')}" class="kt-btn kt-btn-primary">
                                                    <i class="ki-filled ki-plus text-base"></i>
                                                    Create First Brand
                                                </a>
                                                {/if}
                                            </div>
                                        </td>
                                    </tr>
                                {:else}
                                    <!-- Actual data rows -->
                                    {#each brands as brand}
                                        <tr class="hover:bg-muted/50">
                                            <td>
                                                <input class="kt-checkbox kt-checkbox-sm" type="checkbox" value={brand.id}/>
                                            </td>
                                            <td>
                                                <span class="text-sm font-medium text-mono">#{brand.id}</span>
                                            </td>
                                            <td>
                                                <div class="flex items-center gap-3">
                                                    <div class="flex-shrink-0">
                                                        <img
                                                            src={brand.thumbnailUrl}
                                                            alt={brand.name}
                                                            class="w-10 h-10 rounded-lg object-cover"
                                                        />
                                                    </div>
                                                    <div class="flex flex-col gap-1">
                                                        <span class="text-sm font-medium text-mono hover:text-primary">
                                                            {brand.name}
                                                        </span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="flex flex-col gap-1">
                                                    <span class="text-xs text-secondary-foreground">/identity/{brand.slug}</span>

                                                </div>
                                            </td>
                                            <td>
                                                <span class="kt-badge kt-badge-outline {brand.status === 'published' ? 'kt-badge-success' : 'kt-badge-warning'}">
                                                    {brand.status}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <div class="kt-menu flex-inline" data-kt-menu="true">
                                                    <div class="kt-menu-item" data-kt-menu-item-offset="0, 10px" data-kt-menu-item-placement="bottom-end" data-kt-menu-item-placement-rtl="bottom-start" data-kt-menu-item-toggle="dropdown" data-kt-menu-item-trigger="click">
                                                        <button class="kt-menu-toggle kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost">
                                                            <i class="ki-filled ki-dots-vertical text-lg"></i>
                                                        </button>
                                                        <div class="kt-menu-dropdown kt-menu-default w-full max-w-[175px]" data-kt-menu-dismiss="true">
                                                            {#if hasPermission('admin.brands.show')}
                                                            <div class="kt-menu-item">
                                                                <a class="kt-menu-link" href={route('admin.brands.show', { brand: brand.id })}>
                                                                    <span class="kt-menu-icon">
                                                                        <i class="ki-filled ki-search-list"></i>
                                                                    </span>
                                                                    <span class="kt-menu-title">View</span>
                                                                </a>
                                                            </div>
                                                            {/if}
                                                            {#if hasPermission('admin.brands.update')}
                                                            <div class="kt-menu-item">
                                                                <a class="kt-menu-link" href={route('admin.brands.edit', { brand: brand.id })}>
                                                                    <span class="kt-menu-icon">
                                                                        <i class="ki-filled ki-pencil"></i>
                                                                    </span>
                                                                    <span class="kt-menu-title">Edit</span>
                                                                </a>
                                                            </div>
                                                            {/if}
                                                            {#if hasPermission('admin.brands.destroy')}
                                                                <div class="kt-menu-separator"></div>
                                                                <div class="kt-menu-item">
                                                                    <button class="kt-menu-link" on:click={() => deleteBrand(brand.id)}>
                                                                        <span class="kt-menu-icon">
                                                                            <i class="ki-filled ki-trash"></i>
                                                                        </span>
                                                                        <span class="kt-menu-title">Remove</span>
                                                                    </button>
                                                                </div>
                                                            {/if}
                                                        </div>
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
</AdminLayout>
