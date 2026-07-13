<script>
    import { createEventDispatcher } from 'svelte';
    import Select2 from '../../Shared/Utils/Forms/Select2.svelte';
    import QuantityInput from '../../Shared/Utils/Forms/QuantityInput.svelte';

    const dispatch = createEventDispatcher();

    export let filters = {
        sort_direction: 'desc',
        type: '',
        has_shelf_life: '',
        min_current_stock: '',
        max_current_stock: '',
        supplier_id: '',
    };

    let tempFilters = { ...filters };
    let supplierSelectComponent;

    function handleSupplierChange(event) {
        tempFilters.supplier_id = event.detail.value || '';
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
            has_shelf_life: '',
            min_current_stock: '',
            max_current_stock: '',
            supplier_id: '',
        };
        filters = { ...tempFilters };
        if (supplierSelectComponent) {
            supplierSelectComponent.setValue('');
        }
        dispatch('filtersChanged', filters);
        closeDrawer();
    }

    function closeDrawer() {
        const dismissButton = document.querySelector('[data-kt-drawer-dismiss="#filters_drawer"]');
        if (dismissButton) {
            dismissButton.click();
        }
    }
</script>

<div class="hidden kt-drawer kt-drawer-end card flex-col max-w-[90%] w-[450px] top-5 bottom-5 end-5 rounded-xl border border-border" data-kt-drawer="true" data-kt-drawer-container="body" id="filters_drawer">
    <div class="flex items-center justify-between gap-2.5 text-sm text-mono font-semibold px-5 py-2.5 border-b border-b-border">
        <div class="flex items-center gap-2">
            Filter Products
        </div>
        <button class="kt-btn kt-btn-sm kt-btn-icon kt-btn-dim shrink-0" data-kt-drawer-dismiss="true" on:click={closeDrawer}>
            <i class="ki-filled ki-cross"></i>
        </button>
    </div>

    <div class="kt-card-content flex flex-col space-y-3 p-4 pt-2 kt-scrollable-y-auto">
        <div>
            <label class="text-sm font-semibold text-mono">Type</label>
            <select bind:value={tempFilters.type} class="kt-select">
                <option value="">All</option>
                <option value="raw_material">Raw Material</option>
                <option value="finished_product">Finished Product</option>
                <option value="component">Component</option>
            </select>
        </div>

        <div>
            <label class="text-sm font-semibold text-mono">Shelf Life</label>
            <select bind:value={tempFilters.has_shelf_life} class="kt-select">
                <option value="">All</option>
                <option value="true">Has shelf life</option>
                <option value="false">No shelf life</option>
            </select>
        </div>

        <QuantityInput
            label="Min current stock"
            bind:value={tempFilters.min_current_stock}
            is_integer={false}
            placeholder="Minimum stock"
        />

        <QuantityInput
            label="Max current stock"
            bind:value={tempFilters.max_current_stock}
            is_integer={false}
            placeholder="Maximum stock"
        />

        <div>
            <label class="text-sm font-semibold text-mono" for="supplier-filter-select">Supplier</label>
            <Select2
                bind:this={supplierSelectComponent}
                id="supplier-filter-select"
                placeholder="Search and select supplier..."
                value={tempFilters.supplier_id}
                on:change={handleSupplierChange}
                ajax={{
                    url: route('api.v1.admin.suppliers.index'),
                    dataType: 'json',
                    delay: 300,
                    data: function(params) {
                        return {
                            search: params.term,
                            per_page: 10,
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data.suppliers.map(supplier => ({
                                id: supplier.id,
                                text: supplier.name,
                            }))
                        };
                    },
                    cache: true
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
        <button
            type="button"
            class="kt-btn kt-btn-md kt-btn-ghost"
            on:click={resetFilters}
        >
            <i class="fa-solid fa-rotate-left mr-1"></i>
            Reset
        </button>

        <div class="flex gap-2">
            <button
                type="button"
                class="kt-btn kt-btn-md kt-btn-primary"
                on:click={applyFilters}
            >
                <i class="fa-solid fa-check mr-1"></i>
                Apply Filters
            </button>
        </div>
    </div>
</div>
