<script>
    import MainLayout from '../../Shared/Layouts/MainLayout.svelte';
    import FullCalendar from '../../Shared/Utils/FullCalendar.svelte';
    import Select2 from '../../Shared/Utils/Forms/Select2.svelte';
    import ShiftEditDrawer from './ShiftEditDrawer.svelte';
    import PlanEditDrawer from './PlanEditDrawer.svelte';
    import { onMount, tick } from 'svelte';

    const NOVO_BLEU = '#001965';
    const DEFAULT_SHIFT_COUNT = 3;

    const shiftLegend = [
        { shift_count: 3, label: '3 Shifts', background: '#005AD2' },
        { shift_count: 2, label: '2 Shifts', background: '#007AFF' },
        { shift_count: 1, label: '1 Shift', background: '#001965' },
        { shift_count: 0, label: 'Holiday', background: '#efa7c0', border: '#efa7c0' },
    ];

    function getShiftColor(shiftCount) {
        const colors = {
            3: { background: '#001965', border: '#001965', label: '3 Shifts', text: '#ffffff' },
            2: { background: '#005AD2', border: '#005AD2', label: '2 Shifts', text: '#ffffff' },
            1: { background: '#007AFF', border: '#007AFF', label: '1 Shift', text: '#ffffff' },
            0: { background: '#efa7c0', border: '#efa7c0', label: 'Holiday', text: '#ffffff' },
        };

        return colors[shiftCount] ?? colors[3];
    }

    function normalizeCalendarDate(value) {
        if (!value) {
            return '';
        }

        if (typeof value === 'string') {
            return value.slice(0, 10);
        }

        if (value instanceof Date) {
            const year = value.getFullYear();
            const month = String(value.getMonth() + 1).padStart(2, '0');
            const day = String(value.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        return '';
    }

    function normalizeCalendarDates(values) {
        return [...new Set(values.map(normalizeCalendarDate).filter(Boolean))];
    }

    function eachDateInRange(start, end) {
        const dates = [];
        const current = new Date(`${start}T00:00:00`);
        const endDate = new Date(`${end}T00:00:00`);

        while (current < endDate) {
            const year = current.getFullYear();
            const month = String(current.getMonth() + 1).padStart(2, '0');
            const day = String(current.getDate()).padStart(2, '0');
            dates.push(`${year}-${month}-${day}`);
            current.setDate(current.getDate() + 1);
        }

        return dates;
    }

    function isDefaultHoliday(date) {
        const day = new Date(`${date}T00:00:00`).getDay();

        return day === 5 || day === 6;
    }

    function resolveShiftCount(date, overrideByDate) {
        if (overrideByDate[date] !== undefined) {
            return overrideByDate[date].shift_count;
        }

        return isDefaultHoliday(date) ? 0 : DEFAULT_SHIFT_COUNT;
    }

    function isDateInActivePeriod(date, periodStart, periodEnd) {
        if (!periodStart || !periodEnd) {
            return true;
        }

        return date >= periodStart && date < periodEnd;
    }

    function buildShiftCalendarEvents(overrideByDate, rangeStart, rangeEnd, periodStart, periodEnd) {
        const events = [];

        for (const date of eachDateInRange(rangeStart, rangeEnd)) {
            if (!isDateInActivePeriod(date, periodStart, periodEnd)) {
                continue;
            }

            const shiftCount = resolveShiftCount(date, overrideByDate);
            const colors = getShiftColor(shiftCount);

            events.push({
                id: date,
                start: date,
                title: colors.label,
                allDay: true,
                display: 'block',
                backgroundColor: colors.background,
                borderColor: colors.border,
                textColor: colors.text,
                extendedProps: {
                    shift_count: shiftCount,
                    has_override: overrideByDate[date] !== undefined,
                },
            });
        }

        return events;
    }

    function buildCapacityCalendarEvents(days = [], rangeStart, rangeEnd, periodStart, periodEnd) {
        const capacityByDate = Object.fromEntries(
            (days || []).map((day) => [day.date, day.daily_capacity]),
        );

        return eachDateInRange(rangeStart, rangeEnd).flatMap((date) => {
            if (!isDateInActivePeriod(date, periodStart, periodEnd)) {
                return [];
            }

            return [{
                id: `${date}-capacity`,
                start: date,
                title: capacityByDate[date] ?? '—',
                allDay: true,
                display: 'block',
                backgroundColor: NOVO_BLEU,
                borderColor: NOVO_BLEU,
                textColor: '#ffffff',
                extendedProps: {
                    daily_capacity: capacityByDate[date] ?? null,
                },
            }];
        });
    }

    function buildPlanCalendarEvents(plans = [], rangeStart, rangeEnd, periodStart, periodEnd) {
        const planByDateLocal = Object.fromEntries(
            (plans || []).map((plan) => [plan.plan_date, plan]),
        );

        return eachDateInRange(rangeStart, rangeEnd).flatMap((date) => {
            if (!isDateInActivePeriod(date, periodStart, periodEnd)) {
                return [];
            }

            const plan = planByDateLocal[date];
            const shiftCount = resolveShiftCount(date, overrideByDate);

            if (shiftCount === 0) {
                const colors = getShiftColor(0);

                return [{
                    id: `${date}-holiday`,
                    start: date,
                    title: colors.label,
                    allDay: true,
                    display: 'block',
                    backgroundColor: colors.background,
                    borderColor: colors.border,
                    textColor: colors.text,
                    extendedProps: { shift_count: 0, is_holiday: true },
                }];
            }

            if (!plan) {
                return [{
                    id: `${date}-empty`,
                    start: date,
                    title: 'No plan',
                    allDay: true,
                    display: 'block',
                    backgroundColor: '#f4f6fa',
                    borderColor: '#d1d5db',
                    textColor: '#6b7280',
                    extendedProps: { has_plan: false },
                }];
            }

            const productLabel = plan.product?.code || plan.product?.name || 'Product';

            return [{
                id: plan.id,
                start: date,
                title: `${productLabel} · ${plan.quantity}`,
                allDay: true,
                display: 'block',
                backgroundColor: NOVO_BLEU,
                borderColor: NOVO_BLEU,
                textColor: '#ffffff',
                extendedProps: {
                    has_plan: true,
                    plan,
                },
            }];
        });
    }

    function buildPlansFetchUrl() {
        const url = new URL(
            route('api.v1.admin.production-plans.index', { productionLine: productionLineId }),
            window.location.origin,
        );

        url.searchParams.set('start', visibleRange.start);
        url.searchParams.set('end', visibleRange.end);

        if (productId) {
            url.searchParams.set('product_id', productId);
        }

        return url.toString();
    }

    function buildCapacityFetchUrl() {
        const url = new URL(
            route('api.v1.admin.line-products.index', { productionLine: productionLineId }),
            window.location.origin,
        );

        url.searchParams.set('start', visibleRange.start);
        url.searchParams.set('end', visibleRange.end);
        url.searchParams.set('product_id', productId);

        return url.toString();
    }

    function buildShiftOverridesFetchUrl() {
        const url = new URL(
            route('api.v1.admin.line-daily-shift-overrides.index', { productionLine: productionLineId }),
            window.location.origin,
        );

        url.searchParams.set('start', visibleRange.start);
        url.searchParams.set('end', visibleRange.end);

        return url.toString();
    }

    const breadcrumbs = [
        {
            title: 'Planning Management',
            url: route('web.admin.line-daily-shift-overrides.index'),
            active: false,
        },
        {
            title: 'Daily Shift Overrides',
            url: route('web.admin.line-daily-shift-overrides.index'),
            active: true,
        },
    ];

    const pageTitle = 'Daily Shift Overrides';

    let productionLineId = '';
    let productId = '';
    let viewMode = 'shifts';
    let selectedProductionLine = null;
    let selectedProduct = null;
    let lineSelectComponent;
    let productSelectComponent;
    let calendarComponent;
    let calendarEvents = [];
    let loadingCalendar = false;
    let selectedDatesForDrawer = [];
    let selectedDatesForPlanDrawer = [];
    let overrideByDate = {};
    let planByDate = {};
    let neededStockByDate = [];
    let visibleRange = { start: '', end: '' };
    let activePeriod = { start: '', end: '' };

    $: isCapacityView = viewMode === 'capacity' && !!productId;
    $: isPlanView = viewMode === 'plans';
    $: calendarOptions = isCapacityView
        ? { selectable: false }
        : {
            selectable: true,
            selectMirror: true,
            unselectAuto: true,
            selectMinDistance: 4,
        };
    $: productAjax = productionLineId
        ? {
            url: route('api.v1.admin.line-products.index', { productionLine: productionLineId }),
            dataType: 'json',
            delay: 300,
            data: (params) => ({
                search: params.term || '',
                per_page: 20,
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

    async function fetchCalendarData() {
        if (!productionLineId || !visibleRange.start || !visibleRange.end) {
            calendarEvents = [];
            return;
        }

        const capacityView = viewMode === 'capacity' && !!productId;
        const planView = viewMode === 'plans';

        loadingCalendar = true;

        try {
            if (planView) {
                const [plansResponse, shiftsResponse] = await Promise.all([
                    fetch(buildPlansFetchUrl(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } }),
                    fetch(buildShiftOverridesFetchUrl(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } }),
                ]);

                const plansData = await plansResponse.json();
                const shiftsData = await shiftsResponse.json();

                if (plansResponse.ok && shiftsResponse.ok) {
                    selectedProductionLine = plansData.production_line;
                    selectedProduct = null;
                    overrideByDate = Object.fromEntries(
                        (shiftsData.line_daily_shift_overrides || []).map((override) => [override.date, override]),
                    );
                    planByDate = Object.fromEntries(
                        (plansData.production_plans || []).map((plan) => [plan.plan_date, plan]),
                    );
                    neededStockByDate = plansData.needed_stock_by_date || [];
                    calendarEvents = buildPlanCalendarEvents(
                        plansData.production_plans || [],
                        visibleRange.start,
                        visibleRange.end,
                        activePeriod.start,
                        activePeriod.end,
                    );
                } else {
                    calendarEvents = [];
                    toast(plansData.message || shiftsData.message || 'Failed to load production plan calendar.', 'error');
                }
            } else if (capacityView) {
                const response = await fetch(buildCapacityFetchUrl(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                const data = await response.json();

                if (response.ok) {
                    selectedProductionLine = data.production_line;
                    selectedProduct = data.line_product;
                    calendarEvents = buildCapacityCalendarEvents(
                        data.days || [],
                        visibleRange.start,
                        visibleRange.end,
                        activePeriod.start,
                        activePeriod.end,
                    );
                } else {
                    calendarEvents = [];
                    toast(data.message || 'Failed to load capacity calendar.', 'error');
                }
            } else {
                const response = await fetch(buildShiftOverridesFetchUrl(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                const data = await response.json();

                if (response.ok) {
                    selectedProductionLine = data.production_line;
                    selectedProduct = null;
                    overrideByDate = Object.fromEntries(
                        (data.line_daily_shift_overrides || []).map((override) => [override.date, override]),
                    );
                    calendarEvents = buildShiftCalendarEvents(
                        overrideByDate,
                        visibleRange.start,
                        visibleRange.end,
                        activePeriod.start,
                        activePeriod.end,
                    );
                } else {
                    calendarEvents = [];
                    toast(data.message || 'Failed to load shift calendar.', 'error');
                }
            }
        } catch (error) {
            console.error('Error loading calendar data:', error);
            calendarEvents = [];
            toast('Network error occurred while loading calendar.', 'error');
        } finally {
            loadingCalendar = false;
            await tick();
            calendarComponent?.updateSize();
        }
    }

    function handleLineChange(event) {
        productionLineId = event.detail.value || '';
        productId = '';
        viewMode = 'shifts';
        selectedProductionLine = null;
        selectedProduct = null;
        calendarEvents = [];
        visibleRange = { start: '', end: '' };
        activePeriod = { start: '', end: '' };

        productSelectComponent?.setValue?.('');

        if (productionLineId) {
            loadingCalendar = true;
        } else {
            loadingCalendar = false;
        }
    }

    function handleProductChange(event) {
        const nextProductId = event.detail?.value || '';
        productId = nextProductId;
        selectedProduct = null;
        calendarEvents = [];

        if (!productionLineId || !visibleRange.start || !visibleRange.end) {
            return;
        }

        if (viewMode === 'capacity' || viewMode === 'plans') {
            loadingCalendar = true;
            fetchCalendarData();
        }
    }

    function handleViewModeChange(mode) {
        viewMode = mode;

        if (mode === 'capacity' && !productId) {
            calendarEvents = [];
            return;
        }

        if (!productionLineId || !visibleRange.start || !visibleRange.end) {
            return;
        }

        loadingCalendar = true;
        fetchCalendarData();
    }

    function handleDatesSet(event) {
        const newRange = {
            start: event.detail.start.slice(0, 10),
            end: event.detail.end.slice(0, 10),
        };

        const view = event.detail.view;
        const newActivePeriod = view
            ? {
                start: normalizeCalendarDate(view.currentStart),
                end: normalizeCalendarDate(view.currentEnd),
            }
            : { start: '', end: '' };

        const rangeUnchanged = newRange.start === visibleRange.start && newRange.end === visibleRange.end;
        const periodUnchanged = newActivePeriod.start === activePeriod.start && newActivePeriod.end === activePeriod.end;

        activePeriod = newActivePeriod;

        if (rangeUnchanged && periodUnchanged) {
            return;
        }

        visibleRange = newRange;
        fetchCalendarData();
    }

    async function openPlanDrawer(dates) {
        if (!productionLineId || viewMode !== 'plans') {
            return;
        }

        const normalizedDates = normalizeCalendarDates(dates).filter((date) =>
            isDateInActivePeriod(date, activePeriod.start, activePeriod.end),
        );

        if (normalizedDates.length !== 1) {
            return;
        }

        selectedDatesForPlanDrawer = normalizedDates;
        await tick();

        document.querySelector('[data-kt-drawer-toggle="#plan_edit_drawer"]')?.click();
    }

    async function openShiftDrawer(dates) {
        if (!productionLineId || viewMode !== 'shifts') {
            return;
        }

        const normalizedDates = normalizeCalendarDates(dates).filter((date) =>
            isDateInActivePeriod(date, activePeriod.start, activePeriod.end),
        );

        if (normalizedDates.length === 0) {
            return;
        }

        selectedDatesForDrawer = normalizedDates;
        await tick();

        const toggleButton = document.querySelector('[data-kt-drawer-toggle="#shift_override_drawer"]');
        toggleButton?.click();
    }

    function handleDrawerClosed() {
        selectedDatesForDrawer = [];
    }

    function handlePlanDrawerClosed() {
        selectedDatesForPlanDrawer = [];
    }

    function handleDayClick(event) {
        if (viewMode === 'plans') {
            openPlanDrawer([event.detail.date]);
            return;
        }

        if (viewMode !== 'shifts') {
            return;
        }

        openShiftDrawer([event.detail.date]);
    }

    function handleEventClick(event) {
        const clickedDate = normalizeCalendarDate(
            event.detail.date || event.detail.event?.startStr || event.detail.event?.start,
        );

        if (viewMode === 'plans') {
            openPlanDrawer([clickedDate]);
            return;
        }

        if (viewMode !== 'shifts') {
            return;
        }

        openShiftDrawer([clickedDate]);
    }

    function handleSelect(event) {
        if (viewMode === 'plans') {
            const dates = eachDateInRange(event.detail.start, event.detail.end).filter((date) =>
                isDateInActivePeriod(date, activePeriod.start, activePeriod.end),
            );

            if (dates.length === 1) {
                openPlanDrawer(dates);
            }

            calendarComponent?.getCalendar()?.unselect();
            return;
        }

        if (viewMode !== 'shifts') {
            calendarComponent?.getCalendar()?.unselect();
            return;
        }

        const dates = eachDateInRange(event.detail.start, event.detail.end).filter((date) =>
            isDateInActivePeriod(date, activePeriod.start, activePeriod.end),
        );

        if (dates.length === 0) {
            calendarComponent?.getCalendar()?.unselect();
            return;
        }

        openShiftDrawer(dates);
        calendarComponent?.getCalendar()?.unselect();
    }

    function handleShiftSaved() {
        fetchCalendarData();
    }

    function handlePlanSaved() {
        fetchCalendarData();
    }

    $: activeNeededStockDays = neededStockByDate.filter((day) =>
        isDateInActivePeriod(day.date, activePeriod.start, activePeriod.end)
        && day.components?.length > 0,
    );
</script>

<svelte:head>
    <title>Novonordisk supply chain management system  - {pageTitle}</title>
</svelte:head>

<MainLayout {breadcrumbs} {pageTitle}>
    <div class="grid gap-5 lg:gap-7.5">
        <div class="kt-card border border-border">
            <div class="kt-card-header border-b border-border flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-col gap-1">
                    <span class="text-sm font-semibold text-mono">Filters</span>
                    <span class="text-xs text-muted-foreground">
                        Select a production line. Use Shifts to override schedules, Capacity to preview max output, or Production Plan to schedule finished products and components.
                    </span>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button
                        type="button"
                        class="kt-btn kt-btn-sm"
                        class:kt-btn-primary={viewMode === 'shifts'}
                        class:kt-btn-outline={viewMode !== 'shifts'}
                        on:click={() => handleViewModeChange('shifts')}
                    >
                        Shifts
                    </button>
                    <button
                        type="button"
                        class="kt-btn kt-btn-sm"
                        class:kt-btn-primary={viewMode === 'capacity'}
                        class:kt-btn-outline={viewMode !== 'capacity'}
                        on:click={() => handleViewModeChange('capacity')}
                    >
                        Capacity
                    </button>
                    <button
                        type="button"
                        class="kt-btn kt-btn-sm"
                        class:kt-btn-primary={viewMode === 'plans'}
                        class:kt-btn-outline={viewMode !== 'plans'}
                        on:click={() => handleViewModeChange('plans')}
                    >
                        Production Plan
                    </button>
                </div>
            </div>
            <div class="kt-card-content p-4 grid gap-4 lg:grid-cols-2">
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-mono" for="shift-override-production-line-select">
                        Production Line
                    </label>
                    <Select2
                        bind:this={lineSelectComponent}
                        id="shift-override-production-line-select"
                        placeholder="Search and select production line..."
                        value={productionLineId}
                        on:change={handleLineChange}
                        ajax={{
                            url: route('api.v1.admin.production-lines.index'),
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
                                    results: data.production_lines.map((line) => ({
                                        id: line.id,
                                        text: line.production_site?.name
                                            ? `${line.name} (${line.production_site.name})`
                                            : line.name,
                                    })),
                                };
                            },
                            cache: true,
                        }}
                    />
                </div>

                <div class="flex flex-col gap-2">
                    <label class="text-sm font-medium text-mono" for="shift-override-product-select">
                        Product
                        <span class="text-muted-foreground font-normal">
                            {#if viewMode === 'capacity'}(required){:else}(optional filter){/if}
                        </span>
                    </label>
                    {#key productionLineId}
                    <Select2
                        bind:this={productSelectComponent}
                        id="shift-override-product-select"
                        placeholder={productionLineId ? 'Filter by product on this line...' : 'Select a production line first'}
                        value={productId}
                        disabled={!productionLineId}
                        minimumInputLength={0}
                        on:change={handleProductChange}
                        ajax={productAjax}
                    />
                    {/key}
                </div>
            </div>
        </div>

        {#if productionLineId}
            <div class="kt-card border border-border">
                <div class="kt-card-header border-b border-border flex flex-wrap items-center justify-between gap-3">
                    <div class="flex flex-col gap-1">
                        <span class="text-sm font-semibold text-mono">
                            {#if isPlanView}
                                Production Plan Calendar
                            {:else if isCapacityView}
                                Capacity Calendar
                            {:else}
                                Shift Calendar
                            {/if}
                        </span>
                        {#if selectedProductionLine}
                            <span class="text-xs text-muted-foreground">
                                {selectedProductionLine.name}
                                {#if selectedProductionLine.production_site?.name}
                                    · {selectedProductionLine.production_site.name}
                                {/if}
                                {#if isCapacityView && selectedProduct?.product}
                                    · {selectedProduct.product.name}
                                {/if}
                            </span>
                        {:else if isPlanView}
                            <span class="text-xs text-muted-foreground">
                                Click a day to plan one product (finished or component). Quantity must respect that day shift-tier capacity.
                            </span>
                        {:else if isCapacityView}
                            <span class="text-xs text-muted-foreground">
                                Read-only view of daily max capacity for the selected product.
                            </span>
                        {:else}
                            <span class="text-xs text-muted-foreground">
                                Click or drag days to assign shifts. Weekdays default to 3 shifts; Fridays and Saturdays default to holidays.
                            </span>
                        {/if}
                    </div>

                    {#if viewMode === 'shifts'}
                        <div class="flex flex-wrap items-center gap-3">
                            {#each shiftLegend as item}
                                <div class="flex items-center gap-2">
                                    <span
                                        class="inline-block size-3 rounded-full border"
                                        style="background-color: {item.background}; border-color: {item.border ?? item.background};"
                                    ></span>
                                    <span class="text-xs text-muted-foreground">{item.label}</span>
                                </div>
                            {/each}
                        </div>
                    {/if}
                </div>

                <div class="kt-card-content p-4 min-h-[650px]">
                    {#if loadingCalendar}
                        <div class="grid grid-cols-4 gap-4">
                            {#each Array(16) as _}
                                <div class="kt-skeleton w-full h-24 rounded-lg"></div>
                            {/each}
                        </div>
                    {/if}

                    <div class:hidden={loadingCalendar}>
                        {#key productionLineId}
                            <FullCalendar
                                bind:this={calendarComponent}
                                events={calendarEvents}
                                options={calendarOptions}
                                themeColor={NOVO_BLEU}
                                height="650px"
                                on:dateClick={handleDayClick}
                                on:eventClick={handleEventClick}
                                on:select={handleSelect}
                                on:datesSet={handleDatesSet}
                            />
                        {/key}
                    </div>
                </div>

                {#if isPlanView && activeNeededStockDays.length > 0}
                    <div class="border-t border-border px-4 py-3">
                        <div class="flex flex-col gap-2">
                            <span class="text-sm font-semibold text-mono">Needed BOM stock (site, visible month)</span>
                            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                                {#each activeNeededStockDays as day}
                                    <div class="rounded-lg border border-border p-3">
                                        <div class="text-xs font-medium text-mono mb-2">{day.date}</div>
                                        <ul class="space-y-1">
                                            {#each day.components as component}
                                                <li class="text-xs text-muted-foreground flex justify-between gap-2">
                                                    <span>{component.product_name || component.product_id}</span>
                                                    <span class="font-medium text-mono">{component.quantity}</span>
                                                </li>
                                            {/each}
                                        </ul>
                                    </div>
                                {/each}
                            </div>
                        </div>
                    </div>
                {/if}
            </div>
        {/if}
    </div>

    <button style="display:none" data-kt-drawer-toggle="#shift_override_drawer" aria-label="Toggle shift override drawer"></button>
    <button style="display:none" data-kt-drawer-toggle="#plan_edit_drawer" aria-label="Toggle plan edit drawer"></button>

    <ShiftEditDrawer
        productionLine={selectedProductionLine || (productionLineId ? { id: productionLineId } : null)}
        selectedDates={selectedDatesForDrawer}
        {overrideByDate}
        on:saved={handleShiftSaved}
        on:closed={handleDrawerClosed}
    />

    <PlanEditDrawer
        productionLine={selectedProductionLine || (productionLineId ? { id: productionLineId } : null)}
        selectedDates={selectedDatesForPlanDrawer}
        {planByDate}
        {overrideByDate}
        on:saved={handlePlanSaved}
        on:closed={handlePlanDrawerClosed}
    />
</MainLayout>
