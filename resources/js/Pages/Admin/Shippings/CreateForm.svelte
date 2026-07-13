<script>
    import Select2 from '../../Shared/Utils/Forms/Select2.svelte';
    import QuantityInput from '../../Shared/Utils/Forms/QuantityInput.svelte';
    import DocumentsInput from '../../Shared/Utils/Forms/DocumentsInput.svelte';
    import { suggestShippingDates } from './dateSuggestions.js';
    import ShippingDeliveryDates from './ShippingDeliveryDates.svelte';
    import { createEventDispatcher } from 'svelte';

    const dispatch = createEventDispatcher();

    export let purchaseOrderId = null;

    const emptyForm = () => ({
        purchase_order_line_id: '',
        quantity: '',
        mode_of_transportation: 'sea',
        carrier: '',
        tracking_number: '',
        notes: '',
        planned_delivery_at_customs: '',
        planned_delivery_at_site: '',
        actual_delivery_at_customs: '',
        actual_delivery_at_site: '',
        documents: [],
    });

    let form = emptyForm();
    let selectedLine = null;
    let errors = {};
    let loading = false;
    let linesLoading = false;
    let purchaseOrderLineSelectComponent;
    let lineOptions = [];

    const linesAjaxConfig = {
        url: route('api.v1.admin.purchase-order-lines.index-all'),
        dataType: 'json',
        delay: 300,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
        },
        data: function (params) {
            return {
                search: params.term || '',
                per_page: 20,
                status: 'confirmed',
                page: params.page || 1,
            };
        },
        processResults: function (data) {
            return {
                results: (data.purchase_order_lines || []).map((line) => ({
                    id: line.id,
                    text: `${line.code || line.line} — ${line.product?.name || 'N/A'}`,
                    is_unit_integer: line.product?.is_unit_integer,
                    unit_of_measurement: line.product?.unit_of_measurement,
                    supply_lead_time: line.product?.supply_lead_time,
                })),
                pagination: {
                    more: (data.pagination?.current_page || 1) < (data.pagination?.last_page || 1),
                },
            };
        },
        cache: true,
    };

    $: isUnitInteger = !!(selectedLine?.is_unit_integer);
    $: productUnit = selectedLine?.unit_of_measurement?.trim() || '';
    $: usePreloadedLines = !!purchaseOrderId;

    $: if (purchaseOrderId) {
        loadPurchaseOrderLines(purchaseOrderId);
    } else {
        lineOptions = [];
    }

    async function loadPurchaseOrderLines(poId) {
        linesLoading = true;

        try {
            const response = await fetch(route('api.v1.admin.purchase-order-lines.index-purchase-order', {
                purchaseOrder: poId,
                per_page: 100,
                status: 'confirmed',
                sort_direction: 'asc',
            }), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
            });

            if (response.ok) {
                const data = await response.json();
                lineOptions = (data.purchase_order_lines || []).map((line) => ({
                    id: line.id,
                    text: `${line.line} — ${line.product?.name || 'N/A'}`,
                    is_unit_integer: line.product?.is_unit_integer,
                    unit_of_measurement: line.product?.unit_of_measurement,
                    supply_lead_time: line.product?.supply_lead_time,
                }));
            } else {
                lineOptions = [];
            }
        } catch (error) {
            console.error('Error loading purchase order lines:', error);
            lineOptions = [];
        } finally {
            linesLoading = false;
        }
    }

    function handlePurchaseOrderLineChange(event) {
        form.purchase_order_line_id = event.detail.value || '';

        if (event.detail.data) {
            selectedLine = {
                is_unit_integer: event.detail.data.is_unit_integer,
                unit_of_measurement: event.detail.data.unit_of_measurement,
                supply_lead_time: event.detail.data.supply_lead_time,
            };
            applySuggestedDates();
        } else {
            selectedLine = null;
        }

        if (purchaseOrderLineSelectComponent) {
            purchaseOrderLineSelectComponent.setError(false);
        }
    }

    function applySuggestedDates() {
        if (!selectedLine) {
            return;
        }

        const suggested = suggestShippingDates(form.mode_of_transportation, selectedLine.supply_lead_time);
        form = { ...form, ...suggested };
    }

    function handleModeChange() {
        if (selectedLine) {
            applySuggestedDates();
        }
    }

    function handleDocumentsChange(event) {
        form.documents = event.detail.files || [];
    }

    function appendDocuments(formData) {
        form.documents.forEach((file) => {
            formData.append('documents[]', file);
        });
    }

    async function handleSubmit() {
        if (!form.purchase_order_line_id) {
            errors = { purchase_order_line_id: 'Please select a purchase order line.' };
            if (purchaseOrderLineSelectComponent) {
                purchaseOrderLineSelectComponent.setError(true);
            }
            return;
        }

        loading = true;
        errors = {};

        try {
            const formData = prepareFormData(form);
            appendDocuments(formData);

            const response = await fetch(route('api.v1.admin.shippings.store'), {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: formData,
            });

            const data = await response.json();

            if (response.ok) {
                form = emptyForm();
                selectedLine = null;
                errors = {};

                if (purchaseOrderLineSelectComponent) {
                    purchaseOrderLineSelectComponent.setValue('');
                    purchaseOrderLineSelectComponent.setError(false);
                }

                toast('Shipping created successfully', 'success');
                dispatch('created');
            } else if (data.errors) {
                errors = data.errors;
            } else {
                errors = { general: data.message || 'An error occurred while creating the shipping.' };
            }
        } catch (error) {
            console.error('Network error:', error);
            errors = { general: 'Network error occurred. Please try again.' };
        } finally {
            loading = false;
        }
    }

    function handleCancel() {
        form = emptyForm();
        selectedLine = null;
        errors = {};
        dispatch('canceled');
    }
</script>

<div class="space-y-6">
    <form on:submit|preventDefault={handleSubmit} class="space-y-5">
        {#if errors.general}
            <div class="p-3 bg-destructive/10 border border-destructive/20 rounded-lg">
                <p class="text-sm text-destructive">{errors.general}</p>
            </div>
        {/if}

        {#if errors.purchase_order_line}
            <div class="p-3 bg-destructive/10 border border-destructive/20 rounded-lg">
                <p class="text-sm text-destructive">{errors.purchase_order_line}</p>
            </div>
        {/if}

        <div class="kt-card border border-border">
            <div class="kt-card-header border-b border-border">
                <span class="text-sm font-semibold text-mono">Purchase Order Line</span>
            </div>
            <div class="kt-card-content p-4 space-y-2">
                {#if linesLoading}
                    <p class="text-sm text-muted-foreground">Loading confirmed lines...</p>
                {:else if usePreloadedLines && lineOptions.length === 0}
                    <p class="text-sm text-muted-foreground">No confirmed lines available for this purchase order.</p>
                {:else}
                    {#key purchaseOrderId}
                        {#if usePreloadedLines}
                            <Select2
                                bind:this={purchaseOrderLineSelectComponent}
                                placeholder="Select a confirmed line..."
                                value={form.purchase_order_line_id}
                                on:change={handlePurchaseOrderLineChange}
                                disabled={loading || linesLoading}
                                data={lineOptions}
                            />
                        {:else}
                            <Select2
                                bind:this={purchaseOrderLineSelectComponent}
                                placeholder="Search and select a confirmed line..."
                                value={form.purchase_order_line_id}
                                on:change={handlePurchaseOrderLineChange}
                                disabled={loading}
                                ajax={linesAjaxConfig}
                            />
                        {/if}
                    {/key}
                {/if}
                {#if errors.purchase_order_line_id}
                    <p class="text-sm text-destructive">{errors.purchase_order_line_id}</p>
                {/if}
            </div>
        </div>

        <div class="kt-card border border-border">
            <div class="kt-card-header border-b border-border">
                <span class="text-sm font-semibold text-mono">Shipping Details</span>
            </div>
            <div class="kt-card-content p-4 space-y-5">
                <QuantityInput
                    label="Quantity"
                    placeholder="Enter quantity..."
                    bind:value={form.quantity}
                    is_integer={isUnitInteger}
                    unit={productUnit}
                    disabled={loading || !form.purchase_order_line_id}
                    error={errors.quantity}
                    required={true}
                />

                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-mono" for="mode-of-transportation">Mode of Transportation</label>
                    <select id="mode-of-transportation" class="kt-select" bind:value={form.mode_of_transportation} on:change={handleModeChange} disabled={loading}>
                        <option value="sea">Sea</option>
                        <option value="air">Air</option>
                        <option value="local">Local</option>
                    </select>
                    {#if errors.mode_of_transportation}
                        <p class="text-sm text-destructive">{errors.mode_of_transportation}</p>
                    {/if}
                </div>

                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-mono" for="carrier">Carrier</label>
                    <input id="carrier" type="text" class="kt-input" placeholder="Enter carrier..." bind:value={form.carrier} disabled={loading} />
                    {#if errors.carrier}
                        <p class="text-sm text-destructive">{errors.carrier}</p>
                    {/if}
                </div>

                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-mono" for="tracking-number">Tracking Number</label>
                    <input id="tracking-number" type="text" class="kt-input" placeholder="Enter tracking number..." bind:value={form.tracking_number} disabled={loading} />
                    {#if errors.tracking_number}
                        <p class="text-sm text-destructive">{errors.tracking_number}</p>
                    {/if}
                </div>

                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-mono" for="shipping-notes">Notes</label>
                    <textarea id="shipping-notes" class="kt-textarea min-h-[90px]" placeholder="Enter notes..." bind:value={form.notes} disabled={loading}></textarea>
                    {#if errors.notes}
                        <p class="text-sm text-destructive">{errors.notes}</p>
                    {/if}
                </div>
            </div>
        </div>

        <div class="kt-card border border-border">
            <div class="kt-card-header border-b border-border">
                <span class="text-sm font-semibold text-mono">Delivery Dates</span>
            </div>
            <div class="kt-card-content p-4">
                {#key form.mode_of_transportation}
                <ShippingDeliveryDates
                    modeOfTransportation={form.mode_of_transportation}
                    disabled={loading}
                    {errors}
                    showHint={true}
                    bind:planned_delivery_at_customs={form.planned_delivery_at_customs}
                    bind:planned_delivery_at_site={form.planned_delivery_at_site}
                    bind:actual_delivery_at_customs={form.actual_delivery_at_customs}
                    bind:actual_delivery_at_site={form.actual_delivery_at_site}
                />
                {/key}
            </div>
        </div>

        <div class="kt-card border border-border">
            <div class="kt-card-header border-b border-border">
                <span class="text-sm font-semibold text-mono">Documents</span>
            </div>
            <div class="kt-card-content p-4">
                <DocumentsInput
                    bind:files={form.documents}
                    disabled={loading}
                    label=""
                    helpText="PDF and CSV files only. You can add multiple files."
                    error={errors.documents || errors['documents.0']}
                    on:change={handleDocumentsChange}
                />
            </div>
        </div>

        <div class="flex items-center justify-end gap-2.5">
            <button type="button" class="kt-btn kt-btn-secondary" on:click={handleCancel} disabled={loading}>Cancel</button>
            <button type="submit" class="kt-btn kt-btn-primary" disabled={loading || linesLoading}>
                {#if loading}<i class="fa-solid fa-spinner fa-spin mr-1"></i>{/if}
                Create Shipping
            </button>
        </div>
    </form>
</div>
