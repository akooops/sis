<script>
    /**
     * One time slot, created or edited.
     *
     * Opened by dragging a range on the calendar (create, prefilled from the drag)
     * or by clicking an existing event (edit). The SERVICE IS NOT EDITABLE here:
     * moving a slot between facilities would carry its bookings with it and silently
     * change what a family booked, so the honest way to do that is to delete an
     * empty slot and make another.
     *
     * The two refusals — capacity below what is booked, and deleting a booked slot
     * — are the server's, and their wording comes back in the 422. Nothing is
     * re-checked here, because the count could change between the render and the
     * click.
     */
    import Drawer from '@/components/ui/Drawer.svelte';
    import Field from '@/components/form/Field.svelte';
    import Input from '@/components/form/Input.svelte';
    import Switch from '@/components/form/Switch.svelte';
    import DatePicker from '@/components/form/DatePicker.svelte';
    import Button from '@/components/ui/Button.svelte';
    import Badge from '@/components/ui/Badge.svelte';
    import Alert from '@/components/feedback/Alert.svelte';
    import { useForm } from '@/lib/api/useForm.svelte';
    import { api } from '@/lib/api/client';
    import { toast } from '@/lib/toast';
    import { confirm } from '@/lib/confirm';
    import { hasPermission } from '@/lib/permissions';
    import { SLOT_STATE_LABELS, SLOT_STATE_VARIANTS, slotOccupancy } from '@/lib/facility';

    let {
        open = $bindable(false),
        serviceId = null,
        visitSlot = null,
        /** {start, end} from a calendar drag, for a fresh visitSlot. */
        range = null,
        defaultCapacity = 1,
        onsaved,
    } = $props();

    const editing = $derived(!!visitSlot?.id);

    const form = useForm({ starts_at: '', ends_at: '', capacity: defaultCapacity, is_open: true });

    /*
     * A PLAIN LAST-KEY COMPARE, not a reactive $effect over the props.
     *
     * The drawer is reused across every slot on the calendar and stays mounted, so
     * a naive effect would re-seed the form on any parent re-render — including the
     * one caused by typing in it. See the note on the same pattern in
     * JobApplications/ApplicationDrawer.
     */
    let lastKey = null;

    $effect(() => {
        if (!open) {
            lastKey = null;

            return;
        }

        const key = visitSlot?.id ?? `new:${range?.start ?? ''}:${range?.end ?? ''}`;

        if (key === lastKey) return;

        lastKey = key;
        seed();
    });

    function seed() {
        form.clearErrors();

        if (visitSlot?.id) {
            form.data.starts_at = local(visitSlot.starts_at);
            form.data.ends_at = local(visitSlot.ends_at);
            form.data.capacity = visitSlot.capacity ?? 1;
            form.data.is_open = visitSlot.is_open ?? true;

            return;
        }

        form.data.starts_at = range?.start ? local(range.start) : '';
        form.data.ends_at = range?.end ? local(range.end) : '';
        form.data.capacity = defaultCapacity;
        form.data.is_open = true;
    }

    /**
     * An ISO string (or a Date) as the "Y-m-d H:i" the DatePicker speaks.
     *
     * Sliced off a LOCAL rendering, not off the ISO string: toISOString() is UTC,
     * so a 09:00 Riyadh slot would open the editor showing 06:00.
     */
    function local(value) {
        const date = value instanceof Date ? value : new Date(value);

        if (Number.isNaN(date.getTime())) return '';

        const pad = (n) => String(n).padStart(2, '0');

        return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())} ${pad(date.getHours())}:${pad(date.getMinutes())}`;
    }

    async function submit(event) {
        event?.preventDefault();

        const url = editing
            ? route('api.v1.admin.facility-slots.update', visitSlot.id)
            : route('api.v1.admin.facility-slots.store');

        try {
            const res = await form.submit(editing ? 'put' : 'post', url, {
                transform: (data) => ({
                    ...data,
                    capacity: Number(data.capacity) || 0,
                    ...(editing ? {} : { facility_id: serviceId }),
                }),
            });

            if (res) {
                toast.success(editing ? 'Time slot updated.' : 'Time slot created.');
                open = false;
                onsaved?.();
            }
        } catch (err) {
            toast.error(err?.message ?? 'Something went wrong. Please try again.');
        }
    }

    async function remove() {
        if (!(await confirm({
            body: 'Delete this time slot? This is refused while anyone is booked on it — close it instead.',
            variant: 'destructive',
        }))) return;

        try {
            await api.delete(route('api.v1.admin.facility-slots.destroy', visitSlot.id));
            toast.success('Time slot deleted.');
            open = false;
            onsaved?.();
        } catch (err) {
            toast.error(err?.message ?? 'Something went wrong. Please try again.');
        }
    }
</script>

<Drawer bind:open title={editing ? 'Edit time slot' : 'New time slot'} width="w-[480px]" {footer}>
    <form class="flex flex-col gap-5" onsubmit={submit}>
        {#if editing}
            <div class="flex flex-wrap items-center gap-2">
                <Badge variant={SLOT_STATE_VARIANTS[visitSlot.state] ?? 'secondary'}>
                    {SLOT_STATE_LABELS[visitSlot.state] ?? visitSlot.state}
                </Badge>
                <span class="text-sm text-muted-foreground">{slotOccupancy(visitSlot)} booked</span>
                {#if visitSlot.is_overbooked}
                    <Badge variant="destructive">Over capacity</Badge>
                {/if}
            </div>

            {#if visitSlot.reserved > 0}
                <Alert variant="warning">
                    {visitSlot.reserved} booking(s) are held on this time. The limit cannot go below that, and it
                    cannot be deleted until they are cancelled — closing it stops new bookings and leaves them alone.
                </Alert>
            {/if}
        {/if}

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <Field label="Starts at" error={form.errors.starts_at} required>
                <DatePicker enableTime bind:value={form.data.starts_at} invalid={!!form.errors.starts_at} />
            </Field>
            <Field label="Ends at" error={form.errors.ends_at} required>
                <DatePicker enableTime bind:value={form.data.ends_at} invalid={!!form.errors.ends_at} />
            </Field>
        </div>

        <Field label="Limit" error={form.errors.capacity} required hint="How many BOOKINGS this time accepts. Party size is capped separately, on the visit.">
            <Input type="number" min="1" max="1000" bind:value={form.data.capacity} invalid={!!form.errors.capacity} />
        </Field>

        <Field label="Open for booking" error={form.errors.is_open} hint="Closing hides it from the public calendar without touching the bookings already made.">
            <Switch bind:value={form.data.is_open} />
        </Field>
    </form>
</Drawer>

{#snippet footer()}
    {#if editing && hasPermission('facility-slots.destroy')}
        <Button variant="destructive" onclick={remove}>
            <i class="ki-filled ki-trash"></i>Delete
        </Button>
    {/if}
    <Button variant="secondary" onclick={() => (open = false)}>Cancel</Button>
    <Button variant="primary" loading={form.processing} onclick={submit}>Save</Button>
{/snippet}
