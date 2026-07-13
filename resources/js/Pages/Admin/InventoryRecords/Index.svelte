<script>
    import MainLayout from '../../Shared/Layouts/MainLayout.svelte';
    import Pagination from '../../Shared/Utils/Pagination.svelte';
    import SearchBar from '../../Shared/Utils/Forms/SearchBar.svelte';
    import ExportButton from '../../Shared/Utils/ExportButton.svelte';
    import FiltersDrawer from './FiltersDrawer.svelte';
    import ViewDrawer from './ViewDrawer.svelte';
    import { formatInventoryType, getInventoryTypeBadgeClass } from '../Warehouse/recordActions.js';
    import { onMount, tick } from 'svelte';
    import { fly } from 'svelte/transition';

    const breadcrumbs = [
        { title: 'Inventory Records', url: route('web.admin.inventory-records.index'), active: false },
        { title: 'Index', url: route('web.admin.inventory-records.index'), active: true },
    ];
    const pageTitle = 'Inventory Records';

    let inventoryRecords = [];
    let search = '';
    let loading = true;
    let perPage = 10;
    let currentPage = 1;
    let pagination = {};
    let filters = {
        sort_direction: 'desc',
        type: '',
        product_id: '',
        production_site_id: '',
        q_p_record_id: '',
    };
    let selectedInventoryRecord = null;

    function formatQuantityWithUnit(product, value) {
        if (value === null || value === undefined || value === '') return null;
        const unit = product?.unit_of_measurement?.trim();
        return unit ? `${value} ${unit}` : `${value}`;
    }

    async function fetchRecords() {
        loading = true;
        try {
            const queryParams = {
                page: currentPage,
                per_page: perPage,
                search,
                sort_direction: filters.sort_direction,
            };

            if (filters.type) queryParams.type = filters.type;
            if (filters.product_id) queryParams.product_id = filters.product_id;
            if (filters.production_site_id) queryParams.production_site_id = filters.production_site_id;
            if (filters.q_p_record_id) queryParams.q_p_record_id = filters.q_p_record_id;

            const response = await fetch(route('api.v1.admin.inventory-records.index', queryParams), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });

            const data = await response.json();
            inventoryRecords = data.inventory_records || [];
            pagination = data.pagination || {};

            await tick();
            window.KTMenu?.init();
        } catch (error) {
            console.error('Error fetching inventory records:', error);
        } finally {
            loading = false;
        }
    }

    function openViewDrawer(record) {
        selectedInventoryRecord = record;
        document.querySelector('[data-kt-drawer-toggle="#inventory_record_view_drawer"]')?.click();
    }

    function handleRowClick(record) {
        openViewDrawer(record);
    }

    function handleSearchFromComponent(event) {
        search = event.detail.value;
        currentPage = 1;
        fetchRecords();
    }

    function handleFiltersChange(event) {
        filters = event.detail;
        currentPage = 1;
        fetchRecords();
    }

    onMount(() => fetchRecords());
</script>

<svelte:head>
    <title>Novonordisk supply chain management system - {pageTitle}</title>
</svelte:head>

<MainLayout {breadcrumbs} {pageTitle}>
    <div class="grid gap-5 lg:gap-7.5">
        <div class="kt-card kt-card-grid min-w-full overflow-hidden">
            <div class="kt-card w-full border-0">
                <div class="kt-card-header">
                    <div class="kt-card-toolbar flex items-center justify-between w-full">
                        <div class="flex items-center gap-2">
                            <SearchBar bind:value={search} placeholder="Search inventory records..." debounceMs={500} on:search={handleSearchFromComponent} />
                            <button type="button" class="kt-btn kt-btn-sm kt-btn-ghost" on:click={() => document.querySelector('[data-kt-drawer-toggle="#inventory_records_filters_drawer"]')?.click()} title="Filter inventory records" aria-label="Filter inventory records">
                                <i class="fa-solid fa-filter"></i>
                            </button>
                            <ExportButton
                                tableData={inventoryRecords}
                                headers={[
                                    { key: 'id', label: 'ID' },
                                    { key: 'type', label: 'Type' },
                                    { key: 'product.name', label: 'Product' },
                                    { key: 'quantity', label: 'Quantity' },
                                ]}
                                filename="inventory-records"
                                totalRecords={pagination?.total || 0}
                                currentPerPage={perPage}
                                {filters}
                            />
                        </div>
                    </div>
                </div>

                <div class="kt-card-content p-0" in:fly={{ x: '-100%', duration: 750 }}>
                    <div class="kt-scrollable-x-auto kt-card-table">
                        <table class="kt-table kt-table-auto kt-table-border text-sm">
                            <thead>
                                <tr>
                                    <th style="width: 115px;"><span class="kt-table-col whitespace-nowrap capitalize">ID</span></th>
                                    <th><span class="kt-table-col whitespace-nowrap capitalize">Type</span></th>
                                    <th><span class="kt-table-col whitespace-nowrap capitalize">Product</span></th>
                                    <th><span class="kt-table-col whitespace-nowrap capitalize">Quantity</span></th>
                                    <th><span class="kt-table-col whitespace-nowrap capitalize">Site</span></th>
                                    <th class="w-[80px]"><span class="kt-table-col whitespace-nowrap capitalize">Actions</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                {#if loading}
                                    {#each Array(perPage) as _}
                                        <tr>
                                            <td class="p-4"><div class="kt-skeleton w-full h-4 rounded"></div></td>
                                            <td class="p-4"><div class="kt-skeleton w-full h-4 rounded"></div></td>
                                            <td class="p-4"><div class="kt-skeleton w-full h-4 rounded"></div></td>
                                            <td class="p-4"><div class="kt-skeleton w-full h-4 rounded"></div></td>
                                            <td class="p-4"><div class="kt-skeleton w-full h-4 rounded"></div></td>
                                            <td class="p-4"><div class="kt-skeleton w-8 h-8 rounded"></div></td>
                                        </tr>
                                    {/each}
                                {:else if inventoryRecords.length === 0}
                                    <tr>
                                        <td colspan="6" class="p-10">
                                            <div class="flex flex-col items-center justify-center text-center">
                                                <i class="fa-solid fa-boxes-stacked text-4xl text-muted-foreground mb-4"></i>
                                                <h3 class="text-lg font-semibold text-mono mb-2">No results found</h3>
                                                <p class="text-sm text-secondary-foreground">No inventory records match your criteria.</p>
                                            </div>
                                        </td>
                                    </tr>
                                {:else}
                                    {#each inventoryRecords as record}
                                        <tr class="hover:bg-muted cursor-pointer">
                                            <td on:click={() => handleRowClick(record)}>
                                                <span class="text-xs font-medium text-primary">#{record.id}</span>
                                            </td>
                                            <td on:click={() => handleRowClick(record)}>
                                                <span class="kt-badge kt-badge-outline {getInventoryTypeBadgeClass(record.type)} text-xs font-medium capitalize">
                                                    {formatInventoryType(record.type)}
                                                </span>
                                            </td>
                                            <td on:click={() => handleRowClick(record)}>
                                                {#if record.product}
                                                    <div class="flex items-center gap-3">
                                                        <img src={record.product.image_url} alt={record.product.name} class="w-[30px] h-[30px] rounded-lg object-cover" />
                                                        <span class="text-sm font-medium">{record.product.name}</span>
                                                    </div>
                                                {:else}
                                                    <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs">N/A</span>
                                                {/if}
                                            </td>
                                            <td on:click={() => handleRowClick(record)}>
                                                {#if formatQuantityWithUnit(record.product, record.quantity)}
                                                    <span class="text-sm font-medium">{formatQuantityWithUnit(record.product, record.quantity)}</span>
                                                {:else}
                                                    <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs">N/A</span>
                                                {/if}
                                            </td>
                                            <td on:click={() => handleRowClick(record)}>
                                                {#if record.production_site}
                                                    <span class="kt-badge kt-badge-outline kt-badge-info text-xs font-medium">{record.production_site.name}</span>
                                                {:else}
                                                    <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs">N/A</span>
                                                {/if}
                                            </td>
                                            <td class="text-center">
                                                <div class="kt-menu flex-inline" data-kt-menu="true">
                                                    <div class="kt-menu-item" data-kt-menu-item-offset="0, 10px" data-kt-menu-item-placement="bottom-end" data-kt-menu-item-toggle="dropdown" data-kt-menu-item-trigger="click">
                                                        <button class="kt-menu-toggle kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" aria-label="Open actions menu">
                                                            <i class="ki-filled ki-dots-vertical text-lg"></i>
                                                        </button>
                                                        <div class="kt-menu-dropdown kt-menu-default w-full max-w-[175px]">
                                                            <div class="kt-menu-item">
                                                                <button class="kt-menu-link" data-kt-menu-dismiss="true" on:click={() => openViewDrawer(record)}>
                                                                    <span class="kt-menu-icon"><i class="fa-solid fa-eye"></i></span>
                                                                    <span class="kt-menu-title">View</span>
                                                                </button>
                                                            </div>
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

                    {#if pagination?.total > 0}
                        <Pagination {pagination} {perPage} onPageChange={(page) => { currentPage = page; fetchRecords(); }} onPerPageChange={(newPerPage) => { perPage = newPerPage; currentPage = 1; fetchRecords(); }} />
                    {/if}
                </div>
            </div>
        </div>
    </div>

    <button style="display:none" data-kt-drawer-toggle="#inventory_records_filters_drawer" aria-label="Toggle filters drawer"></button>
    <button style="display:none" data-kt-drawer-toggle="#inventory_record_view_drawer" aria-label="Toggle view drawer"></button>

    <FiltersDrawer {filters} on:filtersChanged={handleFiltersChange} />
    <ViewDrawer {selectedInventoryRecord} />
</MainLayout>
