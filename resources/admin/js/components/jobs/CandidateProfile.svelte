<script>
    /**
     * A candidate's profile, as read from the typed tables.
     *
     * ONE COMPONENT, TWO DRAWERS. The application drawer and the candidate drawer
     * both need exactly this block — summary, CV, education, experience,
     * languages, skills — and it is ~150 lines of markup with eight distinct
     * empty states. Duplicating it would guarantee the two drift.
     *
     * EVERY EMPTY STATE IS HONEST. No AI provider is configured, so `ai_comment`
     * is null on every candidate in the system today; it says "No summary yet" and
     * explains that one is written in the background, rather than showing a blank
     * panel that reads like a bug. Same for a missing CV, which is an anomaly
     * (the CV is required at submit) and reads as one.
     *
     * The profile is a PROJECTION of the most recent application, replaced
     * wholesale each time that person applies — so nothing here is editable.
     */
    import Badge from '@/components/ui/Badge.svelte';
    import DateTime from '@/components/ui/DateTime.svelte';
    import { formatFileSize } from '@/lib/format';
    import { NO_CV, NO_SUMMARY, proficiencyLabel, yearRange } from '@/lib/candidate';
    import { hasPermission } from '@/lib/permissions';

    let { candidate = null, showSummary = true } = $props();

    const educations = $derived(candidate?.educations ?? []);
    const experiences = $derived(candidate?.experiences ?? []);
    const languages = $derived(candidate?.languages ?? []);
    const skills = $derived(candidate?.skills ?? []);
</script>

{#if candidate}
    <div class="flex flex-col gap-5">
        {#if showSummary}
            <!-- Summary -->
            <div class="flex flex-col gap-2">
                <span class="text-sm font-medium text-mono">Summary</span>
                {#if candidate.ai_comment}
                    <div class="rounded-lg border border-border p-3 text-sm text-mono">
                        {candidate.ai_comment}
                    </div>
                    {#if candidate.summarised_at}
                        <span class="text-xs text-muted-foreground">
                            Written <DateTime value={candidate.summarised_at} />
                        </span>
                    {/if}
                {:else}
                    <div class="rounded-lg border border-dashed border-border p-3">
                        <p class="text-sm text-muted-foreground">{NO_SUMMARY}</p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            A summary is written in the background once a candidate applies.
                        </p>
                    </div>
                {/if}
            </div>
        {/if}

        <!-- CV. The link is signed and short-lived, so it only exists on a
             freshly fetched record — never render one from a list row. -->
        <div class="flex flex-col gap-2">
            <span class="text-sm font-medium text-mono">CV</span>
            {#if candidate.cv?.url && hasPermission('candidates.cv')}
                <a
                    class="flex items-center gap-3 rounded-lg border border-border p-3 hover:border-primary"
                    href={candidate.cv.url}
                    target="_blank"
                    rel="noreferrer"
                >
                    <i class="ki-filled ki-file text-xl text-primary"></i>
                    <span class="flex min-w-0 flex-col">
                        <span class="truncate text-sm font-medium text-mono">{candidate.cv.name}</span>
                        <span class="text-xs text-muted-foreground">
                            {candidate.cv.mime ?? 'file'} · {formatFileSize(candidate.cv.size)}
                        </span>
                    </span>
                    <i class="ki-filled ki-exit-down ms-auto text-muted-foreground"></i>
                </a>
            {:else if candidate.has_cv}
                <!-- The file exists; this admin may not pull it. Saying so beats
                     showing the same "No CV on file" as a candidate who has none. -->
                <p class="text-sm text-muted-foreground">
                    A CV is on file. You do not have permission to download it.
                </p>
            {:else}
                <p class="text-sm text-muted-foreground">{NO_CV}</p>
            {/if}
        </div>

        <!-- Education -->
        <div class="flex flex-col gap-2">
            <span class="text-sm font-medium text-mono">Education</span>
            {#if educations.length}
                <ul class="flex flex-col divide-y divide-border rounded-lg border border-border">
                    {#each educations as row (row.id)}
                        <li class="flex flex-col gap-0.5 px-3 py-2 text-sm">
                            <span class="font-medium text-mono">{row.institution}</span>
                            {#if row.degree || row.field_of_study}
                                <span class="text-muted-foreground">
                                    {[row.degree, row.field_of_study].filter(Boolean).join(' · ')}
                                </span>
                            {/if}
                            {#if yearRange(row.start_year, row.end_year)}
                                <span class="text-xs text-muted-foreground">{yearRange(row.start_year, row.end_year)}</span>
                            {/if}
                            {#if row.description}
                                <span class="mt-1 text-xs text-muted-foreground">{row.description}</span>
                            {/if}
                        </li>
                    {/each}
                </ul>
            {:else}
                <!-- Not a defect: the education group is optional with no minimum
                     row, because demanding a qualification before someone can
                     apply for a manual post is a filter nobody intended. -->
                <p class="text-sm text-muted-foreground">Nothing recorded.</p>
            {/if}
        </div>

        <!-- Experience -->
        <div class="flex flex-col gap-2">
            <span class="text-sm font-medium text-mono">Experience</span>
            {#if experiences.length}
                <ul class="flex flex-col divide-y divide-border rounded-lg border border-border">
                    {#each experiences as row (row.id)}
                        <li class="flex flex-col gap-0.5 px-3 py-2 text-sm">
                            <span class="flex items-center gap-2">
                                <span class="font-medium text-mono">{row.company_name}</span>
                                {#if row.is_current}<Badge variant="primary">Current</Badge>{/if}
                            </span>
                            {#if row.job_title}
                                <span class="text-muted-foreground">{row.job_title}</span>
                            {/if}
                            {#if yearRange(row.start_year, row.end_year, row.is_current)}
                                <span class="text-xs text-muted-foreground">
                                    {yearRange(row.start_year, row.end_year, row.is_current)}
                                </span>
                            {/if}
                            {#if row.description}
                                <span class="mt-1 text-xs text-muted-foreground">{row.description}</span>
                            {/if}
                        </li>
                    {/each}
                </ul>
            {:else}
                <p class="text-sm text-muted-foreground">Nothing recorded.</p>
            {/if}
        </div>

        <!-- Languages -->
        <div class="flex flex-col gap-2">
            <span class="text-sm font-medium text-mono">Languages</span>
            {#if languages.length}
                <div class="flex flex-wrap gap-1.5">
                    {#each languages as row (row.id)}
                        <Badge variant="info">{row.name} · {proficiencyLabel(row.proficiency)}</Badge>
                    {/each}
                </div>
            {:else}
                <p class="text-sm text-muted-foreground">Nothing recorded.</p>
            {/if}
        </div>

        <!-- Skills -->
        <div class="flex flex-col gap-2">
            <span class="text-sm font-medium text-mono">Skills</span>
            {#if skills.length}
                <div class="flex flex-wrap gap-1.5">
                    {#each skills as row (row.id)}
                        <Badge variant="secondary">{row.name}</Badge>
                    {/each}
                </div>
            {:else}
                <p class="text-sm text-muted-foreground">Nothing recorded.</p>
            {/if}
        </div>
    </div>
{/if}
