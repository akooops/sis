<script>
    import { createEventDispatcher } from 'svelte';
    import Select2 from '../../Shared/Utils/Forms/Select2.svelte';

    const dispatch = createEventDispatcher();

    const DEFAULT_SHIFT_COUNT = 3;

    const shiftOptions = [
        { shift_count: 3, label: '3 Shifts' },
        { shift_count: 2, label: '2 Shifts' },
        { shift_count: 1, label: '1 Shift' },
        { shift_count: 0, label: 'Holiday' },
    ];

    export let productionLine = null;
    export let selectedDates = [];
    export let planByDate = {};
    export let overrideByDate = {};

    let productId = '';
    let quantity = '';
    let maxCapacity = null;
    let errors = {};
    let loading = false;
    let loadingCapacity = false;
    let lastDatesKey = '';

    $: sortedDates = [...selectedDates].sort();
    $: datesKey = sortedDates.join(',');
    $: planDate = sortedDates.length === 1 ? sortedDates[0] : '';
    $: existingPlan = planDate ? planByDate[planDate] : null;
    $: shiftCount = planDate ? resolveShiftCount(planDate) : DEFAULT_SHIFT_COUNT;
    $: shiftLabel = shiftOptions.find((option) => option.shift_count === shiftCount)?.label ?? 'Unknown';

    $: if (datesKey && datesKey !== lastDatesKey) {
        lastDatesKey = datesKey;
        resetFormForDates();
    }

    $: if (productId && planDate && productionLine?.id) {
        fetchMaxCapacity();
    }

    function isDefaultHoliday(date) {
        const day = new Date(`${date}T00:00:00`).getDay();

        return day === 5 || day === 6;
    }

    function resolveShiftCount(date) {
        if (overrideByDate[date] !== undefined) {
            return overrideByDate[date].shift_count;
        }

        return isDefaultHoliday(date) ? 0 : DEFAULT_SHIFT_COUNT;
    }

    function formatDateLabel(date) {
        const parsed = new Date(`${date}T00:00:00`);

        return parsed.toLocaleDateString(undefined, {
            weekday: 'short',
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        });
    }

    function resetFormForDates() {
        errors = {};

        if (existingPlan) {
            productId = existingPlan.product_id || '';
            quantity = String(existingPlan.quantity ?? '');
            maxCapacity = existingPlan.max_capacity ?? null;
            return;
        }

        productId = '';
        quantity = '';
        maxCapacity = null;
    }

    async function fetchMaxCapacity() {
        if (!productionLine?.id || !productId || !planDate) {
            return;
        }

        loadingCapacity = true;

        try {
            const endDate = new Date(`${planDate}T00:00:00`);
            endDate.setDate(endDate.getDate() + 1);
            const end = endDate.toISOString().slice(0, 10);

            const url = new URL(
                route('api.v1.admin.line-products.index', { productionLine: productionLine.id }),
                window.location.origin,
            );
            url.searchParams.set('start', planDate);
            url.searchParams.set('end', end);
            url.searchParams.set('product_id', productId);

            const response = await fetch(url.toString(), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            const data = await response.json();

            if (response.ok) {
                maxCapacity = data.days?.[0]?.daily_capacity ?? null;
            }
        } catch (error) {
            console.error('Error loading capacity:', error);
        } finally {
            loadingCapacity = false;
        }
    }

    function handleDismiss() {
        dispatch('closed');
    }

    function closeDrawer() {
        document.querySelector('#plan_edit_drawer [data-kt-drawer-dismiss="true"]')?.click();
    }

    async function handleSubmit() {
        if (!productionLine?.id || !planDate) {
            errors = { general: 'Please select a production line and day first.' };
            return;
        }

        if (shiftCount === 0) {
            errors = { general: 'Cannot plan production on a holiday (0 shifts).' };
            return;
        }

        if (!productId) {
            errors = { product_id: 'Please select a product.' };
            return;
        }

        if (quantity === '' || Number(quantity) <= 0) {
            errors = { quantity: 'Quantity must be greater than zero.' };
            return;
        }

        loading = true;
        errors = {};

        try {
            const formData = new FormData();
            formData.append('plan_date', planDate);
            formData.append('product_id', productId);
            formData.append('quantity', quantity);

            const isUpdate = !!existingPlan?.id;
            const url = isUpdate
                ? route('api.v1.admin.production-plans.update', { productionPlan: existingPlan.id })
                : route('api.v1.admin.production-plans.store', { productionLine: productionLine.id });

            const response = await fetch(url, {
                method: isUpdate ? 'PUT' : 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: formData,
            });

            const data = await response.json();

            if (response.ok) {
                toast(isUpdate ? 'Production plan updated.' : 'Production plan saved.', 'success');
                dispatch('saved');
                closeDrawer();
            } else if (data.errors) {
                errors = data.errors;
            } else {
                errors = { general: data.message || 'An error occurred while saving the plan.' };
            }
        } catch (error) {
            console.error('Network error:', error);
            errors = { general: 'Network error occurred. Please try again.' };
        } finally {
            loading = false;
        }
    }

    async function handleDelete() {
        if (!existingPlan?.id) {
            return;
        }

        if (!confirm('Remove this production plan for the selected day?')) {
            return;
        }

        loading = true;
        errors = {};

        try {
            const response = await fetch(route('api.v1.admin.production-plans.destroy', {
                productionPlan: existingPlan.id,
            }), {
                method: 'DELETE',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
            });

            const data = await response.json();

            if (response.ok) {
                toast('Production plan removed.', 'success');
                dispatch('saved');
                closeDrawer();
            } else {
                errors = { general: data.message || 'Failed to delete production plan.' };
            }
        } catch (error) {
            console.error('Network error:', error);
            errors = { general: 'Network error occurred. Please try again.' };
        } finally {
            loading = false;
        }
    }

    $: productAjax = productionLine?.id
        ? {
            url: route('api.v1.admin.line-products.index', { productionLine: productionLine.id }),
            dataType: 'json',
            delay: 300,
            data: (params) => ({
                search: params.term || '',
                per_page: 20,
                for_production_plan: 1,
            }),
            processResults: (data) => ({
                results: (data.line_products || []).map((lineProduct) => ({
                    id: lineProduct.product?.id,
                    text: lineProduct.product?.code
                        ? `${lineProduct.product.name} (${lineProduct.product.code})`
                        : lineProduct.product?.name || lineProduct.id,
                })).filter((item) => item.id),
            }),
            cache: true,
        }
        : null;
</script>

<div
    class="hidden kt-drawer kt-drawer-end card flex-col max-w-[95%] w-[600px] top-5 bottom-5 end-5 rounded-xl border border-border overflow-hidden-x"
    data-kt-drawer="true"
    data-kt-drawer-container="body"
    id="plan_edit_drawer"
>
    <div class="flex items-center justify-between gap-2.5 text-sm text-mono font-semibold px-5 py-2.5 border-b border-b-border">
        <div class="flex flex-col gap-0.5 min-w-0">
            <span>Production Plan</span>
            {#if productionLine?.name}
                <span class="text-xs font-normal text-muted-foreground truncate">{productionLine.name}</span>
            {/if}
        </div>
        <button
            type="button"
            class="kt-btn kt-btn-sm kt-btn-icon kt-btn-dim shrink-0"
            data-kt-drawer-dismiss="true"
            aria-label="Close plan drawer"
            on:click={handleDismiss}
        >
            <i class="ki-filled ki-cross"></i>
        </button>
    </div>

    <div class="kt-card-content flex flex-col gap-5 p-4 kt-scrollable-y-auto">
        {#if errors.general}
            <div class="text-sm text-destructive">{errors.general}</div>
        {/if}

        {#if planDate}
            <div class="rounded-lg border border-border p-4 flex flex-col gap-2">
                <span class="text-sm font-medium text-mono">{formatDateLabel(planDate)}</span>
                <span class="text-xs text-muted-foreground">Shift schedule: {shiftLabel}</span>
                {#if loadingCapacity}
                    <span class="text-xs text-muted-foreground">Loading max capacity…</span>
                {:else if maxCapacity !== null}
                    <span class="text-xs text-muted-foreground">Max capacity: {maxCapacity}</span>
                {/if}
            </div>
        {:else}
            <div class="text-sm text-muted-foreground">Select a single day on the calendar.</div>
        {/if}

        <div class="flex flex-col gap-2">
            <label class="text-sm font-medium text-mono" for="plan-product-select">Product</label>
            {#key productionLine?.id}
            <Select2
                id="plan-product-select"
                placeholder={productionLine?.id ? 'Select product on this line...' : 'Select a line first'}
                value={productId}
                disabled={!productionLine?.id || shiftCount === 0}
                minimumInputLength={0}
                on:change={(event) => { productId = event.detail.value || ''; }}
                ajax={productAjax}
            />
            {/key}
            {#if errors.product_id}
                <span class="text-xs text-destructive">{errors.product_id}</span>
            {/if}
        </div>

        <div class="flex flex-col gap-2">
            <label class="text-sm font-medium text-mono" for="plan-quantity-input">Quantity</label>
            <input
                id="plan-quantity-input"
                type="number"
                min="0"
                step="any"
                class="kt-input"
                bind:value={quantity}
                disabled={shiftCount === 0 || loading}
            />
            {#if errors.quantity}
                <span class="text-xs text-destructive">{errors.quantity}</span>
            {/if}
        </div>
    </div>

    <div class="flex items-center justify-between gap-2 px-5 py-4 border-t border-border">
        {#if existingPlan?.id}
            <button
                type="button"
                class="kt-btn kt-btn-sm kt-btn-outline kt-btn-destructive"
                disabled={loading}
                on:click={handleDelete}
            >
                Remove plan
            </button>
        {:else}
            <span></span>
        {/if}

        <div class="flex items-center gap-2">
            <button
                type="button"
                class="kt-btn kt-btn-sm kt-btn-outline"
                data-kt-drawer-dismiss="true"
                disabled={loading}
                on:click={handleDismiss}
            >
                Cancel
            </button>
            <button
                type="button"
                class="kt-btn kt-btn-sm kt-btn-primary"
                disabled={loading || !planDate || shiftCount === 0}
                on:click={handleSubmit}
            >
                {loading ? 'Saving…' : 'Save plan'}
            </button>
        </div>
    </div>
</div>
