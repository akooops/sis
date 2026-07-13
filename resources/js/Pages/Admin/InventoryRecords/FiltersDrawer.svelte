<script>
    import { createEventDispatcher } from 'svelte';
    import Select2 from '../../Shared/Utils/Forms/Select2.svelte';

    const dispatch = createEventDispatcher();

    export let filters = {
        sort_direction: 'desc',
        type: '',
        product_id: '',
        production_site_id: '',
        q_p_record_id: '',
    };

    let tempFilters = { ...filters };
    let productSelectComponent;
    let productionSiteSelectComponent;
    let qpRecordSelectComponent;

    function handleProductChange(event) {
        tempFilters.product_id = event.detail.value || '';
    }

    function handleProductionSiteChange(event) {
        tempFilters.production_site_id = event.detail.value || '';
    }

    function handleQpRecordChange(event) {
        tempFilters.q_p_record_id = event.detail.value || '';
    }

    function applyFilters() {
        filters = { ...tempFilters };
        dispatch('filtersChanged', filters);
        closeDrawer();
    }

    function resetFilters() {
        tempFilters = {
            sort_direction: 'desc',
            type: '',
            product_id: '',
            production_site_id: '',
            q_p_record_id: '',
        };
        filters = { ...tempFilters };

        productSelectComponent?.setValue('');
        productionSiteSelectComponent?.setValue('');
        qpRecordSelectComponent?.setValue('');

        dispatch('filtersChanged', filters);
        closeDrawer();
    }

    function closeDrawer() {
        document.querySelector('[data-kt-drawer-dismiss="#inventory_records_filters_drawer"]')?.click();
    }
</script>

<div class="hidden kt-drawer kt-drawer-end card flex-col max-w-[90%] w-[450px] top-5 bottom-5 end-5 rounded-xl border border-border" data-kt-drawer="true" data-kt-drawer-container="body" id="inventory_records_filters_drawer">
    <div class="flex items-center justify-between gap-2.5 text-sm text-mono font-semibold px-5 py-2.5 border-b border-b-border">
        <div class="flex items-center gap-2">Filter Inventory Records</div>
        <button class="kt-btn kt-btn-sm kt-btn-icon kt-btn-dim shrink-0" data-kt-drawer-dismiss="true" on:click={closeDrawer}>
            <i class="ki-filled ki-cross"></i>
        </button>
    </div>

    <div class="kt-card-content flex flex-col space-y-3 p-4 pt-2 kt-scrollable-y-auto">
        <div>
            <label class="text-sm font-semibold text-mono">Type</label>
            <select bind:value={tempFilters.type} class="kt-select">
                <option value="">All</option>
                <option value="incoming">Incoming</option>
                <option value="consumption">Consumption</option>
                <option value="scrap">Scrap</option>
                <option value="expired">Expired</option>
                <option value="transfer">Transfer</option>
            </select>
        </div>

        <div>
            <label class="text-sm font-semibold text-mono" for="inventory-product-filter">Product</label>
            <Select2
                bind:this={productSelectComponent}
                id="inventory-product-filter"
                placeholder="Search and select product..."
                value={tempFilters.product_id}
                on:change={handleProductChange}
                ajax={{
                    url: route('api.v1.admin.products.index'),
                    dataType: 'json',
                    delay: 300,
                    data: function (params) {
                        return { search: params.term, per_page: 10 };
                    },
                    processResults: function (data) {
                        return {
                            results: data.products.map((product) => ({
                                id: product.id,
                                text: product.name,
                            })),
                        };
                    },
                    cache: true,
                }}
            />
        </div>

        <div>
            <label class="text-sm font-semibold text-mono" for="inventory-site-filter">Production Site</label>
            <Select2
                bind:this={productionSiteSelectComponent}
                id="inventory-site-filter"
                placeholder="Search and select production site..."
                value={tempFilters.production_site_id}
                on:change={handleProductionSiteChange}
                ajax={{
                    url: route('api.v1.admin.production-sites.index'),
                    dataType: 'json',
                    delay: 300,
                    data: function (params) {
                        return { search: params.term, per_page: 10 };
                    },
                    processResults: function (data) {
                        return {
                            results: data.production_sites.map((site) => ({
                                id: site.id,
                                text: site.name,
                            })),
                        };
                    },
                    cache: true,
                }}
            />
        </div>

        <div>
            <label class="text-sm font-semibold text-mono" for="inventory-qp-filter">QP Record</label>
            <Select2
                bind:this={qpRecordSelectComponent}
                id="inventory-qp-filter"
                placeholder="Search and select QP record..."
                value={tempFilters.q_p_record_id}
                on:change={handleQpRecordChange}
                ajax={{
                    url: route('api.v1.admin.qp-records.index'),
                    dataType: 'json',
                    delay: 300,
                    data: function (params) {
                        return { search: params.term, per_page: 10 };
                    },
                    processResults: function (data) {
                        return {
                            results: data.q_p_records.map((record) => ({
                                id: record.id,
                                text: record.code || `#${record.id}`,
                            })),
                        };
                    },
                    cache: true,
                }}
            />
        </div>

        <div>
            <label class="text-sm font-semibold text-mono">Sort Direction</label>
            <select bind:value={tempFilters.sort_direction} class="kt-select">
                <option value="desc">Newest First</option>
                <option value="asc">Oldest First</option>
            </select>
        </div>
    </div>

    <div class="flex items-center justify-between gap-2.5 px-5 py-2.5 border-t border-t-border">
        <button type="button" class="kt-btn kt-btn-md kt-btn-ghost" on:click={resetFilters}>
            <i class="fa-solid fa-rotate-left mr-1"></i>
            Reset
        </button>
        <button type="button" class="kt-btn kt-btn-md kt-btn-primary" on:click={applyFilters}>
            <i class="fa-solid fa-check mr-1"></i>
            Apply Filters
        </button>
    </div>
</div>
