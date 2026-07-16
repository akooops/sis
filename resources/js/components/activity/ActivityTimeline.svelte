<script>
    /**
     * ActivityTimeline — the activity log as a day-grouped timeline.
     *
     * Rows expand to show their column diff. Used by ActivityDrawer (per record)
     * and reusable anywhere a chronological read beats a table.
     *
     *   <ActivityTimeline rows={list.rows} loading={list.loading} />
     */
    import Avatar from '@/components/ui/Avatar.svelte';
    import Badge from '@/components/ui/Badge.svelte';
    import EmptyState from '@/components/ui/EmptyState.svelte';
    import Skeleton from '@/components/ui/Skeleton.svelte';
    import ActivityDiff from './ActivityDiff.svelte';
    import ClampText from '@/components/ui/ClampText.svelte';
    import {
        activityMessage,
        eventIcon,
        eventVariant,
        hasDiff,
        EVENT_LABELS,
        SUBJECT_TYPE_LABELS,
    } from '@/lib/activity';
    import { formatDate, formatRelative, formatTime } from '@/lib/date';

    let { rows = [], loading = false, skeletonCount = 6 } = $props();

    let expanded = $state(new Set());

    function toggle(id) {
        const next = new Set(expanded);
        next.has(id) ? next.delete(id) : next.add(id);
        expanded = next;
    }

    // Grouped by calendar day.
    const groups = $derived.by(() => {
        const map = new Map();
        for (const row of rows ?? []) {
            const day = (row.created_at ?? '').slice(0, 10);
            if (!map.has(day)) map.set(day, []);
            map.get(day).push(row);
        }
        return [...map.entries()].map(([day, items]) => ({ day, items }));
    });
</script>

{#if loading}
    <div class="flex flex-col gap-5">
        {#each Array(skeletonCount) as _, i (i)}
            <div class="flex gap-3">
                <Skeleton class="size-8 shrink-0 rounded-full" />
                <div class="flex grow flex-col gap-2">
                    <Skeleton class="h-3.5 w-2/3 rounded" />
                    <Skeleton class="h-3 w-1/3 rounded" />
                </div>
            </div>
        {/each}
    </div>
{:else if !rows?.length}
    <EmptyState icon="ki-filled ki-time" title="No activity yet" body="Changes will appear here as soon as someone makes one." />
{:else}
    <div class="flex flex-col gap-6">
        {#each groups as group (group.day)}
            <div class="flex flex-col gap-3">
                <div class="sticky top-0 z-10 -mx-1 bg-background/95 px-1 py-1 backdrop-blur">
                    <span class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                        {formatDate(group.day)}
                    </span>
                </div>

                <ul class="flex flex-col">
                    {#each group.items as row (row.id)}
                        {@const expandable = hasDiff(row.properties)}
                        <li class="relative flex gap-3 pb-5 last:pb-0">
                            <!-- connector -->
                            <span class="absolute start-4 top-9 bottom-0 w-px bg-border last:hidden"></span>

                            <span
                                class="relative z-10 flex size-8 shrink-0 items-center justify-center rounded-full border border-border bg-background text-sm text-muted-foreground"
                            >
                                <i class={eventIcon(row.event)}></i>
                            </span>

                            <div class="flex min-w-0 grow flex-col gap-1.5">
                                <div class="flex min-w-0 flex-wrap items-center gap-2">
                                    <Badge variant={eventVariant(row.event)} size="sm">
                                        {EVENT_LABELS[row.event] ?? row.event}
                                    </Badge>
                                    <div class="min-w-0 grow text-sm text-mono">
                                        <ClampText value={activityMessage(row)} lines={2} toggle />
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                                    {#if row.causer_id}
                                        <span class="inline-flex items-center gap-1.5">
                                            <Avatar name={row.causer_name ?? ''} size="xs" />
                                            <span class="font-medium">{row.causer_name ?? row.causer_id}</span>
                                            <span class="opacity-60">·</span>
                                            <span>{SUBJECT_TYPE_LABELS[row.causer_type] ?? row.causer_type}</span>
                                        </span>
                                    {:else}
                                        <span class="inline-flex items-center gap-1.5">
                                            <i class="ki-filled ki-technology-4"></i>
                                            System
                                        </span>
                                    {/if}
                                    <span class="opacity-60">·</span>
                                    <span title={row.created_at}>{formatTime(row.created_at)}</span>
                                    <span class="opacity-60">·</span>
                                    <span>{formatRelative(row.created_at)}</span>

                                    {#if expandable}
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-1 text-primary hover:underline"
                                            onclick={() => toggle(row.id)}
                                        >
                                            <i class={expanded.has(row.id) ? 'ki-filled ki-up' : 'ki-filled ki-down'}></i>
                                            {expanded.has(row.id) ? 'Collapse' : 'What changed'}
                                        </button>
                                    {/if}
                                </div>

                                {#if expandable && expanded.has(row.id)}
                                    <div class="pt-1">
                                        <ActivityDiff properties={row.properties} />
                                    </div>
                                {/if}
                            </div>
                        </li>
                    {/each}
                </ul>
            </div>
        {/each}
    </div>
{/if}
