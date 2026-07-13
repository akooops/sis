<script>
    import { createEventDispatcher } from 'svelte';

    const dispatch = createEventDispatcher();

    const DEFAULT_SHIFT_COUNT = 3;

    const shiftOptions = [
        { shift_count: 3, label: '3 Shifts', description: 'Three-shift production day', background: '#005AD2' },
        { shift_count: 2, label: '2 Shifts', description: 'Two-shift production day', background: '#001965' },
        { shift_count: 1, label: '1 Shift', description: 'Single-shift production day', background: '#007AFF' },
        { shift_count: 0, label: 'Holiday', description: 'No production', background: '#efa7c0', border: '#efa7c0' },
    ];

    export let productionLine = null;
    export let selectedDates = [];
    export let overrideByDate = {};

    let shiftCount = '';
    let applyForAllSiteLines = false;
    let siteLinesScope = 'same_process';
    let errors = {};
    let loading = false;
    let lastDatesKey = '';

    $: siteName = productionLine?.production_site?.name || 'this site';
    $: processName = productionLine?.production_process?.name || 'the same process';

    $: sortedDates = [...selectedDates].sort();
    $: datesKey = sortedDates.join(',');

    $: if (datesKey && datesKey !== lastDatesKey) {
        lastDatesKey = datesKey;
        resetFormForDates();
    }

    $: hasMixedShifts = sortedDates.length > 1
        && new Set(sortedDates.map((date) => resolveShiftCount(date))).size > 1;

    $: rangeFromLabel = sortedDates.length > 0 ? formatDateLabel(sortedDates[0]) : '';
    $: rangeToLabel = sortedDates.length > 0 ? formatDateLabel(sortedDates[sortedDates.length - 1]) : '';

    $: rangeScheduleSummary = (() => {
        if (sortedDates.length === 0) {
            return '';
        }

        const counts = [...new Set(sortedDates.map((date) => resolveShiftCount(date)))];

        if (counts.length > 1) {
            return 'Mixed schedules';
        }

        const label = getShiftLabel(counts[0]);
        const allDefault = sortedDates.every((date) => isDefaultSchedule(date));

        return allDefault ? `${label} (default)` : label;
    })();

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

    function isDefaultSchedule(date) {
        return overrideByDate[date] === undefined;
    }

    function getShiftLabel(count) {
        return shiftOptions.find((option) => option.shift_count === count)?.label ?? 'Unknown';
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
        applyForAllSiteLines = false;
        siteLinesScope = 'same_process';

        if (sortedDates.length === 1) {
            shiftCount = String(resolveShiftCount(sortedDates[0]));
            return;
        }

        const counts = [...new Set(sortedDates.map((date) => resolveShiftCount(date)))];
        shiftCount = counts.length === 1 ? String(counts[0]) : '';
    }

    function handleDismiss() {
        dispatch('closed');
    }

    function closeDrawer() {
        document.querySelector('#shift_override_drawer [data-kt-drawer-dismiss="true"]')?.click();
    }

    async function handleSubmit() {
        if (!productionLine?.id) {
            errors = { general: 'Please select a production line first.' };
            return;
        }

        if (sortedDates.length === 0) {
            errors = { general: 'No dates selected.' };
            return;
        }

        if (shiftCount === '') {
            errors = { shift_count: 'Please select a shift schedule.' };
            return;
        }

        loading = true;
        errors = {};

        try {
            const formData = new FormData();
            formData.append('shift_count', shiftCount);
            sortedDates.forEach((date) => {
                formData.append('dates[]', date);
            });
            formData.append('apply_for_all_site_lines', applyForAllSiteLines ? '1' : '0');

            if (applyForAllSiteLines) {
                formData.append('site_lines_scope', siteLinesScope);
            }

            const response = await fetch(route('api.v1.admin.line-daily-shift-overrides.store', {
                productionLine: productionLine.id,
            }), {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: formData,
            });

            const data = await response.json();

            if (response.ok) {
                const label = getShiftLabel(Number(shiftCount));
                const dayLabel = sortedDates.length === 1 ? 'day' : 'days';
                const linesCount = data.affected_production_lines_count || 1;
                const linesLabel = linesCount === 1 ? 'line' : 'lines';

                let message = `Shift schedule updated for ${sortedDates.length} ${dayLabel} (${label}).`;

                if (linesCount > 1) {
                    message = `Shift schedule updated on ${linesCount} production ${linesLabel} for ${sortedDates.length} ${dayLabel} (${label}).`;
                }

                toast(message, 'success');
                dispatch('saved');
                closeDrawer(); // triggers handleDismiss via dismiss button
            } else if (data.errors) {
                errors = data.errors;
            } else {
                errors = { general: data.message || 'An error occurred while saving shift overrides.' };
            }
        } catch (error) {
            console.error('Network error:', error);
            errors = { general: 'Network error occurred. Please try again.' };
        } finally {
            loading = false;
        }
    }

</script>

<div
    class="hidden kt-drawer kt-drawer-end card flex-col max-w-[95%] w-[600px] top-5 bottom-5 end-5 rounded-xl border border-border overflow-hidden-x"
    data-kt-drawer="true"
    data-kt-drawer-container="body"
    id="shift_override_drawer"
>
    <div class="flex items-center justify-between gap-2.5 text-sm text-mono font-semibold px-5 py-2.5 border-b border-b-border">
        <div class="flex flex-col gap-0.5 min-w-0">
            <span>Daily Shift Override</span>
            {#if productionLine?.name}
                <span class="text-xs font-normal text-muted-foreground truncate">{productionLine.name}</span>
            {/if}
        </div>
        <button
            type="button"
            class="kt-btn kt-btn-sm kt-btn-icon kt-btn-dim shrink-0"
            data-kt-drawer-dismiss="true"
            aria-label="Close shift override drawer"
            on:click={handleDismiss}
        >
            <i class="ki-filled ki-cross"></i>
        </button>
    </div>

    <div class="kt-card-content flex flex-col gap-5 p-4 kt-scrollable-y-auto">
        {#if errors.general}
            <div class="p-3 bg-destructive/10 border border-destructive/20 rounded-lg">
                <p class="text-sm text-destructive">{errors.general}</p>
            </div>
        {/if}

        <div class="kt-card border border-border">
            <div class="kt-card-header border-b border-border">
                <span class="text-sm font-semibold text-mono">
                    {#if sortedDates.length === 1}
                        Selected Date
                    {:else if sortedDates.length > 1}
                        Selected Range ({sortedDates.length} days)
                    {:else}
                        Selected Dates
                    {/if}
                </span>
            </div>
            <div class="kt-card-content p-4">
                {#if sortedDates.length === 0}
                    <p class="text-sm text-muted-foreground">Click or drag days on the calendar to select dates.</p>
                {:else}
                    <div class="p-3 bg-muted/30 border border-border rounded-lg space-y-2">
                        {#if sortedDates.length === 1}
                            <p class="text-sm font-medium text-secondary-foreground">{rangeFromLabel}</p>
                        {:else}
                            <div class="flex flex-col gap-1 text-sm font-medium text-secondary-foreground">
                                <span><span class="text-muted-foreground font-normal">From</span> {rangeFromLabel}</span>
                                <span><span class="text-muted-foreground font-normal">To</span> {rangeToLabel}</span>
                            </div>
                        {/if}
                    </div>
                {/if}
            </div>
        </div>

        <form on:submit|preventDefault={handleSubmit} class="space-y-5">
            <div class="kt-card border border-border">
                <div class="kt-card-header border-b border-border">
                    <span class="text-sm font-semibold text-mono">Shift Schedule</span>
                </div>
                <div class="kt-card-content p-4 space-y-3">
                    {#each shiftOptions as option (option.shift_count)}
                        <label
                            class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer hover:bg-muted/30 transition-colors"
                            class:border-primary={shiftCount === String(option.shift_count)}
                        >
                            <input
                                type="radio"
                                bind:group={shiftCount}
                                value={String(option.shift_count)}
                                class="shrink-0"
                                disabled={loading || sortedDates.length === 0}
                            />
                            <span
                                class="inline-block size-3 rounded-full border shrink-0"
                                style="background-color: {option.background}; border-color: {option.border ?? option.background};"
                            ></span>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium text-mono">{option.label}</div>
                                <div class="text-xs text-muted-foreground">{option.description}</div>
                            </div>
                        </label>
                    {/each}

                    {#if errors.shift_count}
                        <p class="text-sm text-destructive">{errors.shift_count}</p>
                    {/if}
                </div>
            </div>

            {#if productionLine?.production_site}
                <div class="kt-card border border-border">
                    <div class="kt-card-header border-b border-border">
                        <span class="text-sm font-semibold text-mono">Site-wide apply</span>
                    </div>
                    <div class="kt-card-content p-4 space-y-4">
                        <div class="flex items-center gap-2">
                            <input
                                class="kt-switch"
                                type="checkbox"
                                id="apply-for-all-site-lines"
                                bind:checked={applyForAllSiteLines}
                                disabled={loading || sortedDates.length === 0}
                            />
                            <label class="kt-label text-sm" for="apply-for-all-site-lines">
                                Apply for all lines on {siteName}
                            </label>
                        </div>

                        {#if applyForAllSiteLines}
                            <div class="space-y-3 pl-1">
                                <label
                                    class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer hover:bg-muted/30 transition-colors"
                                    class:border-primary={siteLinesScope === 'same_process'}
                                >
                                    <input
                                        type="radio"
                                        bind:group={siteLinesScope}
                                        value="same_process"
                                        class="shrink-0"
                                        disabled={loading}
                                    />
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm font-medium text-mono">Same process only</div>
                                        <div class="text-xs text-muted-foreground">
                                            Lines on {siteName} with process "{processName}"
                                        </div>
                                    </div>
                                </label>

                                <label
                                    class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer hover:bg-muted/30 transition-colors"
                                    class:border-primary={siteLinesScope === 'all_processes'}
                                >
                                    <input
                                        type="radio"
                                        bind:group={siteLinesScope}
                                        value="all_processes"
                                        class="shrink-0"
                                        disabled={loading}
                                    />
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm font-medium text-mono">All lines on site</div>
                                        <div class="text-xs text-muted-foreground">
                                            Every production line on {siteName}, regardless of process
                                        </div>
                                    </div>
                                </label>
                            </div>
                        {/if}

                        {#if errors.apply_for_all_site_lines}
                            <p class="text-sm text-destructive">{errors.apply_for_all_site_lines}</p>
                        {/if}
                        {#if errors.site_lines_scope}
                            <p class="text-sm text-destructive">{errors.site_lines_scope}</p>
                        {/if}
                    </div>
                </div>
            {/if}

            <div class="flex items-center justify-end gap-3 pt-2 border-t border-border">
                <button type="button" class="kt-btn kt-btn-secondary" on:click={closeDrawer} disabled={loading}>
                    Cancel
                </button>
                {#if hasPermission('line-daily-shift-overrides.store')}
                    <button
                        type="submit"
                        class="kt-btn kt-btn-primary"
                        disabled={loading || sortedDates.length === 0 || shiftCount === ''}
                    >
                        {#if loading}
                            <i class="fa-solid fa-spinner fa-spin mr-2"></i>
                            Saving...
                        {:else}
                            <i class="fa-solid fa-check mr-2"></i>
                            Save {sortedDates.length === 1 ? 'Override' : `Overrides (${sortedDates.length})`}
                        {/if}
                    </button>
                {/if}
            </div>
        </form>
    </div>
</div>
