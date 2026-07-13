<script>
    import { createEventDispatcher, onMount } from 'svelte';

    const dispatch = createEventDispatcher();

    const TEMPLATES = {
        shipment_delay: {
            label: 'Shipment delay',
            from_name: 'Supplier Logistics',
            subject: 'RE: Shipment delay — updated ETA',
            body: `Dear Mawdja team,

We regret to inform you that shipment for your recent PO is delayed at customs.

Updated ETA: +7 days from original plan.
Reason: documentation review at port.

Please confirm if you need us to split the shipment.

Best regards,
Supplier Logistics Team`,
        },
        po_confirmation: {
            label: 'PO confirmation',
            from_name: 'Supplier Sales',
            subject: 'Purchase order confirmed — ready to ship',
            body: `Hello,

Your purchase order has been confirmed in our system.
We will prepare shipment within 5 business days.

Please confirm receiving warehouse contact details.

Regards,
Supplier Sales`,
        },
        quality_hold: {
            label: 'Quality hold',
            from_name: 'Supplier QA',
            subject: 'Batch on quality hold pending COA',
            body: `Dear warehouse team,

Batch attached to the latest delivery is on temporary quality hold.
COA will be shared within 48 hours.

Do not release to QC until we confirm.

Supplier QA Department`,
        },
    };

    const emptyForm = () => ({
        supplier_id: '',
        from_email: '',
        from_name: '',
        subject: '',
        body: '',
        run_agent: true,
    });

    let suppliers = [];
    let form = emptyForm();
    let errors = {};
    let loading = false;
    let lastChatId = null;

    onMount(fetchSuppliers);

    async function fetchSuppliers() {
        try {
            const response = await fetch(route('api.v1.admin.suppliers.index', { per_page: 100 }), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            const data = await response.json();
            suppliers = data.suppliers || [];
        } catch (error) {
            console.error('Failed to load suppliers', error);
        }
    }

    function applyTemplate(key) {
        const template = TEMPLATES[key];
        if (!template) return;

        form = {
            ...form,
            from_name: template.from_name,
            subject: template.subject,
            body: template.body,
        };
    }

    function onSupplierChange() {
        const supplier = suppliers.find((s) => s.id === form.supplier_id);
        if (!supplier) return;

        form.from_email = supplier.email || form.from_email;
        form.from_name = supplier.name || form.from_name;
    }

    async function handleSubmit() {
        loading = true;
        errors = {};
        lastChatId = null;

        try {
            const response = await fetch(route('api.v1.admin.mail-simulator.store'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                },
                body: JSON.stringify({
                    ...form,
                    supplier_id: form.supplier_id || null,
                }),
            });

            const data = await response.json();

            if (response.ok) {
                form = emptyForm();
                lastChatId = data.chat_id || null;
                toast(data.message || 'Inbound email received', 'success');
                dispatch('created', { chat_id: lastChatId });
            } else if (data.errors) {
                errors = data.errors;
            } else {
                errors = { general: data.message || 'Failed to receive email.' };
            }
        } catch (error) {
            console.error(error);
            errors = { general: 'Network error occurred. Please try again.' };
        } finally {
            loading = false;
        }
    }

    function handleCancel() {
        form = emptyForm();
        errors = {};
        lastChatId = null;
        dispatch('canceled');
    }
</script>

<div class="space-y-6">
    <form on:submit|preventDefault={handleSubmit} class="space-y-5">
        {#if errors.general}
            <div class="p-3 bg-destructive/10 border border-destructive/20 rounded-lg">
                <p class="text-sm text-destructive">{errors.general}</p>
            </div>
        {/if}

        <div class="kt-card border border-border">
            <div class="kt-card-header border-b border-border flex items-center justify-between">
                <span class="text-sm font-semibold text-mono">Receive inbound email</span>
                <div class="flex flex-wrap gap-2">
                    {#each Object.entries(TEMPLATES) as [key, template]}
                        <button
                            type="button"
                            class="kt-btn kt-btn-xs kt-btn-light"
                            disabled={loading}
                            on:click={() => applyTemplate(key)}
                        >
                            {template.label}
                        </button>
                    {/each}
                </div>
            </div>
            <div class="kt-card-content p-4 space-y-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-medium text-mono" for="supplier_id">Supplier</label>
                        <select
                            id="supplier_id"
                            class="kt-input {errors.supplier_id ? 'kt-input-error' : ''}"
                            bind:value={form.supplier_id}
                            disabled={loading}
                            on:change={onSupplierChange}
                        >
                            <option value="">Custom sender</option>
                            {#each suppliers as supplier}
                                <option value={supplier.id}>{supplier.name}</option>
                            {/each}
                        </select>
                        {#if errors.supplier_id}
                            <p class="text-sm text-destructive">{errors.supplier_id}</p>
                        {/if}
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-medium text-mono" for="from_email">
                            From email <span class="text-destructive">*</span>
                        </label>
                        <input
                            id="from_email"
                            type="email"
                            class="kt-input {errors.from_email ? 'kt-input-error' : ''}"
                            placeholder="supplier@example.com"
                            bind:value={form.from_email}
                            disabled={loading}
                        />
                        {#if errors.from_email}
                            <p class="text-sm text-destructive">{errors.from_email}</p>
                        {/if}
                    </div>

                    <div class="flex flex-col gap-2 md:col-span-2">
                        <label class="text-sm font-medium text-mono" for="from_name">From name</label>
                        <input
                            id="from_name"
                            type="text"
                            class="kt-input {errors.from_name ? 'kt-input-error' : ''}"
                            placeholder="Supplier name"
                            bind:value={form.from_name}
                            disabled={loading}
                        />
                        {#if errors.from_name}
                            <p class="text-sm text-destructive">{errors.from_name}</p>
                        {/if}
                    </div>

                    <div class="flex flex-col gap-2 md:col-span-2">
                        <label class="text-sm font-medium text-mono" for="subject">
                            Subject <span class="text-destructive">*</span>
                        </label>
                        <input
                            id="subject"
                            type="text"
                            class="kt-input {errors.subject ? 'kt-input-error' : ''}"
                            placeholder="Email subject"
                            bind:value={form.subject}
                            disabled={loading}
                        />
                        {#if errors.subject}
                            <p class="text-sm text-destructive">{errors.subject}</p>
                        {/if}
                    </div>

                    <div class="flex flex-col gap-2 md:col-span-2">
                        <label class="text-sm font-medium text-mono" for="body">
                            Body <span class="text-destructive">*</span>
                        </label>
                        <textarea
                            id="body"
                            class="kt-input min-h-[220px] font-mono text-sm {errors.body ? 'kt-input-error' : ''}"
                            placeholder="Full email body…"
                            bind:value={form.body}
                            disabled={loading}
                        ></textarea>
                        {#if errors.body}
                            <p class="text-sm text-destructive">{errors.body}</p>
                        {/if}
                    </div>

                    <div class="flex items-center gap-2 md:col-span-2">
                        <input
                            id="run_agent"
                            type="checkbox"
                            class="kt-checkbox"
                            bind:checked={form.run_agent}
                            disabled={loading}
                        />
                        <label for="run_agent" class="text-sm text-mono">
                            Run supervisor agent (creates chat + notifications)
                        </label>
                    </div>
                </div>
            </div>
        </div>

        {#if lastChatId}
            <div class="rounded-lg border border-primary/30 bg-primary/5 px-4 py-3 text-sm">
                Agent chat created —
                <a
                    href={route('web.admin.chats.index', {}, false) + '?chat=' + lastChatId}
                    class="text-primary font-medium hover:underline"
                >
                    Open chat
                </a>
            </div>
        {/if}

        <div class="flex items-center justify-end gap-2">
            <button type="button" class="kt-btn kt-btn-secondary" disabled={loading} on:click={handleCancel}>
                Cancel
            </button>
            <button
                type="submit"
                class="kt-btn kt-btn-primary"
                disabled={loading || !form.from_email || !form.subject || !form.body}
            >
                {#if loading}
                    <i class="fa-solid fa-spinner fa-spin mr-1"></i>
                {/if}
                Receive email
            </button>
        </div>
    </form>
</div>
