<script>
    export let selectedMessage = null;
    export let selectedChat = null;

    let steps = [];
    let loading = false;
    let error = null;
    let expandedStepIds = new Set();

    const STEP_ICONS = {
        agent_start: 'fa-play',
        llm_call: 'fa-brain',
        tool_call: 'fa-wrench',
        tool_result: 'fa-check',
        delegate: 'fa-share-nodes',
        context_summary: 'fa-compress',
        agent_end: 'fa-stop',
    };

    const STEP_COLORS = {
        agent_start: 'text-blue-500',
        llm_call: 'text-violet-500',
        tool_call: 'text-amber-500',
        tool_result: 'text-green-500',
        delegate: 'text-cyan-500',
        context_summary: 'text-orange-500',
        agent_end: 'text-slate-500',
    };

    const AGENT_LABELS = {
        supervisor: 'Supervisor',
        purchasing: 'Purchasing',
        purchase_order: 'PO Agent',
        shipping: 'Shipping',
        warehouse: 'Warehouse',
        wh_good_receipt: 'WH Receipt',
        qc: 'QC',
        qa: 'QA',
        qp: 'QP',
        planning: 'Planning',
    };

    function closeDrawer() {
        const dismissButton = document.querySelector('[data-kt-drawer-dismiss="#reasoning_view_drawer"]');
        if (dismissButton) {
            dismissButton.click();
        }
    }

    function formatAgent(agent) {
        if (!agent) return 'Agent';
        return AGENT_LABELS[agent] || agent.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
    }

    function formatStepType(type) {
        return (type || '').replace(/_/g, ' ');
    }

    function toggleStepDetails(stepId) {
        if (expandedStepIds.has(stepId)) {
            expandedStepIds.delete(stepId);
        } else {
            expandedStepIds.add(stepId);
        }
        expandedStepIds = expandedStepIds;
    }

    function formatPayload(payload) {
        if (!payload) return '';
        try {
            return JSON.stringify(payload, null, 2);
        } catch {
            return String(payload);
        }
    }

    async function loadReasoningSteps() {
        if (!selectedMessage || !selectedChat) {
            steps = [];
            return;
        }

        loading = true;
        error = null;
        expandedStepIds = new Set();

        try {
            const response = await fetch(
                route('api.v1.admin.chats.messages.reasoning', {
                    chat: selectedChat.id,
                    message: selectedMessage.id,
                }),
                { headers: { 'X-Requested-With': 'XMLHttpRequest' } }
            );

            if (!response.ok) {
                throw new Error('Failed to load reasoning trace');
            }

            const data = await response.json();
            steps = data.steps || [];
        } catch (err) {
            console.error('Error loading reasoning steps:', err);
            error = 'Could not load reasoning trace.';
            steps = [];
        } finally {
            loading = false;
        }
    }

    $: if (selectedMessage && selectedChat) {
        loadReasoningSteps();
    }
</script>

<div
    class="hidden kt-drawer kt-drawer-end card flex-col max-w-[90%] w-[520px] top-5 bottom-5 end-5 rounded-xl border border-border"
    data-kt-drawer="true"
    data-kt-drawer-container="body"
    id="reasoning_view_drawer"
>
    <div class="flex items-center justify-between gap-2.5 text-sm text-mono font-semibold px-5 py-2.5 border-b border-b-border">
        <div class="flex items-center gap-2">
            <i class="fa-solid fa-diagram-project text-primary"></i>
            Reasoning trace
        </div>
        <button class="kt-btn kt-btn-sm kt-btn-icon kt-btn-dim shrink-0" data-kt-drawer-dismiss="true" on:click={closeDrawer}>
            <i class="ki-filled ki-cross"></i>
        </button>
    </div>

    <div class="kt-card-content flex flex-col space-y-4 p-5 kt-scrollable-y-auto">
        {#if !selectedMessage}
            <div class="flex flex-col items-center justify-center text-center p-8">
                <div class="mb-4">
                    <i class="fa-solid fa-diagram-project text-4xl text-muted-foreground"></i>
                </div>
                <h3 class="text-lg font-semibold text-mono mb-2">No message selected</h3>
                <p class="text-sm text-muted-foreground">
                    Select an agent message to inspect its reasoning flow.
                </p>
            </div>
        {:else}
            <div class="space-y-2 pb-2 border-b border-border">
                <div class="text-xs text-muted-foreground">Agent response</div>
                <div class="text-sm font-medium text-mono">{formatAgent(selectedMessage.agent)}</div>
                <div class="text-xs text-muted-foreground">
                    {steps.length} step{steps.length === 1 ? '' : 's'}
                </div>
            </div>

            {#if loading}
                <div class="flex flex-col gap-3">
                    {#each Array(5) as _}
                        <div class="kt-skeleton h-16 w-full rounded-lg"></div>
                    {/each}
                </div>
            {:else if error}
                <div class="text-sm text-destructive text-center py-6">{error}</div>
            {:else if steps.length === 0}
                <div class="flex flex-col items-center justify-center text-center p-8">
                    <div class="mb-4">
                        <i class="fa-solid fa-route text-4xl text-muted-foreground"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-mono mb-2">No trace recorded</h3>
                    <p class="text-sm text-muted-foreground">
                        This message was sent before reasoning tracing was enabled.
                    </p>
                </div>
            {:else}
                <div class="space-y-3">
                    {#each steps as step (step.id)}
                        <div class="rounded-lg border border-border bg-muted/20 p-3">
                            <div class="flex items-start gap-3">
                                <div class="flex items-center justify-center size-8 rounded-full bg-background border border-border shrink-0 mt-0.5">
                                    <i class="fa-solid {STEP_ICONS[step.step_type] || 'fa-circle'} text-xs {STEP_COLORS[step.step_type] || 'text-muted-foreground'}"></i>
                                </div>

                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0">
                                            <div class="text-sm font-medium text-mono">
                                                {step.title}
                                            </div>
                                            <div class="text-[11px] text-muted-foreground mt-0.5 capitalize">
                                                Step {step.step_order} · {formatStepType(step.step_type)}
                                                {#if step.agent}
                                                    · {formatAgent(step.agent)}
                                                {/if}
                                            </div>
                                        </div>
                                        {#if step.payload}
                                            <button
                                                type="button"
                                                class="kt-btn kt-btn-xs kt-btn-ghost shrink-0"
                                                on:click={() => toggleStepDetails(step.id)}
                                            >
                                                {expandedStepIds.has(step.id) ? 'Hide' : 'Details'}
                                            </button>
                                        {/if}
                                    </div>

                                    {#if step.provider || step.model}
                                        <div class="mt-2 flex flex-wrap gap-1.5">
                                            {#if step.provider}
                                                <span class="kt-badge kt-badge-outline kt-badge-secondary text-[10px]">
                                                    {step.provider}
                                                </span>
                                            {/if}
                                            {#if step.model}
                                                <span class="kt-badge kt-badge-outline kt-badge-primary text-[10px]">
                                                    {step.model}
                                                </span>
                                            {/if}
                                        </div>
                                    {/if}

                                    {#if step.tool_name}
                                        <div class="mt-2">
                                            <span class="kt-badge kt-badge-outline kt-badge-warning text-[10px] font-mono">
                                                {step.tool_name}
                                            </span>
                                        </div>
                                    {/if}

                                    {#if step.payload?.content_preview}
                                        <p class="mt-2 text-xs text-muted-foreground whitespace-pre-wrap break-words line-clamp-4">
                                            {step.payload.content_preview}
                                        </p>
                                    {/if}

                                    {#if step.payload?.preview}
                                        <p class="mt-2 text-xs text-muted-foreground whitespace-pre-wrap break-words line-clamp-3 font-mono">
                                            {step.payload.preview}
                                        </p>
                                    {/if}

                                    {#if expandedStepIds.has(step.id) && step.payload}
                                        <pre class="mt-2 text-[11px] bg-background border border-border rounded-md p-2 overflow-x-auto whitespace-pre-wrap break-words">{formatPayload(step.payload)}</pre>
                                    {/if}
                                </div>
                            </div>
                        </div>
                    {/each}
                </div>
            {/if}
        {/if}
    </div>
</div>
