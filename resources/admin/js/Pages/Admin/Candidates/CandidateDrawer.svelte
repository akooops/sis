<script>
    /**
     * One candidate, read in full.
     *
     * Same fetch discipline as ApplicationDrawer and SubmissionDrawer: the index
     * does not load the profile or the CV media, so this fetches show() itself,
     * behind a PLAIN LAST-ID COMPARE rather than a reactive key — the parent list
     * polls every 20 s, and a naive $effect would refetch on every poll and
     * re-mint a signed link that expires in 30 minutes.
     *
     * MATCHES ARE A SEPARATE REQUEST, not part of show(). The controller
     * deliberately does not eager-load them, so an admin who holds candidates.show
     * but not candidate-matches.index loses this one block instead of being 403'd
     * off the whole record. Its useIndex is readUrl:false (it must not fight the
     * page's own query string), immediate:false (a mounted-but-closed drawer must
     * not fetch) and pollMs:0.
     */
    import Drawer from '@/components/ui/Drawer.svelte';
    import Badge from '@/components/ui/Badge.svelte';
    import DateTime from '@/components/ui/DateTime.svelte';
    import ClampText from '@/components/ui/ClampText.svelte';
    import Avatar from '@/components/ui/Avatar.svelte';
    import Skeleton from '@/components/ui/Skeleton.svelte';
    import Alert from '@/components/feedback/Alert.svelte';
    import CandidateProfile from '@/components/jobs/CandidateProfile.svelte';
    import { useIndex } from '@/lib/api/useIndex.svelte';
    import { api } from '@/lib/api/client';
    import { NOT_SCORED, isScored, scoreVariant } from '@/lib/candidate';
    import { JOB_APPLICATION_STATUS_LABELS, JOB_APPLICATION_STATUS_VARIANTS } from '@/lib/jobApplication';
    import { hasPermission } from '@/lib/permissions';
    import { untrack } from 'svelte';

    let { open = $bindable(false), candidate = null } = $props();

    let record = $state(null);
    let loading = $state(false);
    let error = $state(null);

    const canReadMatches = hasPermission('candidate-matches.index');

    const matches = useIndex('api.v1.admin.candidate-matches.index', {
        perPage: 5,
        sort: '-score',
        readUrl: false,
        immediate: false,
        pollMs: 0,
    });

    let lastId = null;

    $effect(() => {
        if (!open) {
            lastId = null;
            return;
        }

        const id = candidate?.id ?? null;

        if (id === lastId) return;
        lastId = id;

        untrack(() => {
            load(id);
            if (id && canReadMatches) matches.setFilters({ candidate_id: id });
        });
    });

    async function load(id) {
        if (!id) {
            record = null;
            error = null;
            return;
        }

        record = candidate;
        error = null;
        loading = true;

        try {
            record = await api.get(route('api.v1.admin.candidates.show', id));
        } catch (e) {
            error = e?.message ?? 'This candidate could not be loaded.';
        } finally {
            loading = false;
        }
    }

    const applications = $derived(record?.applications ?? []);
    const pools = $derived(record?.clusters ?? []);
</script>

<Drawer bind:open title="Candidate" width="w-[640px]">
    {#snippet header()}
        <div class="flex min-w-0 items-center gap-3">
            <Avatar name={record?.full_name ?? candidate?.full_name ?? ''} size="sm" />
            <div class="flex min-w-0 flex-col gap-0.5">
                <h3 class="text-base font-semibold text-mono">
                    <ClampText value={record?.full_name ?? candidate?.full_name ?? 'Candidate'} />
                </h3>
                <div class="text-xs font-normal text-muted-foreground">
                    <ClampText value={record?.email ?? candidate?.email ?? ''} />
                </div>
            </div>
        </div>
    {/snippet}

    {#if error}
        <Alert variant="destructive">{error}</Alert>
    {/if}

    {#if record}
        <div class="flex flex-col gap-5">
            <!-- Contact -->
            <dl class="flex flex-col divide-y divide-border rounded-lg border border-border">
                <div class="flex items-center justify-between gap-4 px-3 py-2 text-sm">
                    <dt class="shrink-0 text-muted-foreground">ID</dt>
                    <dd class="break-all text-end font-mono font-medium text-primary">#{record.id}</dd>
                </div>
                <div class="flex items-center justify-between gap-4 px-3 py-2 text-sm">
                    <dt class="shrink-0 text-muted-foreground">Phone</dt>
                    <dd class="text-end text-mono">{record.phone || '—'}</dd>
                </div>
                <div class="flex items-start justify-between gap-4 px-3 py-2 text-sm">
                    <dt class="shrink-0 text-muted-foreground">Address</dt>
                    <dd class="min-w-0 max-w-[65%] text-mono">
                        <ClampText value={record.address ?? '—'} lines={3} />
                    </dd>
                </div>
                <div class="flex items-center justify-between gap-4 px-3 py-2 text-sm">
                    <dt class="shrink-0 text-muted-foreground">Nationality</dt>
                    <dd class="text-end text-mono">{record.country_name ?? record.country?.name ?? '—'}</dd>
                </div>
                <div class="flex items-center justify-between gap-4 px-3 py-2 text-sm">
                    <dt class="shrink-0 text-muted-foreground">Experience</dt>
                    <dd class="text-end text-mono">
                        {record.years_of_experience === null || record.years_of_experience === undefined
                            ? '—'
                            : `${record.years_of_experience} year${record.years_of_experience === 1 ? '' : 's'}`}
                    </dd>
                </div>
                <div class="flex items-center justify-between gap-4 px-3 py-2 text-sm">
                    <dt class="shrink-0 text-muted-foreground">Added</dt>
                    <dd class="text-end text-mono"><DateTime value={record.created_at} /></dd>
                </div>
            </dl>

            {#if loading && !record.educations}
                <div class="flex flex-col gap-2"><Skeleton /><Skeleton /><Skeleton /></div>
            {:else}
                <CandidateProfile candidate={record} />
            {/if}

            <!-- Matches: the same scoring table the posting drawer reads, from the
                 candidate's end. -->
            {#if canReadMatches}
                <div class="flex flex-col gap-2">
                    <span class="text-sm font-medium text-mono">Best matches</span>
                    {#if matches.loading && !matches.rows.length}
                        <Skeleton />
                    {:else if matches.rows.length}
                        <ul class="flex flex-col divide-y divide-border rounded-lg border border-border">
                            {#each matches.rows as m (m.id)}
                                <li class="flex items-center justify-between gap-3 px-3 py-2 text-sm">
                                    <span class="min-w-0">
                                        <ClampText value={m.job_offer_name ?? m.job_offer_id} />
                                    </span>
                                    <span class="flex shrink-0 items-center gap-1.5">
                                        {#if m.has_applied}
                                            <Badge variant={JOB_APPLICATION_STATUS_VARIANTS[m.application_status] ?? 'secondary'}>
                                                {JOB_APPLICATION_STATUS_LABELS[m.application_status] ?? m.application_status}
                                            </Badge>
                                        {:else}
                                            <Badge variant="secondary">Recommended</Badge>
                                        {/if}
                                        {#if isScored(m.score)}
                                            <Badge variant={scoreVariant(m.score)}>{m.score}</Badge>
                                        {:else}
                                            <Badge variant="secondary">{NOT_SCORED}</Badge>
                                        {/if}
                                    </span>
                                </li>
                            {/each}
                        </ul>
                    {:else}
                        <p class="text-sm text-muted-foreground">
                            No matches yet. Scoring runs in the background after an application arrives.
                        </p>
                    {/if}
                </div>
            {/if}

            <!-- Applications -->
            <div class="flex flex-col gap-2">
                <span class="text-sm font-medium text-mono">Applications</span>
                {#if applications.length}
                    <ul class="flex flex-col divide-y divide-border rounded-lg border border-border">
                        {#each applications as a (a.id)}
                            <li class="flex items-center justify-between gap-3 px-3 py-2 text-sm">
                                <span class="min-w-0">
                                    <!-- CandidateApplicationData flattens the posting to a
                                         name; there is no nested job_offer to reach into. -->
                                    <ClampText value={a.job_offer_name ?? a.job_offer_id} />
                                </span>
                                <span class="flex shrink-0 items-center gap-2">
                                    <Badge variant={JOB_APPLICATION_STATUS_VARIANTS[a.status] ?? 'secondary'}>
                                        {JOB_APPLICATION_STATUS_LABELS[a.status] ?? a.status}
                                    </Badge>
                                    {#if a.applied_at}
                                        <span class="text-xs text-muted-foreground"><DateTime value={a.applied_at} /></span>
                                    {/if}
                                </span>
                            </li>
                        {/each}
                    </ul>
                {:else}
                    <p class="text-sm text-muted-foreground">No applications recorded.</p>
                {/if}
            </div>

            <!-- Pools -->
            <div class="flex flex-col gap-2">
                <span class="text-sm font-medium text-mono">Talent pools</span>
                {#if pools.length}
                    <div class="flex flex-wrap gap-1.5">
                        {#each pools as p (p.id)}
                            <Badge variant="primary">{p.name}</Badge>
                        {/each}
                    </div>
                {:else}
                    <div class="rounded-lg border border-dashed border-border p-3">
                        <p class="text-sm text-muted-foreground">Not in a pool yet.</p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {#if record.is_embedded}
                                Pools are rebuilt nightly once at least 30 candidates have an embedding.
                            {:else}
                                This candidate has no embedding yet, so the nightly rebuild cannot place them.
                            {/if}
                        </p>
                    </div>
                {/if}
            </div>
        </div>
    {/if}
</Drawer>
