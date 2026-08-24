<script>
    /**
     * One booking, in full, with the moves legal from where it has reached.
     *
     * Not a DetailDrawer: that renders one flat label/value list, and this needs the
     * household, the students and the time as three separate blocks — plus a note
     * field that writes back.
     *
     * The fetch guard is a PLAIN LAST-ID COMPARE rather than a reactive key,
     * because the parent list polls: a naive $effect would refetch on every poll.
     * Same pattern, same reason, as JobApplications/ApplicationDrawer.
     */
    import { untrack } from 'svelte';
    import Drawer from '@/components/ui/Drawer.svelte';
    import Badge from '@/components/ui/Badge.svelte';
    import Button from '@/components/ui/Button.svelte';
    import Alert from '@/components/feedback/Alert.svelte';
    import Spinner from '@/components/ui/Spinner.svelte';
    import Field from '@/components/form/Field.svelte';
    import DateTime from '@/components/ui/DateTime.svelte';
    import VisitorProfile from '@/components/visits/VisitorProfile.svelte';
    import { api } from '@/lib/api/client';
    import { toast } from '@/lib/toast';
    import { hasPermission } from '@/lib/permissions';
    import {
        FACILITY_RESERVATION_STATUS_LABELS,
        FACILITY_RESERVATION_STATUS_VARIANTS,
        availableTransitions,
    } from '@/lib/facilityReservation';

    let { open = $bindable(false), reservation = null, onchanged } = $props();

    let record = $state(null);
    let loading = $state(false);
    let saving = $state(false);
    let error = $state(null);
    let note = $state('');

    let lastId = null;

    $effect(() => {
        if (!open) {
            // Forget WITHOUT clearing: blanking here would empty the panel
            // mid-slide-out.
            lastId = null;

            return;
        }

        const id = reservation?.id ?? null;

        if (id === lastId) return;

        lastId = id;
        untrack(() => load(id));
    });

    async function load(id) {
        if (!id) {
            record = null;
            error = null;

            return;
        }

        // The row renders immediately; show() only adds the students and the slot.
        record = reservation;
        note = reservation?.note ?? '';
        error = null;
        loading = true;

        try {
            record = await api.get(route('api.v1.admin.facility-reservations.show', id));
            note = record?.note ?? '';
        } catch (err) {
            error = err?.message ?? 'This reservation could not be loaded.';
        } finally {
            loading = false;
        }
    }

    const transitions = $derived(
        record ? availableTransitions(record.status).filter((t) => hasPermission(t.permission)) : [],
    );

    async function transition(action) {
        saving = true;

        try {
            record = await api.post(route(`api.v1.admin.facility-reservations.${action}`, record.id));
            toast.success('Updated successfully.');
            onchanged?.();
        } catch (err) {
            // The server's own sentence names which move was refused and why.
            toast.error(err?.message ?? 'Something went wrong. Please try again.');
        } finally {
            saving = false;
        }
    }

    async function saveNote() {
        saving = true;

        try {
            record = await api.put(route('api.v1.admin.facility-reservations.update', record.id), { note });
            toast.success('Note saved.');
            onchanged?.();
        } catch (err) {
            toast.error(err?.message ?? 'Something went wrong. Please try again.');
        } finally {
            saving = false;
        }
    }
</script>

<Drawer bind:open title="Visit booking" width="w-[640px]" {footer}>
    {#if error}
        <Alert variant="danger">{error}</Alert>
    {:else if !record}
        <div class="flex justify-center py-10"><Spinner /></div>
    {:else}
        <div class="flex flex-col gap-6">
            <div class="flex flex-wrap items-center gap-2">
                <Badge variant={FACILITY_RESERVATION_STATUS_VARIANTS[record.status] ?? 'secondary'}>
                    {FACILITY_RESERVATION_STATUS_LABELS[record.status] ?? record.status}
                </Badge>
                {#if loading}<Spinner />{/if}
            </div>

            <div class="flex flex-col gap-2">
                <span class="text-sm font-medium text-mono">The visit</span>
                <dl class="flex flex-col divide-y divide-border rounded-lg border border-border text-sm">
                    <div class="flex items-center justify-between gap-3 px-3 py-2">
                        <dt class="shrink-0 text-muted-foreground">Visit</dt>
                        <dd class="text-end text-mono">{record.service_name ?? ''}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 px-3 py-2">
                        <dt class="shrink-0 text-muted-foreground">Time</dt>
                        <dd class="text-end text-mono">
                            {#if record.slot_starts_at}
                                <DateTime value={record.slot_starts_at} />
                            {/if}
                        </dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 px-3 py-2">
                        <dt class="shrink-0 text-muted-foreground">Booked</dt>
                        <dd class="text-end text-mono">
                            {#if record.booked_at}<DateTime value={record.booked_at} />{/if}
                        </dd>
                    </div>
                </dl>
            </div>

            <!-- No `attendees`: a venue booking names one person, so VisitorProfile
                 omits the section entirely rather than showing "Students (0)". -->
            <VisitorProfile visitor={record.visitor} personLabel="Booked by" />

            {#if hasPermission('facility-reservations.update')}
                <Field label="Internal note" hint="The desk's own. Never shown to whoever booked.">
                    <textarea class="kt-input min-h-[90px]" bind:value={note}></textarea>
                </Field>
                <div class="flex justify-end">
                    <Button variant="outline" size="sm" loading={saving} onclick={saveNote}>Save note</Button>
                </div>
            {:else if record.note}
                <Field label="Internal note">
                    <p class="text-sm text-muted-foreground">{record.note}</p>
                </Field>
            {/if}
        </div>
    {/if}
</Drawer>

{#snippet footer()}
    {#if transitions.length}
        {#each transitions as move (move.action)}
            <Button
                variant={move.variant === 'destructive' ? 'destructive' : 'primary'}
                loading={saving}
                onclick={() => transition(move.action)}
            >
                <i class="ki-filled {move.icon}"></i>{move.label}
            </Button>
        {/each}
    {:else if record}
        <span class="text-xs text-muted-foreground">No moves available from this status.</span>
    {/if}
{/snippet}
