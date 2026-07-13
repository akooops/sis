<script>
    import MainLayout from '../../Shared/Layouts/MainLayout.svelte';
    import Pagination from '../../Shared/Utils/Pagination.svelte';
    import SearchBar from '../../Shared/Utils/Forms/SearchBar.svelte';
    import ExportButton from '../../Shared/Utils/ExportButton.svelte';
    import FiltersDrawer from './FiltersDrawer.svelte';
    import ViewDrawer from './ViewDrawer.svelte';
    import EditForm from './EditForm.svelte';
    import ApproveForm from './ApproveForm.svelte';
    import SubmitToQpForm from './SubmitToQpForm.svelte';
    import { putRecordAction, formatWarehouseStatus, getWarehouseStatusBadgeClass } from '../Warehouse/recordActions.js';
    import { onMount, tick } from 'svelte';
    import { fly } from 'svelte/transition';

    const breadcrumbs = [{ title: 'QA Records', url: route('web.admin.qa-records.index'), active: false }, { title: 'Index', url: route('web.admin.qa-records.index'), active: true }];
    const pageTitle = 'QA Records';

    let qaRecords = [], search = '', loading = true, perPage = 10, currentPage = 1, pagination = {};
    let filters = { sort_direction: 'desc', status: '', product_id: '', w_h_good_receipt_id: '' };
    let activeForm = null, activeRecord = null, selectedQaRecord = null;

    function fmt(product, value) {
        if (value === null || value === undefined || value === '') return null;
        const unit = product?.unit_of_measurement?.trim();
        return unit ? `${value} ${unit}` : `${value}`;
    }

    async function fetchRecords() {
        loading = true;
        try {
            const q = { page: currentPage, per_page: perPage, search, sort_direction: filters.sort_direction };
            if (filters.status) q.status = filters.status;
            if (filters.product_id) q.product_id = filters.product_id;
            if (filters.w_h_good_receipt_id) q.w_h_good_receipt_id = filters.w_h_good_receipt_id;
            const res = await fetch(route('api.v1.admin.qa-records.index', q), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const data = await res.json();
            qaRecords = data.q_a_records || [];
            pagination = data.pagination || {};
            await tick(); window.KTMenu?.init();
        } finally { loading = false; }
    }

    function openForm(type, record = null) { activeForm = type; activeRecord = record; }
    function closeForm() { activeForm = null; activeRecord = null; }
    function handleDone() { closeForm(); fetchRecords(); }
    function openViewDrawer(record) {
        selectedQaRecord = record;
        document.querySelector('[data-kt-drawer-toggle="#qa_record_view_drawer"]')?.click();
    }

    function handleRowClick(record) {
        openViewDrawer(record);
    }

    async function handleCancel(record) {
        if (!confirm(`Cancel QA record "${record.code || `#${record.id}`}"?`)) return;
        const result = await putRecordAction('api.v1.admin.qa-records.cancel', { qaRecord: record.id }, null, 'QA record cancelled');
        if (result.ok) fetchRecords();
    }

    onMount(() => fetchRecords());
</script>

<svelte:head><title>Novonordisk supply chain management system - {pageTitle}</title></svelte:head>

<MainLayout {breadcrumbs} {pageTitle}>
    <div class="grid gap-5 lg:gap-7.5">
        <div class="kt-card kt-card-grid min-w-full overflow-hidden">
            <div class="kt-card w-full border-0">
                <div class="kt-card-header">
                    <div class="kt-card-toolbar flex items-center justify-between w-full">
                        {#if !activeForm}
                            <div class="flex items-center gap-2">
                                <SearchBar bind:value={search} placeholder="Search QA records..." debounceMs={500} on:search={() => { currentPage = 1; fetchRecords(); }} />
                                <button type="button" class="kt-btn kt-btn-sm kt-btn-ghost" on:click={() => document.querySelector('[data-kt-drawer-toggle="#qa_records_filters_drawer"]')?.click()}><i class="fa-solid fa-filter"></i></button>
                                <ExportButton tableData={qaRecords} headers={[{ key: 'id', label: 'ID' }, { key: 'code', label: 'Code' }, { key: 'status', label: 'Status' }]} filename="qa-records" totalRecords={pagination?.total || 0} currentPerPage={perPage} {filters} />
                            </div>
                        {:else}
                            <button type="button" class="kt-btn kt-btn-sm kt-btn-secondary" on:click={closeForm}><i class="fa-solid fa-arrow-left mr-1"></i>Back</button>
                        {/if}
                    </div>
                </div>

                {#if activeForm === 'edit'}
                    <div class="kt-card-content p-4" in:fly={{ x: '100%', duration: 750 }}><EditForm qaRecord={activeRecord} on:updated={handleDone} on:documentDeleted={fetchRecords} on:canceled={closeForm} /></div>
                {:else if activeForm === 'approve'}
                    <div class="kt-card-content p-4" in:fly={{ x: '100%', duration: 750 }}><ApproveForm qaRecord={activeRecord} on:approved={handleDone} on:canceled={closeForm} /></div>
                {:else if activeForm === 'submitToQp'}
                    <div class="kt-card-content p-4" in:fly={{ x: '100%', duration: 750 }}><SubmitToQpForm qaRecord={activeRecord} on:submitted={handleDone} on:canceled={closeForm} /></div>
                {:else}
                    <div class="kt-card-content p-0" in:fly={{ x: '-100%', duration: 750 }}>
                        <div class="kt-scrollable-x-auto kt-card-table">
                            <table class="kt-table kt-table-auto kt-table-border text-sm">
                                <thead>
                                    <tr>
                                        <th style="width: 115px;"><span class="kt-table-col whitespace-nowrap capitalize">ID</span></th>
                                        <th><span class="kt-table-col whitespace-nowrap capitalize">Code</span></th>
                                        <th><span class="kt-table-col whitespace-nowrap capitalize">Product</span></th>
                                        <th><span class="kt-table-col whitespace-nowrap capitalize">Approved</span></th>
                                        <th><span class="kt-table-col whitespace-nowrap capitalize">Status</span></th>
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
                                    {:else if qaRecords.length === 0}
                                        <tr>
                                            <td colspan="6" class="p-10">
                                                <div class="flex flex-col items-center justify-center text-center">
                                                    <i class="fa-solid fa-clipboard-check text-4xl text-muted-foreground mb-4"></i>
                                                    <h3 class="text-lg font-semibold text-mono mb-2">No results found</h3>
                                                    <p class="text-sm text-secondary-foreground">No QA records match your criteria.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    {:else}
                                        {#each qaRecords as record}
                                        <tr class="hover:bg-muted cursor-pointer">
                                            <td on:click={() => handleRowClick(record)}><span class="text-xs font-medium text-primary">#{record.id}</span></td>
                                            <td on:click={() => handleRowClick(record)}>
                                                {#if record.code}
                                                    <span class="text-sm font-medium text-mono">{record.code}</span>
                                                {:else}
                                                    <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs">N/A</span>
                                                {/if}
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
                                                {#if fmt(record.product, record.quantity_approved)}
                                                    <span class="text-sm font-medium">{fmt(record.product, record.quantity_approved)}</span>
                                                {:else}
                                                    <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs">N/A</span>
                                                {/if}
                                            </td>
                                            <td on:click={() => handleRowClick(record)}>
                                                <span class="kt-badge kt-badge-outline {getWarehouseStatusBadgeClass(record.status)} text-xs font-medium capitalize">{formatWarehouseStatus(record.status)}</span>
                                            </td>
                                            <td class="text-center">
                                                <div class="kt-menu flex-inline" data-kt-menu="true">
                                                    <div class="kt-menu-item" data-kt-menu-item-offset="0, 10px" data-kt-menu-item-placement="bottom-end" data-kt-menu-item-toggle="dropdown" data-kt-menu-item-trigger="click">
                                                        <button class="kt-menu-toggle kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" aria-label="Open actions menu"><i class="ki-filled ki-dots-vertical text-lg"></i></button>
                                                        <div class="kt-menu-dropdown kt-menu-default w-full max-w-[175px]">
                                                            <div class="kt-menu-item"><button class="kt-menu-link" data-kt-menu-dismiss="true" on:click={() => openViewDrawer(record)}><span class="kt-menu-icon"><i class="fa-solid fa-eye"></i></span><span class="kt-menu-title">View</span></button></div>
                                                            {#if hasPermission('qa-records.update') && record.can_be_updated}<div class="kt-menu-item"><button class="kt-menu-link" data-kt-menu-dismiss="true" on:click={() => openForm('edit', record)}><span class="kt-menu-icon"><i class="fa-solid fa-pen"></i></span><span class="kt-menu-title">Edit</span></button></div>{/if}
                                                            {#if hasPermission('qa-records.approve') && record.can_be_approved}<div class="kt-menu-item"><button class="kt-menu-link" data-kt-menu-dismiss="true" on:click={() => openForm('approve', record)}><span class="kt-menu-icon"><i class="fa-solid fa-check"></i></span><span class="kt-menu-title">Approve</span></button></div>{/if}
                                                            {#if hasPermission('qa-records.submit-to-qp') && record.can_be_submitted_to_qp}<div class="kt-menu-item"><button class="kt-menu-link" data-kt-menu-dismiss="true" on:click={() => openForm('submitToQp', record)}><span class="kt-menu-icon"><i class="fa-solid fa-arrow-right"></i></span><span class="kt-menu-title">Submit to QP</span></button></div>{/if}
                                                            {#if hasPermission('qa-records.cancel') && record.can_be_cancelled}<div class="kt-menu-item"><button class="kt-menu-link text-destructive" data-kt-menu-dismiss="true" on:click={() => handleCancel(record)}><span class="kt-menu-icon"><i class="fa-solid fa-ban"></i></span><span class="kt-menu-title">Cancel</span></button></div>{/if}
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
                        {#if pagination?.total > 0}<Pagination {pagination} {perPage} onPageChange={(p) => { currentPage = p; fetchRecords(); }} onPerPageChange={(pp) => { perPage = pp; currentPage = 1; fetchRecords(); }} />{/if}
                    </div>
                {/if}
            </div>
        </div>
    </div>
    <button style="display:none" data-kt-drawer-toggle="#qa_records_filters_drawer"></button>
    <button style="display:none" data-kt-drawer-toggle="#qa_record_view_drawer"></button>
    <FiltersDrawer {filters} on:filtersChanged={(e) => { filters = e.detail; currentPage = 1; fetchRecords(); }} />
    <ViewDrawer {selectedQaRecord} />
</MainLayout>
