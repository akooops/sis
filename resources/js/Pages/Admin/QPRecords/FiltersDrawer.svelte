<script>
    import { createEventDispatcher } from 'svelte';
    import Select2 from '../../Shared/Utils/Forms/Select2.svelte';

    const dispatch = createEventDispatcher();

    export let filters = {
        sort_direction: 'desc',
        status: '',
        product_id: '',
        w_h_good_receipt_id: '',
    };

    let tempFilters = { ...filters };
    let productSelectComponent;
    let whGoodReceiptSelectComponent;

    function handleProductChange(event) {
        tempFilters.product_id = event.detail.value || '';
    }

    function handleWhGoodReceiptChange(event) {
        tempFilters.w_h_good_receipt_id = event.detail.value || '';
    }

    function applyFilters() {
        filters = { ...tempFilters };
        dispatch('filtersChanged', filters);
        closeDrawer();
    }

    function resetFilters() {
        tempFilters = {
            sort_direction: 'desc',
            status: '',
            product_id: '',
            w_h_good_receipt_id: '',
        };
        filters = { ...tempFilters };

        productSelectComponent?.setValue('');
        whGoodReceiptSelectComponent?.setValue('');

        dispatch('filtersChanged', filters);
        closeDrawer();
    }

    function closeDrawer() {
        document.querySelector('[data-kt-drawer-dismiss="#qp_records_filters_drawer"]')?.click();
    }
</script>

<div class="hidden kt-drawer kt-drawer-end card flex-col max-w-[90%] w-[450px] top-5 bottom-5 end-5 rounded-xl border border-border" data-kt-drawer="true" data-kt-drawer-container="body" id="qp_records_filters_drawer">
    <div class="flex items-center justify-between gap-2.5 text-sm text-mono font-semibold px-5 py-2.5 border-b border-b-border">
        <div class="flex items-center gap-2">Filter QP Records</div>
        <button class="kt-btn kt-btn-sm kt-btn-icon kt-btn-dim shrink-0" data-kt-drawer-dismiss="true" on:click={closeDrawer}>
            <i class="ki-filled ki-cross"></i>
        </button>
    </div>

    <div class="kt-card-content flex flex-col space-y-3 p-4 pt-2 kt-scrollable-y-auto">
        <div>
            <label class="text-sm font-semibold text-mono">Status</label>
            <select bind:value={tempFilters.status} class="kt-select">
                <option value="">All (excl. cancelled)</option>
                <option value="pending">Pending</option>
                <option value="released">Released</option>
                <option value="partially_released">Partially Released</option>
                <option value="rejected">Rejected</option>
                <option value="cancelled">Cancelled</option>
            </select>
        </div>

        <div>
            <label class="text-sm font-semibold text-mono" for="qp-product-filter">Product</label>
            <Select2
                bind:this={productSelectComponent}
                id="qp-product-filter"
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
            <label class="text-sm font-semibold text-mono" for="qp-wh-filter">WH Good Receipt</label>
            <Select2
                bind:this={whGoodReceiptSelectComponent}
                id="qp-wh-filter"
                placeholder="Search and select WH good receipt..."
                value={tempFilters.w_h_good_receipt_id}
                on:change={handleWhGoodReceiptChange}
                ajax={{
                    url: route('api.v1.admin.wh-good-receipts.index'),
                    dataType: 'json',
                    delay: 300,
                    data: function (params) {
                        return { search: params.term, per_page: 10 };
                    },
                    processResults: function (data) {
                        return {
                            results: data.w_h_good_receipts.map((receipt) => ({
                                id: receipt.id,
                                text: receipt.code || `#${receipt.id}`,
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
