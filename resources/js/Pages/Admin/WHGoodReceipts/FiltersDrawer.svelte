<script>
    import { createEventDispatcher } from 'svelte';
    import Select2 from '../../Shared/Utils/Forms/Select2.svelte';

    const dispatch = createEventDispatcher();

    export let filters = {
        sort_direction: 'desc',
        status: '',
        product_id: '',
        shipping_id: '',
        purchase_order_line_id: '',
    };

    let tempFilters = { ...filters };
    let productSelectComponent;
    let shippingSelectComponent;
    let purchaseOrderLineSelectComponent;

    function handleProductChange(event) {
        tempFilters.product_id = event.detail.value || '';
    }

    function handleShippingChange(event) {
        tempFilters.shipping_id = event.detail.value || '';
    }

    function handlePurchaseOrderLineChange(event) {
        tempFilters.purchase_order_line_id = event.detail.value || '';
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
            shipping_id: '',
            purchase_order_line_id: '',
        };
        filters = { ...tempFilters };

        productSelectComponent?.setValue('');
        shippingSelectComponent?.setValue('');
        purchaseOrderLineSelectComponent?.setValue('');

        dispatch('filtersChanged', filters);
        closeDrawer();
    }

    function closeDrawer() {
        document.querySelector('[data-kt-drawer-dismiss="#wh_good_receipts_filters_drawer"]')?.click();
    }
</script>

<div class="hidden kt-drawer kt-drawer-end card flex-col max-w-[90%] w-[450px] top-5 bottom-5 end-5 rounded-xl border border-border" data-kt-drawer="true" data-kt-drawer-container="body" id="wh_good_receipts_filters_drawer">
    <div class="flex items-center justify-between gap-2.5 text-sm text-mono font-semibold px-5 py-2.5 border-b border-b-border">
        <div class="flex items-center gap-2">Filter WH Good Receipts</div>
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
                <option value="received">Received</option>
                <option value="submitted_to_qc">Submitted to QC</option>
                <option value="cancelled">Cancelled</option>
            </select>
        </div>

        <div>
            <label class="text-sm font-semibold text-mono" for="wh-product-filter">Product</label>
            <Select2
                bind:this={productSelectComponent}
                id="wh-product-filter"
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
            <label class="text-sm font-semibold text-mono" for="wh-shipping-filter">Shipping</label>
            <Select2
                bind:this={shippingSelectComponent}
                id="wh-shipping-filter"
                placeholder="Search and select shipping..."
                value={tempFilters.shipping_id}
                on:change={handleShippingChange}
                ajax={{
                    url: route('api.v1.admin.shippings.index-all'),
                    dataType: 'json',
                    delay: 300,
                    data: function (params) {
                        return { search: params.term, per_page: 10 };
                    },
                    processResults: function (data) {
                        return {
                            results: data.shippings.map((shipping) => ({
                                id: shipping.id,
                                text: shipping.code || `#${shipping.id}`,
                            })),
                        };
                    },
                    cache: true,
                }}
            />
        </div>

        <div>
            <label class="text-sm font-semibold text-mono" for="wh-po-line-filter">Purchase Order Line</label>
            <Select2
                bind:this={purchaseOrderLineSelectComponent}
                id="wh-po-line-filter"
                placeholder="Search and select PO line..."
                value={tempFilters.purchase_order_line_id}
                on:change={handlePurchaseOrderLineChange}
                ajax={{
                    url: route('api.v1.admin.purchase-order-lines.index-all'),
                    dataType: 'json',
                    delay: 300,
                    data: function (params) {
                        return { search: params.term, per_page: 10 };
                    },
                    processResults: function (data) {
                        return {
                            results: data.purchase_order_lines.map((line) => ({
                                id: line.id,
                                text: line.code || line.line || `#${line.id}`,
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
