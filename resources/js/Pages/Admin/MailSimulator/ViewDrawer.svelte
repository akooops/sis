<script>
    import { createEventDispatcher } from 'svelte';

    const dispatch = createEventDispatcher();

    export let email = null;

    let approving = false;
    let rejecting = false;

    function close() {
        dispatch('close');
    }

    function formatDirection(direction) {
        return direction === 'inbound' ? 'Received' : 'Sent';
    }

    async function approveEmail() {
        if (!email?.can_be_approved || approving) return;

        const confirmed = confirm(`Approve and send this email to ${email.to_name || email.to_email}?`);
        if (!confirmed) return;

        approving = true;

        try {
            const response = await fetch(route('api.v1.admin.emails.approve', { email: email.id }), {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                },
            });
            const data = await response.json();

            if (response.ok) {
                toast('Email approved and sent', 'success');
                dispatch('updated', data.email);
                close();
            } else {
                toast(data.message || 'Failed to approve email', 'error');
            }
        } catch (error) {
            console.error('Error approving email:', error);
            toast('Network error occurred', 'error');
        } finally {
            approving = false;
        }
    }

    async function rejectEmail() {
        if (!email?.can_be_approved || rejecting) return;

        const confirmed = confirm('Reject this email draft? It will not be sent.');
        if (!confirmed) return;

        rejecting = true;

        try {
            const response = await fetch(route('api.v1.admin.emails.reject', { email: email.id }), {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                },
            });
            const data = await response.json();

            if (response.ok) {
                toast('Email draft rejected', 'success');
                dispatch('updated', data.email);
                close();
            } else {
                toast(data.message || 'Failed to reject email', 'error');
            }
        } catch (error) {
            console.error('Error rejecting email:', error);
            toast('Network error occurred', 'error');
        } finally {
            rejecting = false;
        }
    }
</script>

{#if email}
    <div
        class="fixed inset-0 z-50 flex justify-end bg-black/40"
        role="presentation"
        on:click={close}
        on:keydown={(e) => e.key === 'Escape' && close()}
    >
        <div
            class="h-full w-full max-w-lg bg-background border-s border-border shadow-xl flex flex-col"
            role="dialog"
            aria-modal="true"
            on:click|stopPropagation
        >
            <div class="flex items-center justify-between px-5 py-4 border-b border-border">
                <h3 class="text-base font-semibold text-mono">Email details</h3>
                <button type="button" class="kt-btn kt-btn-icon kt-btn-ghost size-8" on:click={close}>
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto p-5 space-y-4 text-sm">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold uppercase tracking-wide px-2 py-0.5 rounded bg-muted">
                        {formatDirection(email.direction)}
                    </span>
                    <span class="text-xs text-muted-foreground">{email.status?.replace(/_/g, ' ')}</span>
                </div>

                <div>
                    <div class="text-xs text-muted-foreground mb-1">Subject</div>
                    <div class="font-medium">{email.subject || '(no subject)'}</div>
                </div>

                {#if email.direction === 'inbound'}
                    <div>
                        <div class="text-xs text-muted-foreground mb-1">From</div>
                        <div>{email.from_name || '—'} &lt;{email.from_email}&gt;</div>
                    </div>
                {:else}
                    <div>
                        <div class="text-xs text-muted-foreground mb-1">To</div>
                        <div>{email.to_name || '—'} &lt;{email.to_email}&gt;</div>
                    </div>
                    {#if email.source_agent}
                        <div>
                            <div class="text-xs text-muted-foreground mb-1">Source agent</div>
                            <div class="capitalize">{email.source_agent?.replace(/_/g, ' ')}</div>
                        </div>
                    {/if}
                {/if}

                {#if email.supplier}
                    <div>
                        <div class="text-xs text-muted-foreground mb-1">Supplier</div>
                        <div>{email.supplier.name}</div>
                    </div>
                {/if}

                <div>
                    <div class="text-xs text-muted-foreground mb-1">Body</div>
                    <pre class="whitespace-pre-wrap font-mono text-xs bg-muted/40 rounded-lg p-3 border border-border">{email.body}</pre>
                </div>

                {#if email.chat_id}
                    <a
                        href={route('web.admin.chats.index', {}, false) + '?chat=' + email.chat_id}
                        class="inline-flex items-center gap-2 text-primary hover:underline"
                    >
                        <i class="fa-solid fa-robot"></i>
                        Open agent chat
                    </a>
                {/if}
            </div>

            {#if email.can_be_approved}
                <div class="px-5 py-4 border-t border-border flex gap-2">
                    <button
                        type="button"
                        class="kt-btn kt-btn-primary flex-1"
                        on:click={approveEmail}
                        disabled={approving || rejecting}
                    >
                        {#if approving}
                            <i class="fa-solid fa-spinner fa-spin mr-1"></i>
                        {:else}
                            <i class="fa-solid fa-check mr-1"></i>
                        {/if}
                        Approve &amp; send
                    </button>
                    <button
                        type="button"
                        class="kt-btn kt-btn-outline flex-1"
                        on:click={rejectEmail}
                        disabled={approving || rejecting}
                    >
                        {#if rejecting}
                            <i class="fa-solid fa-spinner fa-spin mr-1"></i>
                        {:else}
                            <i class="fa-solid fa-xmark mr-1"></i>
                        {/if}
                        Reject
                    </button>
                </div>
            {/if}
        </div>
    </div>
{/if}
