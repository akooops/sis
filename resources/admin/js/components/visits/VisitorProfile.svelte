<script>
    /**
     * The household behind a booking, and the children coming with them.
     *
     * Shaped like components/jobs/CandidateProfile: a heading, then either a
     * bordered divided list or a "Nothing recorded" line, so a drawer never shows a
     * bare gap where a section was supposed to be.
     */
    import { gradeLabel } from '@/lib/visitReservation';

    /*
     * `personLabel` because the same row means different things to the two
     * modules that share it: on a school visit this person is a guardian, on a
     * venue booking they are simply whoever booked. One table, two words for it.
     */
    let { visitor = null, attendees = null, personLabel = 'Guardian' } = $props();

    /*
     * NULL means "this kind of booking has no attendees", which is different from
     * an empty list. A school visit always names its students, so it passes an
     * array and an empty one is worth showing; a venue booking names one person,
     * so it passes nothing and the section is omitted rather than reading
     * "Students (0)".
     */
    const rows = $derived(Array.isArray(attendees) ? attendees : null);
</script>

<div class="flex flex-col gap-5">
    <div class="flex flex-col gap-2">
        <span class="text-sm font-medium text-mono">{personLabel}</span>

        {#if visitor}
            <dl class="flex flex-col divide-y divide-border rounded-lg border border-border text-sm">
                <div class="flex items-center justify-between gap-3 px-3 py-2">
                    <dt class="shrink-0 text-muted-foreground">Name</dt>
                    <dd class="text-end text-mono">{visitor.full_name}</dd>
                </div>
                <div class="flex items-center justify-between gap-3 px-3 py-2">
                    <dt class="shrink-0 text-muted-foreground">Email</dt>
                    <dd class="text-end text-mono">
                        <a class="hover:underline" href="mailto:{visitor.email}">{visitor.email}</a>
                    </dd>
                </div>
                {#if visitor.phone}
                    <div class="flex items-center justify-between gap-3 px-3 py-2">
                        <dt class="shrink-0 text-muted-foreground">Phone</dt>
                        <dd class="text-end text-mono">
                            <a class="hover:underline" href="tel:{visitor.phone}" dir="ltr">{visitor.phone}</a>
                        </dd>
                    </div>
                {/if}
            </dl>
        {:else}
            <p class="text-sm text-muted-foreground">Nothing recorded.</p>
        {/if}
    </div>

    {#if rows}
    <div class="flex flex-col gap-2">
        <span class="text-sm font-medium text-mono">Students ({rows.length})</span>

        {#if rows.length}
            <ul class="flex flex-col divide-y divide-border rounded-lg border border-border">
                {#each rows as row (row.id)}
                    <li class="flex flex-col gap-0.5 px-3 py-2 text-sm">
                        <span class="font-medium text-mono">{row.full_name}</span>
                        {#if row.grade || row.current_school}
                            <span class="text-muted-foreground">
                                {[gradeLabel(row.grade), row.current_school].filter(Boolean).join(' · ')}
                            </span>
                        {/if}
                    </li>
                {/each}
            </ul>
        {:else}
            <p class="text-sm text-muted-foreground">Nothing recorded.</p>
        {/if}
    </div>
    {/if}
</div>
