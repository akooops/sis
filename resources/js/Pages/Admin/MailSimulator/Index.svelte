<script>
    import MainLayout from '../../Shared/Layouts/MainLayout.svelte';
    import Pagination from '../../Shared/Utils/Pagination.svelte';
    import CreateForm from './CreateForm.svelte';
    import ViewDrawer from './ViewDrawer.svelte';
    import { inertia } from '@inertiajs/svelte';
    import { fly } from 'svelte/transition';
    import { onMount, tick } from 'svelte';

    const breadcrumbs = [
        { title: 'Emails', url: route('web.admin.mail-simulator.index'), active: false },
        { title: 'Index', url: route('web.admin.mail-simulator.index'), active: true },
    ];

    const pageTitle = 'Emails';

    let emails = [];
    let pagination = {};
    let loading = true;
    let perPage = 15;
    let currentPage = 1;
    let showCreateForm = false;
    let selectedEmail = null;

    async function fetchEmails() {
        loading = true;
        try {
            const response = await fetch(
                route('api.v1.admin.mail-simulator.index', {
                    page: currentPage,
                    per_page: perPage,
                }),
                { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
            );
            const data = await response.json();
            emails = data.emails || [];
            pagination = data.pagination || {};
            await tick();
            if (window.KTMenu) window.KTMenu.init();
        } catch (error) {
            console.error('Error fetching emails:', error);
        } finally {
            loading = false;
        }
    }

    function handleCreateFormToggle() {
        showCreateForm = !showCreateForm;
    }

    function handleFormCreated() {
        showCreateForm = false;
        fetchEmails();
    }

    function handleFormCanceled() {
        showCreateForm = false;
    }

    function handlePageChange(event) {
        currentPage = event.detail.page;
        fetchEmails();
    }

    function handlePerPageChange(event) {
        perPage = event.detail.perPage;
        currentPage = 1;
        fetchEmails();
    }

    function openView(email) {
        selectedEmail = email;
    }

    function closeView() {
        selectedEmail = null;
    }

    function handleEmailUpdated() {
        fetchEmails();
    }

    function directionBadgeClass(direction) {
        return direction === 'inbound'
            ? 'bg-amber-500/15 text-amber-700'
            : 'bg-blue-500/15 text-blue-700';
    }

    function formatDirection(direction) {
        return direction === 'inbound' ? 'Received' : 'Sent';
    }

    function formatParty(email) {
        if (email.direction === 'inbound') {
            return email.from_name || email.from_email || '—';
        }
        return email.to_name || email.to_email || '—';
    }

    onMount(fetchEmails);
</script>

<svelte:head>
    <title>Novonordisk supply chain management system - {pageTitle}</title>
</svelte:head>

<MainLayout {breadcrumbs} {pageTitle}>
    <div class="grid gap-5 lg:gap-7.5">
        <div class="kt-card kt-card-grid min-w-full overflow-hidden">
            <div class="kt-card w-full border-0">
                <div class="kt-card-header">
                    <div class="kt-card-toolbar flex items-center justify-between w-full">
                        {#if !showCreateForm}
                            <p class="text-sm text-muted-foreground m-0">
                                Prototype inbox — receive emails manually; outbound emails are created by AI agents.
                            </p>
                            <button
                                type="button"
                                class="kt-btn kt-btn-sm kt-btn-primary"
                                on:click={handleCreateFormToggle}
                            >
                                <i class="fa-solid fa-plus mr-1"></i>
                                Receive email
                            </button>
                        {:else}
                            <button
                                type="button"
                                class="kt-btn kt-btn-sm kt-btn-secondary"
                                on:click={handleCreateFormToggle}
                            >
                                <i class="fa-solid fa-arrow-left mr-1"></i>
                                Back to list
                            </button>
                        {/if}
                    </div>
                </div>

                {#if showCreateForm}
                    <div class="kt-card-content p-4" in:fly={{ x: '100%', duration: 500 }}>
                        <CreateForm on:created={handleFormCreated} on:canceled={handleFormCanceled} />
                    </div>
                {:else}
                    <div class="kt-card-content p-0" in:fly={{ x: '-100%', duration: 500 }}>
                        <div class="kt-scrollable-x-auto kt-card-table">
                            <table class="kt-table kt-table-auto kt-table-border text-sm">
                                <thead>
                                    <tr>
                                        <th><span class="kt-table-col">Direction</span></th>
                                        <th><span class="kt-table-col">Subject</span></th>
                                        <th><span class="kt-table-col">Party</span></th>
                                        <th><span class="kt-table-col">Status</span></th>
                                        <th><span class="kt-table-col">Chat</span></th>
                                        <th style="width: 80px;"><span class="kt-table-col">Actions</span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {#if loading}
                                        <tr>
                                            <td colspan="6" class="text-center py-10 text-muted-foreground">Loading…</td>
                                        </tr>
                                    {:else if emails.length === 0}
                                        <tr>
                                            <td colspan="6" class="text-center py-10 text-muted-foreground">
                                                No emails yet. Use <strong>Receive email</strong> to simulate an inbound message.
                                            </td>
                                        </tr>
                                    {:else}
                                        {#each emails as email (email.id)}
                                            <tr>
                                                <td>
                                                    <span class="text-[10px] font-semibold uppercase px-1.5 py-0.5 rounded {directionBadgeClass(email.direction)}">
                                                        {formatDirection(email.direction)}
                                                    </span>
                                                </td>
                                                <td class="max-w-[240px] truncate font-medium">{email.subject || '(no subject)'}</td>
                                                <td class="max-w-[180px] truncate text-muted-foreground">{formatParty(email)}</td>
                                                <td class="text-muted-foreground capitalize">{email.processing_status || email.status}</td>
                                                <td>
                                                    {#if email.chat_id}
                                                        <a
                                                            href={route('web.admin.chats.index', {}, false) + '?chat=' + email.chat_id}
                                                            use:inertia
                                                            class="text-primary text-xs hover:underline"
                                                        >
                                                            Open
                                                        </a>
                                                    {:else}
                                                        <span class="text-muted-foreground">—</span>
                                                    {/if}
                                                </td>
                                                <td>
                                                    <button
                                                        type="button"
                                                        class="kt-btn kt-btn-xs kt-btn-ghost"
                                                        on:click={() => openView(email)}
                                                    >
                                                        View
                                                    </button>
                                                </td>
                                            </tr>
                                        {/each}
                                    {/if}
                                </tbody>
                            </table>
                        </div>

                        {#if pagination?.total > 0}
                            <div class="kt-card-footer border-t border-border">
                                <Pagination
                                    {pagination}
                                    {perPage}
                                    on:pageChange={handlePageChange}
                                    on:perPageChange={handlePerPageChange}
                                />
                            </div>
                        {/if}
                    </div>
                {/if}
            </div>
        </div>
    </div>

    <ViewDrawer email={selectedEmail} on:close={closeView} on:updated={handleEmailUpdated} />
</MainLayout>
