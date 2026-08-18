<script>
    /**
     * Generate a term of times in one go.
     *
     * A WEEKDAY MASK OVER A DATE RANGE, then a repeating window inside each day:
     * "Sundays and Tuesdays through December, 09:00 to 12:00, 60-minute tours, 15
     * minutes between" is three tours a day on two days a week. The old app could
     * only express one slot per day spanning the whole window, which meant a school
     * running three morning tours had to enter every one of them by hand.
     *
     * The preview count is worked out here from the same arithmetic the server
     * uses, so nobody presses Generate on a range they have misjudged. It is a
     * courtesy, not a guard — the server counts again, caps the total, and reports
     * how many already existed.
     */
    import Drawer from '@/components/ui/Drawer.svelte';
    import Field from '@/components/form/Field.svelte';
    import Input from '@/components/form/Input.svelte';
    import Button from '@/components/ui/Button.svelte';
    import Alert from '@/components/feedback/Alert.svelte';
    import DatePicker from '@/components/form/DatePicker.svelte';
    import { useForm } from '@/lib/api/useForm.svelte';
    import { toast } from '@/lib/toast';
    import { WEEKDAYS } from '@/lib/visitService';

    let {
        open = $bindable(false),
        serviceId = null,
        /** Pre-fills the step, so what gets generated matches what the visit advertises. */
        defaultMinutes = 60,
        onsaved,
    } = $props();

    const form = useForm({
        start_date: '',
        end_date: '',
        days_of_week: [],
        start_time: '09:00',
        end_time: '12:00',
        slot_minutes: defaultMinutes,
        break_minutes: 0,
        capacity: 5,
    });

    let lastKey = null;

    // Same last-key guard as SlotDrawer: reused, stays mounted, must not re-seed
    // itself while somebody is typing in it.
    $effect(() => {
        if (!open) {
            lastKey = null;

            return;
        }

        if (lastKey === serviceId) return;

        lastKey = serviceId;
        form.clearErrors();
        form.data.slot_minutes = defaultMinutes;
    });

    function toggleDay(value) {
        const days = form.data.days_of_week;

        form.data.days_of_week = days.includes(value) ? days.filter((d) => d !== value) : [...days, value].sort();
    }

    /** Minutes between the two clock times, or 0 when either is unreadable. */
    function windowMinutes() {
        const [sh, sm] = String(form.data.start_time).split(':').map(Number);
        const [eh, em] = String(form.data.end_time).split(':').map(Number);

        if ([sh, sm, eh, em].some((n) => Number.isNaN(n))) return 0;

        return eh * 60 + em - (sh * 60 + sm);
    }

    /** How many whole tours fit in one day's window, at this step. */
    const perDay = $derived.by(() => {
        const span = windowMinutes();
        const length = Number(form.data.slot_minutes) || 0;
        const step = length + (Number(form.data.break_minutes) || 0);

        if (span <= 0 || length <= 0 || step <= 0) return 0;

        let count = 0;

        for (let at = 0; at + length <= span; at += step) count++;

        return count;
    });

    /** How many matching days the range covers. */
    const matchingDays = $derived.by(() => {
        const { start_date: from, end_date: to, days_of_week: days } = form.data;

        if (!from || !to || days.length === 0) return 0;

        const start = new Date(`${from}T00:00:00`);
        const end = new Date(`${to}T00:00:00`);

        if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime()) || end < start) return 0;

        let count = 0;

        for (const day = new Date(start); day <= end; day.setDate(day.getDate() + 1)) {
            if (days.includes(day.getDay())) count++;
        }

        return count;
    });

    const total = $derived(perDay * matchingDays);

    async function submit(event) {
        event?.preventDefault();

        try {
            const res = await form.submit('post', route('api.v1.admin.visit-slots.bulk'), {
                transform: (data) => ({
                    ...data,
                    visit_service_id: serviceId,
                    slot_minutes: Number(data.slot_minutes) || 0,
                    break_minutes: Number(data.break_minutes) || 0,
                    capacity: Number(data.capacity) || 0,
                }),
            });

            if (res) {
                // The server's own sentence: it is the only thing that knows how
                // many of them already existed.
                toast.success(res.message ?? 'Time slots generated.');
                open = false;
                onsaved?.();
            }
        } catch (err) {
            toast.error(err?.message ?? 'Something went wrong. Please try again.');
        }
    }
</script>

<Drawer bind:open title="Generate time slots" width="w-[520px]" {footer}>
    <form class="flex flex-col gap-5" onsubmit={submit}>
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <Field label="From" error={form.errors.start_date} required>
                <DatePicker bind:value={form.data.start_date} invalid={!!form.errors.start_date} />
            </Field>
            <Field label="To" error={form.errors.end_date} required hint="A year ahead at most.">
                <DatePicker bind:value={form.data.end_date} invalid={!!form.errors.end_date} />
            </Field>
        </div>

        <Field label="Days" error={form.errors.days_of_week} required hint="The weekdays tours run on.">
            <div class="flex flex-wrap gap-2">
                {#each WEEKDAYS as day (day.value)}
                    <button
                        type="button"
                        class="kt-btn kt-btn-sm {form.data.days_of_week.includes(day.value) ? 'kt-btn-primary' : 'kt-btn-outline'}"
                        onclick={() => toggleDay(day.value)}
                        aria-pressed={form.data.days_of_week.includes(day.value)}
                    >
                        {day.label}
                    </button>
                {/each}
            </div>
        </Field>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <Field label="First tour starts" error={form.errors.start_time} required>
                <Input type="time" bind:value={form.data.start_time} invalid={!!form.errors.start_time} />
            </Field>
            <Field label="Last tour ends by" error={form.errors.end_time} required hint="A tour that would run past this is not generated.">
                <Input type="time" bind:value={form.data.end_time} invalid={!!form.errors.end_time} />
            </Field>
        </div>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
            <Field label="Tour length" error={form.errors.slot_minutes} required hint="Minutes.">
                <Input type="number" min="5" max="480" step="5" bind:value={form.data.slot_minutes} invalid={!!form.errors.slot_minutes} />
            </Field>
            <Field label="Gap between" error={form.errors.break_minutes} required hint="Minutes. Zero is fine.">
                <Input type="number" min="0" max="240" step="5" bind:value={form.data.break_minutes} invalid={!!form.errors.break_minutes} />
            </Field>
            <Field label="Limit each" error={form.errors.capacity} required hint="Bookings per time.">
                <Input type="number" min="1" max="1000" bind:value={form.data.capacity} invalid={!!form.errors.capacity} />
            </Field>
        </div>

        {#if total > 0}
            <Alert variant="info">
                {perDay} time(s) a day across {matchingDays} day(s) — <strong>{total} in total</strong>.
                Times that already exist are skipped, not duplicated.
            </Alert>
        {:else}
            <Alert variant="warning">
                Nothing to generate yet. Pick a date range, at least one weekday, and a window long enough
                to fit one whole tour.
            </Alert>
        {/if}
    </form>
</Drawer>

{#snippet footer()}
    <Button variant="secondary" onclick={() => (open = false)}>Cancel</Button>
    <Button variant="primary" loading={form.processing} disabled={total === 0} onclick={submit}>
        <i class="ki-filled ki-calendar-add"></i>Generate
    </Button>
{/snippet}
