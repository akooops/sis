<script>
    import MainLayout from '../../Shared/Layouts/MainLayout.svelte';
    import Pagination from '../../Shared/Utils/Pagination.svelte';
    import SearchBar from '../../Shared/Utils/Forms/SearchBar.svelte';
    import ExportButton from '../../Shared/Utils/ExportButton.svelte';
    import FiltersDrawer from './FiltersDrawer.svelte';
    import CreateForm from './CreateForm.svelte';
    import EditForm from './EditForm.svelte';
    import { tick } from 'svelte';
    import { fly } from 'svelte/transition';

    const breadcrumbs = [
        {
            title: 'Import Quotas',
            url: route('web.admin.import-quotas.index'),
            active: false,
        },
        {
            title: 'Index',
            url: route('web.admin.import-quotas.index'),
            active: true,
        },
    ];

    const pageTitle = 'Import Quotas';
    const yearOptions = Array.from({ length: 101 }, (_, index) => 2000 + index);

    let selectedYear = '';
    let importQuotas = [];
    let search = '';
    let loading = false;
    let perPage = 10;
    let currentPage = 1;
    let pagination = {};

    let filters = {
        sort_direction: 'desc',
        product_id: '',
    };

    let showCreateForm = false;
    let showEditForm = false;
    let editingImportQuota = null;

    function formatQuantityWithUnit(product, value) {
        if (value === null || value === undefined || value === '') {
            return '—';
        }

        const unit = product?.unit_of_measurement?.trim();
        return unit ? `${value} ${unit}` : `${value}`;
    }

    function formatPercentage(value) {
        if (value === null || value === undefined) {
            return '—';
        }

        return `${value}%`;
    }

    function isOverConsumed(importQuota) {
        return importQuota?.is_over_consumed === true;
    }

    $: exportImportQuotas = importQuotas.map((importQuota) => ({
        id: importQuota.id,
        year: importQuota.year,
        product_name: importQuota.product?.name || '',
        product_code: importQuota.product?.code || '',
        max_quota: formatQuantityWithUnit(importQuota.product, importQuota.max_quota),
        consumption_quota: formatQuantityWithUnit(importQuota.product, importQuota.consumption_quota),
        consumption_percentage: formatPercentage(importQuota.consumption_percentage),
        is_over_consumed: isOverConsumed(importQuota) ? 'Yes' : 'No',
        created_at: importQuota.created_at,
        updated_at: importQuota.updated_at,
    }));

    function handleYearChange() {
        currentPage = 1;
        search = '';
        filters = {
            sort_direction: 'desc',
            product_id: '',
        };
        showCreateForm = false;
        showEditForm = false;
        editingImportQuota = null;
        importQuotas = [];
        pagination = {};

        if (selectedYear) {
            fetchImportQuotas();
        }
    }

    async function fetchImportQuotas() {
        if (!selectedYear) {
            return;
        }

        loading = true;

        try {
            const queryParams = {
                year: selectedYear,
                page: currentPage,
                per_page: perPage,
                search: search,
                sort_direction: filters.sort_direction,
            };

            if (filters.product_id) {
                queryParams.product_id = filters.product_id;
            }

            const response = await fetch(route('api.v1.admin.import-quotas.index', queryParams), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const data = await response.json();
            importQuotas = data.import_quotas || [];
            pagination = data.pagination || {};

            await tick();

            if (window.KTMenu) {
                window.KTMenu.init();
            }
        } catch (error) {
            console.error('Error fetching import quotas:', error);
        } finally {
            loading = false;
        }
    }

    function handleSearchFromComponent(event) {
        search = event.detail.value;
        currentPage = 1;
        fetchImportQuotas();
    }

    function goToPage(page) {
        if (page && page !== currentPage) {
            currentPage = page;
            fetchImportQuotas();
        }
    }

    function handlePerPageChange(newPerPage) {
        perPage = newPerPage;
        currentPage = 1;
        fetchImportQuotas();
    }

    function handleFiltersChange(event) {
        filters = event.detail;
        currentPage = 1;
        fetchImportQuotas();
    }

    function openFiltersDrawer() {
        const toggleButton = document.querySelector('[data-kt-drawer-toggle="#import_quotas_filters_drawer"]');
        if (toggleButton) {
            toggleButton.click();
        }
    }

    function handleCreateFormToggle() {
        showCreateForm = !showCreateForm;
        showEditForm = false;
        editingImportQuota = null;
    }

    function handleFormCreated() {
        showCreateForm = false;
        fetchImportQuotas();
    }

    function handleFormCanceled() {
        showCreateForm = false;
    }

    function handleEditFormToggle(importQuota = null) {
        if (importQuota?.id) {
            editingImportQuota = importQuota;
            showEditForm = true;
            showCreateForm = false;
        } else {
            showEditForm = false;
            editingImportQuota = null;
        }
    }

    function handleFormUpdated() {
        showEditForm = false;
        editingImportQuota = null;
        fetchImportQuotas();
    }

    function handleEditFormCanceled() {
        showEditForm = false;
        editingImportQuota = null;
    }

    async function handleDeleteImportQuota(importQuota) {
        const productName = importQuota.product?.name || 'this product';
        const confirmed = confirm(`Are you sure you want to delete the import quota for "${productName}" in ${selectedYear}?\n\nThis action cannot be undone.`);

        if (!confirmed) {
            return;
        }

        try {
            const response = await fetch(route('api.v1.admin.import-quotas.destroy', { importQuota: importQuota.id }), {
                method: 'DELETE',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
            });

            const data = await response.json();

            if (response.ok) {
                toast('Import quota deleted successfully', 'success');
                fetchImportQuotas();
            } else if (response.status === 422 && data.errors) {
                let errorMessage = 'Cannot delete import quota:\n';
                Object.entries(data.errors).forEach(([field, message]) => {
                    errorMessage += `• ${message}\n`;
                });
                toast(errorMessage, 'error');
            } else {
                toast(data.message || 'An error occurred while deleting the import quota.', 'error');
            }
        } catch (error) {
            console.error('Network error:', error);
            toast('Network error occurred. Please try again.', 'error');
        }
    }
</script>

<svelte:head>
    <title>Novonordisk supply chain management system - {pageTitle}</title>
</svelte:head>

<MainLayout {breadcrumbs} {pageTitle}>
    <div class="grid gap-5 lg:gap-7.5">
        <div class="kt-card kt-card-grid min-w-full overflow-hidden">
            <div class="kt-card-content p-4">
                <div class="flex flex-col gap-2 max-w-xs">
                    <select
                        id="import-quota-year-select"
                        class="kt-select"
                        bind:value={selectedYear}
                        on:change={handleYearChange}
                    >
                        <option value="">Select year...</option>
                        {#each yearOptions as year}
                            <option value={year}>{year}</option>
                        {/each}
                    </select>
                    <p class="text-xs text-muted-foreground">
                        Choose a year to view and manage product import quotas.
                    </p>
                </div>
            </div>
        </div>

        {#if selectedYear}
            <div class="kt-card kt-card-grid min-w-full overflow-hidden">
                <div class="kt-card w-full border-0">
                    <div class="kt-card-header">
                        <div class="kt-card-toolbar flex items-center justify-between w-full">
                            {#if !showCreateForm && !showEditForm}
                                <div class="flex items-center gap-2">
                                    <SearchBar
                                        bind:value={search}
                                        placeholder="Search products..."
                                        debounceMs={500}
                                        on:search={handleSearchFromComponent}
                                    />

                                    <button
                                        type="button"
                                        class="kt-btn kt-btn-sm kt-btn-ghost"
                                        on:click={openFiltersDrawer}
                                        title="Filter import quotas"
                                        aria-label="Filter import quotas"
                                    >
                                        <i class="fa-solid fa-filter"></i>
                                    </button>

                                    <ExportButton
                                        tableData={exportImportQuotas}
                                        headers={[
                                            { key: 'id', label: 'ID' },
                                            { key: 'year', label: 'Year' },
                                            { key: 'product_name', label: 'Product' },
                                            { key: 'product_code', label: 'Product Code' },
                                            { key: 'max_quota', label: 'Allowed' },
                                            { key: 'consumption_quota', label: 'Consumed' },
                                            { key: 'consumption_percentage', label: 'Consumption %' },
                                            { key: 'is_over_consumed', label: 'Over Consumed' },
                                            { key: 'created_at', label: 'Created' },
                                            { key: 'updated_at', label: 'Updated' },
                                        ]}
                                        filename="import-quotas-{selectedYear}"
                                        totalRecords={pagination?.total || 0}
                                        currentPerPage={perPage}
                                        filters={{ ...filters, year: selectedYear, search }}
                                    />
                                </div>

                                {#if hasPermission('import-quotas.store')}
                                    <button
                                        type="button"
                                        class="kt-btn kt-btn-sm kt-btn-primary"
                                        on:click={handleCreateFormToggle}
                                        title="Add import quota"
                                    >
                                        <i class="fa-solid fa-plus mr-1"></i>
                                        Add Import Quota
                                    </button>
                                {/if}
                            {:else if showCreateForm}
                                <button
                                    type="button"
                                    class="kt-btn kt-btn-sm kt-btn-secondary"
                                    on:click={handleCreateFormToggle}
                                    title="Cancel"
                                >
                                    <i class="fa-solid fa-arrow-left mr-1"></i>
                                    Cancel
                                </button>
                            {:else if showEditForm}
                                <button
                                    type="button"
                                    class="kt-btn kt-btn-sm kt-btn-secondary"
                                    on:click={handleEditFormToggle}
                                    title="Cancel"
                                >
                                    <i class="fa-solid fa-arrow-left mr-1"></i>
                                    Cancel
                                </button>
                            {/if}
                        </div>
                    </div>

                    {#if showCreateForm}
                        <div
                            class="kt-card-content p-4"
                            in:fly={{ x: '100%', duration: 750 }}
                        >
                            <CreateForm
                                year={selectedYear}
                                on:created={handleFormCreated}
                                on:canceled={handleFormCanceled}
                            />
                        </div>
                    {:else if showEditForm}
                        <div
                            class="kt-card-content p-4"
                            in:fly={{ x: '100%', duration: 750 }}
                        >
                            <EditForm
                                importQuota={editingImportQuota}
                                on:updated={handleFormUpdated}
                                on:canceled={handleEditFormCanceled}
                            />
                        </div>
                    {:else}
                        <div
                            class="kt-card-content p-0"
                            in:fly={{ x: '-100%', duration: 750 }}
                        >
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
                                            <th>
                                                <span class="kt-table-col whitespace-nowrap capitalize">Allowed</span>
                                            </th>
                                            <th>
                                                <span class="kt-table-col whitespace-nowrap capitalize">Consumed</span>
                                            </th>
                                            <th>
                                                <span class="kt-table-col whitespace-nowrap capitalize">Consumption %</span>
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
                                                    <td class="p-4">
                                                        <div class="kt-skeleton w-full h-4 rounded"></div>
                                                    </td>
                                                    <td class="p-4">
                                                        <div class="kt-skeleton w-full h-4 rounded"></div>
                                                    </td>
                                                    <td class="p-4">
                                                        <div class="kt-skeleton w-full h-4 rounded"></div>
                                                    </td>
                                                    <td class="p-4">
                                                        <div class="kt-skeleton w-full h-4 rounded"></div>
                                                    </td>
                                                    <td class="p-4">
                                                        <div class="kt-skeleton w-full h-4 rounded"></div>
                                                    </td>
                                                    <td class="p-4">
                                                        <div class="kt-skeleton w-8 h-8 rounded"></div>
                                                    </td>
                                                </tr>
                                            {/each}
                                        {:else if importQuotas.length === 0}
                                            <tr>
                                                <td colspan="6" class="p-10">
                                                    <div class="flex flex-col items-center justify-center text-center">
                                                        <div class="mb-4">
                                                            <i class="ki-filled ki-document text-4xl text-muted-foreground"></i>
                                                        </div>
                                                        <h3 class="text-lg font-semibold text-mono mb-2">No results found</h3>
                                                        <p class="text-sm text-secondary-foreground mb-4">
                                                            {search || filters.product_id ? 'No results match your search or filter criteria.' : `No import quotas found for ${selectedYear}.`}
                                                        </p>
                                                    </div>
                                                </td>
                                            </tr>
                                        {:else}
                                            {#each importQuotas as importQuota (importQuota.id)}
                                                {@const overConsumed = isOverConsumed(importQuota)}
                                                <tr class="hover:bg-muted {overConsumed ? 'bg-destructive/5' : ''}">
                                                    <td>
                                                        <span class="text-xs font-medium text-primary">#{importQuota.id}</span>
                                                    </td>
                                                    <td>
                                                        {#if importQuota.product}
                                                            <div class="flex items-center gap-3 max-content">
                                                                <img
                                                                    src={importQuota.product.image_url}
                                                                    alt={importQuota.product.name}
                                                                    class="w-[30px] h-[30px] rounded-lg object-cover"
                                                                />
                                                                <div class="flex flex-col gap-1">
                                                                    <span class="text-sm font-medium text-secondary-foreground">
                                                                        {importQuota.product.name}
                                                                    </span>
                                                                    {#if importQuota.product.code}
                                                                        <span class="text-xs text-muted-foreground">{importQuota.product.code}</span>
                                                                    {/if}
                                                                </div>
                                                            </div>
                                                        {:else}
                                                            <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">N/A</span>
                                                        {/if}
                                                    </td>
                                                    <td class:text-destructive={overConsumed} class:font-semibold={overConsumed}>
                                                        {formatQuantityWithUnit(importQuota.product, importQuota.max_quota)}
                                                    </td>
                                                    <td class:text-destructive={overConsumed} class:font-semibold={overConsumed}>
                                                        {formatQuantityWithUnit(importQuota.product, importQuota.consumption_quota)}
                                                    </td>
                                                    <td class:text-destructive={overConsumed} class:font-semibold={overConsumed}>
                                                        {formatPercentage(importQuota.consumption_percentage)}
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="kt-menu flex-inline" data-kt-menu="true">
                                                            <div class="kt-menu-item" data-kt-menu-item-offset="0, 10px" data-kt-menu-item-placement="bottom-end" data-kt-menu-item-placement-rtl="bottom-start" data-kt-menu-item-toggle="dropdown" data-kt-menu-item-trigger="click">
                                                                <button class="kt-menu-toggle kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" aria-label="Open actions menu">
                                                                    <i class="ki-filled ki-dots-vertical text-lg"></i>
                                                                </button>
                                                                <div class="kt-menu-dropdown kt-menu-default w-full max-w-[175px]">
                                                                    {#if hasPermission('import-quotas.update')}
                                                                        <div class="kt-menu-item">
                                                                            <button class="kt-menu-link" data-kt-menu-dismiss="true" on:click={() => handleEditFormToggle(importQuota)}>
                                                                                <span class="kt-menu-icon">
                                                                                    <i class="fa-solid fa-pen"></i>
                                                                                </span>
                                                                                <span class="kt-menu-title">Edit</span>
                                                                            </button>
                                                                        </div>
                                                                    {/if}
                                                                    {#if hasPermission('import-quotas.destroy')}
                                                                        <div class="kt-menu-item">
                                                                            <button class="kt-menu-link text-destructive" data-kt-menu-dismiss="true" on:click={() => handleDeleteImportQuota(importQuota)}>
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
        {/if}
    </div>

    <button style="display:none" data-kt-drawer-toggle="#import_quotas_filters_drawer" aria-label="Toggle filters drawer"></button>

    <FiltersDrawer {filters} on:filtersChanged={handleFiltersChange} />
</MainLayout>
