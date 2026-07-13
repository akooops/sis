<script>
    import { createEventDispatcher, tick } from 'svelte';
    import SearchBar from '../../../Shared/Utils/Forms/SearchBar.svelte';
    import Pagination from '../../../Shared/Utils/Pagination.svelte';
    import CreateForm from './CreateForm.svelte';
    import { fly } from 'svelte/transition';

    const dispatch = createEventDispatcher();

    export let productionSite = null;

    let siteProducts = [];
    let search = '';
    let loading = true;
    let errors = {};

    let perPage = 10;
    let currentPage = 1;
    let pagination = {};

    let showCreateForm = false;

    $: if (productionSite) {
        fetchSiteProducts();
    }

    async function fetchSiteProducts() {
        if (!productionSite) return;

        loading = true;
        errors = {};

        try {
            const response = await fetch(route('api.v1.admin.site-products.index', {
                productionSite: productionSite.id,
                page: currentPage,
                per_page: perPage,
                search: search,
                sort_direction: 'desc',
            }), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
            });

            if (response.ok) {
                const data = await response.json();
                siteProducts = data.site_products || [];
                pagination = data.pagination || {};

                await tick();

                if (window.KTMenu) {
                    window.KTMenu.init();
                }
            }
        } catch (error) {
            console.error('Error loading site products:', error);
            errors = { general: 'Failed to load site products. Please try again.' };
        } finally {
            loading = false;
        }
    }

    async function handleDeleteSiteProduct(siteProduct) {
        const productName = siteProduct.product?.name || 'this product';
        const confirmed = confirm(`Are you sure you want to remove "${productName}" from this site?\n\nThis action cannot be undone.`);

        if (!confirmed) {
            return;
        }

        try {
            const response = await fetch(route('api.v1.admin.site-products.destroy', { siteProduct: siteProduct.id }), {
                method: 'DELETE',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
            });

            const data = await response.json();

            if (response.ok) {
                toast('Site product removed successfully', 'success');
                fetchSiteProducts();
                dispatch('productsUpdated');
            } else if (response.status === 422 && data.errors) {
                let errorMessage = 'Cannot remove site product:\n';
                Object.entries(data.errors).forEach(([field, message]) => {
                    errorMessage += `• ${message}\n`;
                });
                toast(errorMessage, 'error');
            } else {
                toast(data.message || 'An error occurred while removing the site product.', 'error');
            }
        } catch (error) {
            console.error('Network error:', error);
            toast('Network error occurred. Please try again.', 'error');
        }
    }

    function handleSearchFromComponent(event) {
        search = event.detail.value;
        currentPage = 1;
        fetchSiteProducts();
    }

    function goToPage(page) {
        if (page && page !== currentPage) {
            currentPage = page;
            fetchSiteProducts();
        }
    }

    function handlePerPageChange(newPerPage) {
        perPage = newPerPage;
        currentPage = 1;
        fetchSiteProducts();
    }

    function showAddProductForm() {
        showCreateForm = true;
    }

    function showProductsTable() {
        showCreateForm = false;
    }

    function handleFormCreated() {
        showCreateForm = false;
        fetchSiteProducts();
        dispatch('productsUpdated');
    }

    function handleFormCanceled() {
        showCreateForm = false;
    }
</script>

<div class="hidden kt-drawer kt-drawer-end card flex-col max-w-[95%] w-[600px] top-5 bottom-5 end-5 rounded-xl border border-border overflow-hidden-x" data-kt-drawer="true" data-kt-drawer-container="body" id="site_products_drawer">
    <div class="flex items-center justify-between gap-2.5 text-sm text-mono font-semibold px-5 py-2.5 border-b border-b-border">
        <div class="flex items-center gap-2">
            Site Products:
            {#if productionSite}
                <span class="text-muted-foreground">{productionSite.name}</span>
            {/if}
        </div>
        <button class="kt-btn kt-btn-sm kt-btn-icon kt-btn-dim shrink-0" data-kt-drawer-dismiss="true">
            <i class="ki-filled ki-cross"></i>
        </button>
    </div>

    <div class="kt-card w-full">
        <div class="kt-card-header">
            <div class="kt-card-toolbar flex items-center justify-between w-full">
                {#if showCreateForm}
                    <button
                        type="button"
                        class="kt-btn kt-btn-sm kt-btn-secondary"
                        on:click={showProductsTable}
                    >
                        <i class="fa-solid fa-arrow-left mr-1"></i>
                        Back
                    </button>
                {:else}
                    <div class="flex items-center justify-between w-full gap-2">
                        <SearchBar
                            bind:value={search}
                            placeholder="Search products..."
                            debounceMs={500}
                            showScanner={false}
                            on:search={handleSearchFromComponent}
                        />

                        {#if hasPermission('site-products.store')}
                            <button
                                type="button"
                                class="kt-btn kt-btn-sm kt-btn-primary shrink-0"
                                on:click={showAddProductForm}
                            >
                                <i class="fa-solid fa-plus mr-1"></i>
                                Add Product
                            </button>
                        {/if}
                    </div>
                {/if}
            </div>
        </div>

        {#if showCreateForm}
            <div class="kt-card-content p-4" in:fly={{ x: '100%', duration: 750 }}>
                <CreateForm
                    {productionSite}
                    on:created={handleFormCreated}
                    on:canceled={handleFormCanceled}
                />
            </div>
        {:else}
            <div class="kt-card-content p-0" in:fly={{ x: '-100%', duration: 750 }}>
                {#if errors.general}
                    <div class="p-4">
                        <p class="text-sm text-destructive">{errors.general}</p>
                    </div>
                {/if}

                <div class="kt-scrollable-x-auto kt-card-table">
                    <table class="kt-table kt-table-auto kt-table-border text-sm">
                        <thead>
                            <tr>
                                <th style="width: 115px;">
                                    <span class="kt-table-col whitespace-nowrap capitalize">ID</span>
                                </th>
                                <th>
                                    <span class="kt-table-col whitespace-nowrap capitalize">Product</span>
                                </th>
                                <th class="w-[80px]">
                                    <span class="kt-table-col whitespace-nowrap capitalize">Actions</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {#if loading}
                                {#each Array(perPage) as _, i}
                                    <tr>
                                        <td class="p-4"><div class="kt-skeleton w-full h-4 rounded"></div></td>
                                        <td class="p-4"><div class="kt-skeleton w-full h-4 rounded"></div></td>
                                        <td class="p-4"><div class="kt-skeleton w-8 h-8 rounded"></div></td>
                                    </tr>
                                {/each}
                            {:else if siteProducts.length === 0}
                                <tr>
                                    <td colspan="3" class="p-10">
                                        <div class="flex flex-col items-center justify-center text-center">
                                            <div class="mb-4">
                                                <i class="fa-solid fa-boxes-stacked text-4xl text-muted-foreground"></i>
                                            </div>
                                            <h3 class="text-lg font-semibold text-mono mb-2">No products found</h3>
                                            <p class="text-sm text-secondary-foreground mb-4">
                                                {search ? 'No products match your search criteria.' : 'This site has no finished products assigned yet.'}
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            {:else}
                                {#each siteProducts as siteProduct}
                                    <tr class="hover:bg-muted">
                                        <td>
                                            <a href={route('web.admin.products.index', { search: siteProduct.product?.id })}>
                                                <span class="text-xs font-medium text-primary">#{siteProduct.id}</span>
                                            </a>
                                        </td>
                                        <td>
                                            {#if siteProduct.product}
                                                <div class="flex items-center gap-3 max-content">
                                                    <img
                                                        src={siteProduct.product.image_url}
                                                        alt={siteProduct.product.name}
                                                        class="w-[30px] h-[30px] rounded-lg object-cover"
                                                    />
                                                    <div class="flex flex-col gap-1">
                                                        <span class="text-sm font-medium text-secondary-foreground">
                                                            {siteProduct.product.name}
                                                        </span>
                                                        {#if siteProduct.product.code}
                                                            <span class="text-xs text-muted-foreground">{siteProduct.product.code}</span>
                                                        {/if}
                                                    </div>
                                                </div>
                                            {:else}
                                                <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">N/A</span>
                                            {/if}
                                        </td>
                                        <td class="text-center">
                                            <div class="kt-menu flex-inline" data-kt-menu="true">
                                                <div class="kt-menu-item" data-kt-menu-item-offset="0, 10px" data-kt-menu-item-placement="bottom-end" data-kt-menu-item-toggle="dropdown" data-kt-menu-item-trigger="click">
                                                    <button class="kt-menu-toggle kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" aria-label="Open actions menu">
                                                        <i class="ki-filled ki-dots-vertical text-lg"></i>
                                                    </button>
                                                    <div class="kt-menu-dropdown kt-menu-default w-full max-w-[175px]">
                                                        {#if hasPermission('site-products.destroy')}
                                                            <div class="kt-menu-item">
                                                                <button class="kt-menu-link text-destructive" data-kt-menu-dismiss="true" on:click={() => handleDeleteSiteProduct(siteProduct)}>
                                                                    <span class="kt-menu-icon">
                                                                        <i class="fa-solid fa-trash-can"></i>
                                                                    </span>
                                                                    <span class="kt-menu-title">Delete</span>
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

                {#if pagination && pagination.total > 0}
                    <Pagination
                        {pagination}
                        {perPage}
                        onPageChange={goToPage}
                        onPerPageChange={handlePerPageChange}
                    />
                {/if}
            </div>
        {/if}
    </div>
</div>
