<script>
    /**
     * SubmissionDrawer — one submission, read in full.
     *
     * ANSWERS ARE RENDERED FROM THE SUBMISSION'S OWN `fields` SNAPSHOT, never
     * from the live form. That snapshot ({key: {label, type, order}}, taken at
     * submit time) is the entire reason the column exists: a form relabelled or
     * restructured last year must not rewrite what a visitor was actually asked.
     * Anything answered under a key the snapshot missed is still listed so no
     * answer can go invisible.
     *
     * EACH ANSWER IS TITLED BY ITS KEY, NOT BY THE SNAPSHOT'S LABEL. The label is
     * the visitor-facing title in the language they submitted in, so an Arabic
     * submission listed its answers under Arabic headings inside an admin that is
     * English-only by design. The key is what the builder card shows, what the
     * CSV column is named and what the API speaks — one name for a field, on
     * every screen. The label stays in the column as part of the record.
     *
     * `order` is what the list is sorted by, because MySQL's JSON type does not
     * keep object key order — see SubmissionValidator::snapshot().
     *
     * Not a DetailDrawer: that renders one flat label/value list, and this is
     * four groups plus a file list plus a per-answer section.
     *
     * Fetches the record itself rather than reusing the row — the index does not
     * load media (it would be a query per row), so `files` and their signed,
     * short-lived links only exist on show(). The fetch is guarded by a plain
     * last-id compare: the page's list polls every 20s and re-renders this
     * component's props with it, and a naive $effect would refetch each time.
     */
    import Drawer from '@/components/ui/Drawer.svelte';
    import Badge from '@/components/ui/Badge.svelte';
    import DateTime from '@/components/ui/DateTime.svelte';
    import ClampText from '@/components/ui/ClampText.svelte';
    import Skeleton from '@/components/ui/Skeleton.svelte';
    import Alert from '@/components/feedback/Alert.svelte';
    import { api } from '@/lib/api/client';
    import { formatFileSize } from '@/lib/format';
    import {
        FILE_FIELD_TYPE,
        GROUP_FIELD_TYPE,
        SUBMISSION_STATUS_LABELS,
        SUBMISSION_STATUS_VARIANTS,
        formatAnswer,
        groupEntries,
    } from '@/lib/form';
    import { untrack } from 'svelte';

    let { open = $bindable(false), submission = null } = $props();

    let record = $state(null);
    let loading = $state(false);
    let error = $state(null);

    // Plain compare, not a reactive key: see the class docblock.
    let lastId = null;

    $effect(() => {
        if (!open) {
            // Forget what was loaded WITHOUT clearing it: reopening refetches
            // (the file links are signed and expire in half an hour, so a drawer
            // left mounted all afternoon would hand out dead ones), while the
            // record itself stays put so the panel does not blank out halfway
            // through its slide-out.
            lastId = null;
            return;
        }

        const id = submission?.id ?? null;

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

        // The row we were opened for renders immediately; the fetch only adds
        // the files and replaces it once it lands.
        record = submission;
        error = null;
        loading = true;

        try {
            record = await api.get(route('api.v1.admin.form-submissions.show', id));
        } catch (e) {
            error = e?.message ?? 'This submission could not be loaded.';
        } finally {
            loading = false;
        }
    }

    /**
     * The answers, snapshot order first (that is the form's own reading order),
     * then anything `data` holds which the snapshot does not name.
     */
    const answers = $derived.by(() => {
        if (!record) return [];

        const snapshot = record.fields ?? {};
        const data = record.data ?? {};
        const files = record.files ?? [];

        /*
         * SORTED BY THE SNAPSHOT'S `order`, NOT BY KEY ORDER.
         *
         * The snapshot is written in the form's reading order, but it lands in a
         * MySQL JSON column, and that type stores an object with its keys sorted
         * by length then lexicographically — so it reads back shuffled, and the
         * drawer used to list answers as field 5, then 2, then 4.
         * SubmissionValidator::snapshot() therefore records an explicit `order`.
         *
         * `?? Infinity` keeps rows written before that field existed working:
         * they simply keep whatever order the column hands back, which is what
         * they did before.
         */
        const keys = [
            ...Object.keys(snapshot).sort(
                (a, b) => (snapshot[a]?.order ?? Infinity) - (snapshot[b]?.order ?? Infinity),
            ),
            ...Object.keys(data).filter((key) => !(key in snapshot)),
        ];

        // No `label` here on purpose: the drawer lists answers by KEY. The
        // snapshot's label is the visitor's translated title and is kept on the
        // row for the record, not for this screen.
        return keys.map((key) => ({
            key,
            type: snapshot[key]?.type ?? null,
            orphaned: !(key in snapshot),
            value: data[key] ?? null,
            files: files.filter((file) => file.field_key === key),
            /*
             * A repeatable group's answer is a list of objects, so it is expanded
             * into its entries here rather than run through formatAnswer(), which
             * would render an education history as `[object Object]`.
             *
             * Its files are keyed `group.child` (see
             * FormSubmissionData::fieldKeysByMedia) and are matched per entry in
             * the markup, in the order the ids appear.
             */
            entries:
                snapshot[key]?.type === GROUP_FIELD_TYPE
                    ? groupEntries(data[key], snapshot[key]?.children ?? {})
                    : [],
            childFiles: files.filter((file) => (file.field_key ?? '').startsWith(`${key}.`)),
        }));
    });

    /**
     * Files no file-answer claims — an upload whose field was dropped, say.
     * Listed anyway: the bytes exist and this drawer is the only door to them.
     */
    const looseFiles = $derived(
        (record?.files ?? []).filter(
            (file) =>
                !answers.some(
                    (answer) =>
                        (answer.type === FILE_FIELD_TYPE && answer.key === file.field_key) ||
                        // A file inside a repeatable group is claimed under
                        // `group.child`, so matching on the answer key alone
                        // would list every one of them as unclaimed.
                        (answer.type === GROUP_FIELD_TYPE &&
                            (file.field_key ?? '').startsWith(`${answer.key}.`)),
                ),
        ),
    );

    const utmEntries = $derived(
        Object.entries(record?.utm ?? {}).filter(([, value]) => value !== null && value !== ''),
    );

    /**
     * `validation_errors` is written as a LIST of the field keys that failed
     * (recordFailure() stores array_keys($validator->errors()->toArray())), so
     * the common shape is ["name","email"] with no messages. Reading it as a map
     * would put the array index in the label and the key in the message —
     * "0 — name". A keyed {field: message} shape is still rendered in full, so an
     * older row or a future writer stays readable.
     */
    const validationEntries = $derived(
        Array.isArray(record?.validation_errors)
            ? record.validation_errors.map((key) => [key, null])
            : Object.entries(record?.validation_errors ?? {}),
    );

    /** Seconds as the admin reads them: "45s", "2m 05s". */
    function duration(seconds) {
        if (seconds === null || seconds === undefined) return '';
        if (seconds < 60) return `${seconds}s`;

        return `${Math.floor(seconds / 60)}m ${String(seconds % 60).padStart(2, '0')}s`;
    }

    const spamVariant = $derived(
        (record?.spam_score ?? 0) >= 60 ? 'destructive' : (record?.spam_score ?? 0) >= 30 ? 'warning' : 'secondary',
    );
</script>

<Drawer bind:open title="Submission" width="w-[640px]">
    {#snippet header()}
        <div class="flex min-w-0 flex-col gap-0.5">
            <!-- The ULID is the reference: it is what the visitor was shown. -->
            <h3 class="font-mono text-base font-semibold text-mono">
                {record?.id ?? submission?.id ?? 'Submission'}
            </h3>
            {#if record?.form_name ?? submission?.form_name}
                <div class="text-xs font-normal text-muted-foreground">
                    <ClampText value={record?.form_name ?? submission?.form_name} />
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
                <Badge variant={SUBMISSION_STATUS_VARIANTS[record.status] ?? 'secondary'}>
                    {SUBMISSION_STATUS_LABELS[record.status] ?? record.status}
                </Badge>
                {#if record.is_honeypot_triggered}
                    <Badge variant="destructive">Honeypot</Badge>
                {/if}
                {#if record.submitted_at}
                    <span class="text-xs text-muted-foreground">
                        <DateTime value={record.submitted_at} />
                    </span>
                {:else}
                    <span class="text-xs text-muted-foreground">Never submitted</span>
                {/if}
            </div>

            <!-- Answers -->
            <div class="flex flex-col gap-2">
                <span class="text-sm font-medium text-mono">Answers</span>

                {#if loading && !answers.length}
                    <div class="flex flex-col gap-2">
                        <Skeleton /><Skeleton /><Skeleton />
                    </div>
                {:else if !answers.length}
                    <p class="text-sm text-muted-foreground">
                        Nothing was answered — this submission never got past the first field.
                    </p>
                {:else}
                    <dl class="flex flex-col divide-y divide-border rounded-lg border border-border">
                        {#each answers as answer (answer.key)}
                            <div class="flex flex-col gap-1 px-3 py-2 text-sm">
                                <dt class="flex items-center gap-2 text-muted-foreground">
                                    <!-- THE FIELD KEY, NOT ITS LABEL. The label is
                                         the visitor-facing title in the language
                                         they submitted in, so an Arabic submission
                                         listed its answers under Arabic headings in
                                         an admin that is English-only by design.
                                         The key is what the builder shows, what the
                                         export column is named and what the API
                                         speaks — one name for a field everywhere. -->
                                    <code class="min-w-0 truncate">{answer.key}</code>
                                    {#if answer.orphaned}
                                        <!-- Answered under a key the form's snapshot
                                             never recorded — a field removed since,
                                             shown so the answer cannot be lost. -->
                                        <Badge variant="warning" size="sm">Not in form</Badge>
                                    {/if}
                                </dt>
                                <dd class="min-w-0 text-mono">
                                    {#if answer.type === FILE_FIELD_TYPE}
                                        {#if answer.files.length}
                                            <div class="flex flex-col gap-1">
                                                {#each answer.files as file (file.id)}
                                                    <!-- Answer uploads are private: no
                                                         media URL exists, and this signed,
                                                         short-lived route is the only way
                                                         to the bytes. -->
                                                    <a
                                                        class="inline-flex items-center gap-1.5 text-primary hover:underline"
                                                        href={file.url}
                                                        download={file.name}
                                                    >
                                                        <i class="ki-filled ki-file-down"></i>
                                                        <span class="truncate">{file.name}</span>
                                                        <span class="shrink-0 text-xs text-muted-foreground">
                                                            {formatFileSize(file.size)}
                                                        </span>
                                                    </a>
                                                {/each}
                                            </div>
                                        {:else if loading}
                                            <Skeleton />
                                        {:else}
                                            <span class="text-muted-foreground">No file</span>
                                        {/if}
                                    {:else if answer.type === GROUP_FIELD_TYPE}
                                        {#if answer.entries.length}
                                            <!-- One card per repeat, so it stays
                                                 obvious which degree belongs to
                                                 which institution. Rows keep the
                                                 field-key convention the rest of
                                                 the drawer uses. -->
                                            <div class="flex flex-col gap-2">
                                                {#each answer.entries as entry, index (index)}
                                                    <div class="flex flex-col gap-1 rounded-lg border border-border px-3 py-2">
                                                        <span class="text-xs text-muted-foreground">#{index + 1}</span>
                                                        {#each entry.rows as row (row.key)}
                                                            <div class="flex min-w-0 items-start gap-2">
                                                                <code class="shrink-0 text-xs text-muted-foreground">{row.key}</code>
                                                                <span class="min-w-0 flex-1 break-words">
                                                                    {#if row.type === FILE_FIELD_TYPE}
                                                                        {@const files = answer.childFiles.filter(
                                                                            (f) => f.field_key === `${answer.key}.${row.key}`,
                                                                        )}
                                                                        {#if files.length}
                                                                            {#each files as file (file.id)}
                                                                                <a
                                                                                    class="inline-flex items-center gap-1.5 text-primary hover:underline"
                                                                                    href={file.url}
                                                                                    download={file.name}
                                                                                >
                                                                                    <i class="ki-filled ki-file-down"></i>
                                                                                    <span class="truncate">{file.name}</span>
                                                                                </a>
                                                                            {/each}
                                                                        {:else}
                                                                            <span class="text-muted-foreground">No file</span>
                                                                        {/if}
                                                                    {:else if formatAnswer(row.value) === ''}
                                                                        <span class="text-muted-foreground">—</span>
                                                                    {:else}
                                                                        {formatAnswer(row.value)}
                                                                    {/if}
                                                                </span>
                                                            </div>
                                                        {/each}
                                                    </div>
                                                {/each}
                                            </div>
                                        {:else}
                                            <span class="text-muted-foreground">—</span>
                                        {/if}
                                    {:else if formatAnswer(answer.value) === ''}
                                        <span class="text-muted-foreground">—</span>
                                    {:else}
                                        <ClampText value={formatAnswer(answer.value)} lines={6} toggle copy />
                                    {/if}
                                </dd>
                            </div>
                        {/each}
                    </dl>
                {/if}

                {#if looseFiles.length}
                    <div class="flex flex-col gap-1 rounded-lg border border-border px-3 py-2">
                        <span class="text-xs text-muted-foreground">
                            Uploaded, but not tied to an answer
                        </span>
                        {#each looseFiles as file (file.id)}
                            <a class="inline-flex items-center gap-1.5 text-sm text-primary hover:underline" href={file.url} download={file.name}>
                                <i class="ki-filled ki-file-down"></i>
                                <span class="truncate">{file.name}</span>
                            </a>
                        {/each}
                    </div>
                {/if}
            </div>

            <!-- Spam & validation -->
            <div class="flex flex-col gap-2">
                <span class="text-sm font-medium text-mono">Spam &amp; validation</span>
                <dl class="flex flex-col divide-y divide-border rounded-lg border border-border">
                    <div class="flex items-center justify-between gap-4 px-3 py-2 text-sm">
                        <dt class="shrink-0 text-muted-foreground">Spam score</dt>
                        <dd><Badge variant={spamVariant}>{record.spam_score}</Badge></dd>
                    </div>
                    <div class="flex items-center justify-between gap-4 px-3 py-2 text-sm">
                        <dt class="shrink-0 text-muted-foreground">Honeypot</dt>
                        <dd class="text-mono">{record.is_honeypot_triggered ? 'Triggered' : 'Clean'}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4 px-3 py-2 text-sm">
                        <dt class="shrink-0 text-muted-foreground">Reasons</dt>
                        <dd class="min-w-0 max-w-[65%] text-mono">
                            {#if record.spam_reasons?.length}
                                <ClampText value={record.spam_reasons.join(', ')} lines={4} toggle />
                            {:else}
                                <span class="text-muted-foreground">None</span>
                            {/if}
                        </dd>
                    </div>
                    {#if validationEntries.length}
                        <div class="flex flex-col gap-1 px-3 py-2 text-sm">
                            <dt class="text-muted-foreground">Validation errors</dt>
                            <dd class="flex flex-col gap-0.5 text-mono">
                                {#each validationEntries as [field, message] (field)}
                                    <span class="text-xs">
                                        <span class="font-mono text-muted-foreground">{field}</span>
                                        {#if message !== null && message !== undefined}
                                            — {Array.isArray(message) ? message.join(' ') : message}
                                        {/if}
                                    </span>
                                {/each}
                            </dd>
                        </div>
                    {/if}
                </dl>
            </div>

            <!-- Technical -->
            <div class="flex flex-col gap-2">
                <span class="text-sm font-medium text-mono">Technical</span>
                <dl class="flex flex-col divide-y divide-border rounded-lg border border-border">
                    <div class="flex items-center justify-between gap-4 px-3 py-2 text-sm">
                        <dt class="shrink-0 text-muted-foreground">Country</dt>
                        <dd class="text-mono">{record.country_code || '—'}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4 px-3 py-2 text-sm">
                        <dt class="shrink-0 text-muted-foreground">City</dt>
                        <dd class="text-mono">{record.city || '—'}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4 px-3 py-2 text-sm">
                        <dt class="shrink-0 text-muted-foreground">Device</dt>
                        <dd class="text-mono">{record.device_type || '—'}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4 px-3 py-2 text-sm">
                        <dt class="shrink-0 text-muted-foreground">Browser</dt>
                        <dd class="text-mono">{record.browser || '—'}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4 px-3 py-2 text-sm">
                        <dt class="shrink-0 text-muted-foreground">Operating system</dt>
                        <dd class="text-mono">{record.os || '—'}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4 px-3 py-2 text-sm">
                        <!-- The flag, never the address: it does not leave the
                             server, so this is the whole of what can be shown. -->
                        <dt class="shrink-0 text-muted-foreground">IP recorded</dt>
                        <dd class="text-mono">{record.has_ip ? 'Yes' : 'No'}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4 px-3 py-2 text-sm">
                        <dt class="shrink-0 text-muted-foreground">Time to complete</dt>
                        <dd class="text-mono">{duration(record.duration_seconds) || '—'}</dd>
                    </div>
                </dl>
            </div>

            <!-- Acquisition -->
            <div class="flex flex-col gap-2">
                <span class="text-sm font-medium text-mono">Acquisition</span>
                <dl class="flex flex-col divide-y divide-border rounded-lg border border-border">
                    <div class="flex items-start justify-between gap-4 px-3 py-2 text-sm">
                        <dt class="shrink-0 text-muted-foreground">Page</dt>
                        <dd class="min-w-0 max-w-[65%] text-mono">
                            <ClampText value={record.page_url ?? ''} lines={3} toggle copy />
                        </dd>
                    </div>
                    <div class="flex items-start justify-between gap-4 px-3 py-2 text-sm">
                        <dt class="shrink-0 text-muted-foreground">Referrer</dt>
                        <dd class="min-w-0 max-w-[65%] text-mono">
                            <ClampText value={record.referrer ?? ''} lines={3} toggle copy />
                        </dd>
                    </div>
                    {#if utmEntries.length}
                        {#each utmEntries as [key, value] (key)}
                            <div class="flex items-start justify-between gap-4 px-3 py-2 text-sm">
                                <dt class="shrink-0 font-mono text-muted-foreground">{key}</dt>
                                <dd class="min-w-0 max-w-[65%] text-mono">
                                    <ClampText value={String(value)} lines={2} toggle />
                                </dd>
                            </div>
                        {/each}
                    {:else}
                        <div class="flex items-center justify-between gap-4 px-3 py-2 text-sm">
                            <dt class="shrink-0 text-muted-foreground">Campaign</dt>
                            <dd class="text-muted-foreground">No UTM tags</dd>
                        </div>
                    {/if}
                </dl>
            </div>

            <!-- Record -->
            <dl class="flex flex-col divide-y divide-border rounded-lg border border-border">
                <div class="flex items-center justify-between gap-4 px-3 py-2 text-sm">
                    <dt class="shrink-0 text-muted-foreground">ID</dt>
                    <dd class="break-all text-end font-mono font-medium text-primary">#{record.id}</dd>
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
    {:else if loading}
        <div class="flex flex-col gap-3">
            <Skeleton /><Skeleton /><Skeleton /><Skeleton />
        </div>
    {/if}
</Drawer>
