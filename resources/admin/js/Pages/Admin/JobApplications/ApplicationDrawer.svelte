<script>
    /**
     * One application, read in full.
     *
     * NOT a DetailDrawer: that renders one flat label/value list, and this is a
     * match block, a profile, a source link and a row of transition buttons.
     *
     * FETCHES THE RECORD ITSELF rather than reusing the row. The index does not
     * load the candidate's profile or media (it would be several queries per row),
     * so the CV's signed link and the four profile lists only exist on show().
     *
     * The fetch is guarded by a PLAIN LAST-ID COMPARE, not a reactive key: the
     * page's list polls every 20 s and re-renders this component's props with each
     * poll, and a naive $effect would refetch every time — which for a drawer left
     * open all afternoon would also mean re-minting links that expire in 30
     * minutes, over and over.
     */
    import Drawer from '@/components/ui/Drawer.svelte';
    import Badge from '@/components/ui/Badge.svelte';
    import Button from '@/components/ui/Button.svelte';
    import DateTime from '@/components/ui/DateTime.svelte';
    import ClampText from '@/components/ui/ClampText.svelte';
    import Skeleton from '@/components/ui/Skeleton.svelte';
    import Alert from '@/components/feedback/Alert.svelte';
    import CandidateProfile from '@/components/jobs/CandidateProfile.svelte';
    import { api } from '@/lib/api/client';
    import {
        JOB_APPLICATION_STATUS_LABELS,
        JOB_APPLICATION_STATUS_VARIANTS,
        availableTransitions,
    } from '@/lib/jobApplication';
    import { NOT_SCORED, isScored, scoreVariant } from '@/lib/candidate';
    import { hasPermission } from '@/lib/permissions';
    import { toast } from '@/lib/toast';
    import { router } from '@inertiajs/svelte';
    import { untrack } from 'svelte';

    let { open = $bindable(false), application = null, onchanged } = $props();

    let record = $state(null);
    let loading = $state(false);
    let saving = $state(false);
    let error = $state(null);

    // Plain compare, not a reactive key — see the class docblock.
    let lastId = null;

    $effect(() => {
        if (!open) {
            // Forget what was loaded WITHOUT clearing it: reopening refetches (the
            // CV link is signed and expires), while the record stays put so the
            // panel does not blank out halfway through its slide-out.
            lastId = null;
            return;
        }

        const id = application?.id ?? null;

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

        // The row we were opened for renders immediately; the fetch only adds the
        // profile and the CV link, and replaces it once it lands.
        record = application;
        error = null;
        loading = true;

        try {
            record = await api.get(route('api.v1.admin.job-applications.show', id));
        } catch (e) {
            error = e?.message ?? 'This application could not be loaded.';
        } finally {
            loading = false;
        }
    }

    const transitions = $derived(
        availableTransitions(record?.status).filter((t) => hasPermission(t.permission)),
    );

    async function transition(action) {
        if (!record?.id) return;
        saving = true;
        try {
            await api.post(route(`api.v1.admin.job-applications.${action}`, record.id));
            toast.success('Updated successfully.');
            // Refetch so the drawer shows the new state and its new legal moves.
            await load(record.id);
            onchanged?.();
        } catch (e) {
            // The server's 422 wording says which moves were legal; show it rather
            // than anything guessed here.
            toast.error(e?.message ?? 'Something went wrong. Please try again.');
        } finally {
            saving = false;
        }
    }

    const openSubmission = () =>
        router.visit(route('web.admin.form-submissions.index', { 'filter[id]': record.form_submission_id }));
</script>

<Drawer bind:open title="Application" width="w-[640px]">
    {#snippet header()}
        <div class="flex min-w-0 flex-col gap-0.5">
            <h3 class="text-base font-semibold text-mono">
                <ClampText value={record?.candidate_name ?? application?.candidate_name ?? 'Application'} />
            </h3>
            {#if record?.job_offer_name ?? application?.job_offer_name}
                <div class="text-xs font-normal text-muted-foreground">
                    <ClampText value={record?.job_offer_name ?? application?.job_offer_name} />
                </div>
            {/if}
        </div>
    {/snippet}

    {#if error}
        <Alert variant="destructive">{error}</Alert>
    {/if}

    {#if record}
        <div class="flex flex-col gap-5">
            <div class="flex flex-wrap items-center gap-2">
                <Badge variant={JOB_APPLICATION_STATUS_VARIANTS[record.status] ?? 'secondary'}>
                    {JOB_APPLICATION_STATUS_LABELS[record.status] ?? record.status}
                </Badge>
                {#if record.applied_at}
                    <span class="text-xs text-muted-foreground">
                        Applied <DateTime value={record.applied_at} />
                    </span>
                {:else}
                    <span class="text-xs text-muted-foreground">No application date recorded</span>
                {/if}
            </div>

            <!-- Match. THE ONE BLOCK THAT MUST NOT LIE: a null score means the
                 scoring queue has not reached this pair, which is a different
                 thing from a low score, and it never renders as 0 or in red. -->
            <div class="flex flex-col gap-2">
                <span class="text-sm font-medium text-mono">Match</span>
                {#if isScored(record.score)}
                    <div class="flex flex-col gap-2 rounded-lg border border-border p-3">
                        <span class="flex items-center gap-2">
                            <Badge variant={scoreVariant(record.score)}>{record.score} / 100</Badge>
                            {#if record.score >= 60}<Badge variant="success" outline={false}>Strong match</Badge>{/if}
                        </span>
                        {#if record.score_comment}
                            <p class="text-sm text-mono">{record.score_comment}</p>
                        {:else}
                            <p class="text-sm text-muted-foreground">No comment.</p>
                        {/if}
                        {#if record.scored_at}
                            <span class="text-xs text-muted-foreground">
                                Scored <DateTime value={record.scored_at} />
                            </span>
                        {/if}
                    </div>
                {:else}
                    <div class="rounded-lg border border-dashed border-border p-3">
                        <p class="text-sm text-muted-foreground">{NOT_SCORED}</p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            Scoring runs in the background after an application arrives.
                        </p>
                    </div>
                {/if}
            </div>

            {#if loading && !record.candidate}
                <div class="flex flex-col gap-2"><Skeleton /><Skeleton /><Skeleton /></div>
            {:else if record.candidate}
                <CandidateProfile candidate={record.candidate} />
            {/if}

            <!-- Where this came from -->
            <div class="flex flex-col gap-2">
                <span class="text-sm font-medium text-mono">Source</span>
                <dl class="flex flex-col divide-y divide-border rounded-lg border border-border">
                    <div class="flex items-center justify-between gap-4 px-3 py-2 text-sm">
                        <dt class="shrink-0 text-muted-foreground">Application ID</dt>
                        <dd class="break-all text-end font-mono font-medium text-primary">#{record.id}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4 px-3 py-2 text-sm">
                        <dt class="shrink-0 text-muted-foreground">Submission</dt>
                        <dd class="min-w-0 text-end">
                            {#if record.form_submission_id && hasPermission('form-submissions.index')}
                                <button class="kt-btn kt-btn-sm kt-btn-ghost text-primary" onclick={openSubmission}>
                                    <i class="ki-filled ki-questionnaire-tablet"></i>View the raw answers
                                </button>
                            {:else if record.form_submission_id}
                                <span class="font-mono text-xs text-mono">{record.form_submission_id}</span>
                            {:else}
                                <!-- nullOnDelete: the answers are an audit trail,
                                     not the record of record. -->
                                <span class="text-muted-foreground">The original submission is gone</span>
                            {/if}
                        </dd>
                    </div>
                    <div class="flex items-center justify-between gap-4 px-3 py-2 text-sm">
                        <dt class="shrink-0 text-muted-foreground">Created</dt>
                        <dd class="text-end text-mono"><DateTime value={record.created_at} /></dd>
                    </div>
                    <div class="flex items-center justify-between gap-4 px-3 py-2 text-sm">
                        <dt class="shrink-0 text-muted-foreground">Updated</dt>
                        <dd class="text-end text-mono"><DateTime value={record.updated_at} /></dd>
                    </div>
                </dl>
            </div>
        </div>
    {/if}

    {#snippet footer()}
        {#if transitions.length}
            {#each transitions as t (t.action)}
                <Button variant={t.variant === 'destructive' ? 'destructive' : 'primary'} loading={saving} onclick={() => transition(t.action)}>
                    <i class="ki-filled {t.icon}"></i>{t.label}
                </Button>
            {/each}
        {:else if record}
            <!-- Hired is terminal; a rejected application can only be reopened by
                 someone holding the shortlist permission. -->
            <span class="text-xs text-muted-foreground">No moves available from this status.</span>
        {/if}
    {/snippet}
</Drawer>
