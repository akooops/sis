<script>
    import { createEventDispatcher, tick } from 'svelte';
    import SearchBar from '../../../Shared/Utils/Forms/SearchBar.svelte';
    import Pagination from '../../../Shared/Utils/Pagination.svelte';
    import CreateForm from './CreateForm.svelte';
    import { fly } from 'svelte/transition';

    const dispatch = createEventDispatcher();

    export let productionSite = null;

    let siteResponsibles = [];
    let search = '';
    let loading = true;
    let errors = {};

    let perPage = 10;
    let currentPage = 1;
    let pagination = {};

    let showCreateForm = false;

    $: if (productionSite) {
        fetchSiteResponsibles();
    }

    function getTypeBadgeClass(type) {
        if (type === 'general') return 'kt-badge-info';
        if (type === 'purchasing') return 'kt-badge-primary';
        if (type === 'production') return 'kt-badge-success';
        if (type === 'planning') return 'kt-badge-warning';
        if (type === 'qa' || type === 'qc' || type === 'qp') return 'kt-badge-secondary';
        if (type === 'warehouse') return 'kt-badge-dark';
        return 'kt-badge-secondary';
    }

    async function fetchSiteResponsibles() {
        if (!productionSite) return;

        loading = true;
        errors = {};

        try {
            const queryParams = {
                productionSite: productionSite.id,
                page: currentPage,
                per_page: perPage,
                search: search,
                sort_direction: 'desc',
            };

            const response = await fetch(route('api.v1.admin.site-responsibles.index', queryParams), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
            });

            if (response.ok) {
                const data = await response.json();
                siteResponsibles = data.site_responsibles || [];
                pagination = data.pagination || {};

                await tick();

                if (window.KTMenu) {
                    window.KTMenu.init();
                }
            }
        } catch (error) {
            console.error('Error loading site responsibles:', error);
            errors = { general: 'Failed to load notification users. Please try again.' };
        } finally {
            loading = false;
        }
    }

    async function handleDeleteSiteResponsible(siteResponsible) {
        const userName = siteResponsible.user?.fullname || siteResponsible.user?.name || 'this user';
        const confirmed = confirm(`Are you sure you want to remove "${userName}" from ${siteResponsible.type_label} notifications?\n\nThis action cannot be undone.`);

        if (!confirmed) {
            return;
        }

        try {
            const response = await fetch(route('api.v1.admin.site-responsibles.destroy', { siteResponsible: siteResponsible.id }), {
                method: 'DELETE',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
            });

            const data = await response.json();

            if (response.ok) {
                toast('Notification user removed successfully', 'success');
                fetchSiteResponsibles();
                dispatch('responsiblesUpdated');
            } else if (response.status === 422 && data.errors) {
                let errorMessage = 'Cannot remove notification user:\n';
                Object.entries(data.errors).forEach(([field, message]) => {
                    errorMessage += `• ${message}\n`;
                });
                toast(errorMessage, 'error');
            } else {
                toast(data.message || 'An error occurred while removing the notification user.', 'error');
            }
        } catch (error) {
            console.error('Network error:', error);
            toast('Network error occurred. Please try again.', 'error');
        }
    }

    function handleSearchFromComponent(event) {
        search = event.detail.value;
        currentPage = 1;
        fetchSiteResponsibles();
    }

    function goToPage(page) {
        if (page && page !== currentPage) {
            currentPage = page;
            fetchSiteResponsibles();
        }
    }

    function handlePerPageChange(newPerPage) {
        perPage = newPerPage;
        currentPage = 1;
        fetchSiteResponsibles();
    }

    function showAddResponsibleForm() {
        showCreateForm = true;
    }

    function showResponsiblesTable() {
        showCreateForm = false;
    }

    function handleFormCreated() {
        showCreateForm = false;
        fetchSiteResponsibles();
        dispatch('responsiblesUpdated');
    }

    function handleFormCanceled() {
        showCreateForm = false;
    }
</script>

<div class="hidden kt-drawer kt-drawer-end card flex-col max-w-[95%] w-[600px] top-5 bottom-5 end-5 rounded-xl border border-border overflow-hidden-x" data-kt-drawer="true" data-kt-drawer-container="body" id="site_responsibles_drawer">
    <div class="flex items-center justify-between gap-2.5 text-sm text-mono font-semibold px-5 py-2.5 border-b border-b-border">
        <div class="flex items-center gap-2">
            Notification Users:
            {#if productionSite}
                <span class="text-muted-foreground">{productionSite.name}</span>
            {/if}
        </div>
        <button class="kt-btn kt-btn-sm kt-btn-icon kt-btn-dim shrink-0" data-kt-drawer-dismiss="true">
            <i class="ki-filled ki-cross"></i>
        </button>
    </div>

    <div class="kt-card w-full">
        <div class="kt-card-header">
            <div class="kt-card-toolbar flex items-center justify-between w-full">
                {#if showCreateForm}
                    <button
                        type="button"
                        class="kt-btn kt-btn-sm kt-btn-secondary"
                        on:click={showResponsiblesTable}
                    >
                        <i class="fa-solid fa-arrow-left mr-1"></i>
                        Back
                    </button>
                {:else}
                    <div class="flex items-center justify-between w-full gap-2">
                        <SearchBar
                            bind:value={search}
                            placeholder="Search users..."
                            debounceMs={500}
                            showScanner={false}
                            on:search={handleSearchFromComponent}
                        />

                        {#if hasPermission('site-responsibles.store')}
                            <button
                                type="button"
                                class="kt-btn kt-btn-sm kt-btn-primary shrink-0"
                                on:click={showAddResponsibleForm}
                            >
                                <i class="fa-solid fa-plus mr-1"></i>
                                Add Users
                            </button>
                        {/if}
                    </div>
                {/if}
            </div>
        </div>

        {#if showCreateForm}
            <div class="kt-card-content p-4" in:fly={{ x: '100%', duration: 750 }}>
                <CreateForm
                    {productionSite}
                    on:created={handleFormCreated}
                    on:canceled={handleFormCanceled}
                />
            </div>
        {:else}
            <div class="kt-card-content p-0" in:fly={{ x: '-100%', duration: 750 }}>
                {#if errors.general}
                    <div class="p-4">
                        <p class="text-sm text-destructive">{errors.general}</p>
                    </div>
                {/if}

                <div class="kt-scrollable-x-auto kt-card-table">
                    <table class="kt-table kt-table-auto kt-table-border text-sm">
                        <thead>
                            <tr>
                                <th style="width: 115px;">
                                    <span class="kt-table-col whitespace-nowrap capitalize">ID</span>
                                </th>
                                <th>
                                    <span class="kt-table-col whitespace-nowrap capitalize">User</span>
                                </th>
                                <th>
                                    <span class="kt-table-col whitespace-nowrap capitalize">Type</span>
                                </th>
                                <th class="w-[80px]">
                                    <span class="kt-table-col whitespace-nowrap capitalize">Actions</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {#if loading}
                                {#each Array(perPage) as _, i}
                                    <tr>
                                        <td class="p-4"><div class="kt-skeleton w-full h-4 rounded"></div></td>
                                        <td class="p-4"><div class="kt-skeleton w-full h-4 rounded"></div></td>
                                        <td class="p-4"><div class="kt-skeleton w-full h-4 rounded"></div></td>
                                        <td class="p-4"><div class="kt-skeleton w-8 h-8 rounded"></div></td>
                                    </tr>
                                {/each}
                            {:else if siteResponsibles.length === 0}
                                <tr>
                                    <td colspan="4" class="p-10">
                                        <div class="flex flex-col items-center justify-center text-center">
                                            <div class="mb-4">
                                                <i class="fa-solid fa-bell text-4xl text-muted-foreground"></i>
                                            </div>
                                            <h3 class="text-lg font-semibold text-mono mb-2">No notification users found</h3>
                                            <p class="text-sm text-secondary-foreground mb-4">
                                                {search ? 'No users match your search criteria.' : 'This site has no notification users assigned yet.'}
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            {:else}
                                {#each siteResponsibles as siteResponsible}
                                    <tr class="hover:bg-muted">
                                        <td>
                                            <a href={route('web.admin.users.index', { search: siteResponsible.user?.id })}>
                                                <span class="text-xs font-medium text-primary">#{siteResponsible.id}</span>
                                            </a>
                                        </td>
                                        <td>
                                            {#if siteResponsible.user}
                                                <div class="flex items-center gap-3 max-content">
                                                    <img
                                                        src={siteResponsible.user.avatar_url}
                                                        alt={siteResponsible.user.fullname || siteResponsible.user.name}
                                                        class="w-[30px] h-[30px] rounded-lg object-cover"
                                                    />
                                                    <div class="flex flex-col gap-1">
                                                        <span class="text-sm font-medium text-secondary-foreground">
                                                            {siteResponsible.user.fullname || siteResponsible.user.name}
                                                        </span>
                                                        {#if siteResponsible.user.email}
                                                            <span class="text-xs text-muted-foreground">{siteResponsible.user.email}</span>
                                                        {/if}
                                                    </div>
                                                </div>
                                            {:else}
                                                <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">N/A</span>
                                            {/if}
                                        </td>
                                        <td>
                                            {#if siteResponsible.type_label}
                                                <span class="kt-badge kt-badge-outline {getTypeBadgeClass(siteResponsible.type)} text-xs font-medium">
                                                    {siteResponsible.type_label}
                                                </span>
                                            {:else}
                                                <span class="kt-badge kt-badge-outline kt-badge-secondary text-xs font-medium capitalize">N/A</span>
                                            {/if}
                                        </td>
                                        <td class="text-center">
                                            <div class="kt-menu flex-inline" data-kt-menu="true">
                                                <div class="kt-menu-item" data-kt-menu-item-offset="0, 10px" data-kt-menu-item-placement="bottom-end" data-kt-menu-item-placement-rtl="bottom-start" data-kt-menu-item-toggle="dropdown" data-kt-menu-item-trigger="click">
                                                    <button class="kt-menu-toggle kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" aria-label="Open actions menu">
                                                        <i class="ki-filled ki-dots-vertical text-lg"></i>
                                                    </button>
                                                    <div class="kt-menu-dropdown kt-menu-default w-full max-w-[175px]">
                                                        {#if hasPermission('site-responsibles.destroy')}
                                                            <div class="kt-menu-item">
                                                                <button class="kt-menu-link text-destructive" data-kt-menu-dismiss="true" on:click={() => handleDeleteSiteResponsible(siteResponsible)}>
                                                                    <span class="kt-menu-icon">
                                                                        <i class="fa-solid fa-trash-can"></i>
                                                                    </span>
                                                                    <span class="kt-menu-title">Delete</span>
                                                                </button>
                                                            </div>
                                                        {/if}
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                {/each}
                            {/if}
                        </tbody>
                    </table>
                </div>

                {#if pagination && pagination.total > 0}
                    <Pagination
                        {pagination}
                        {perPage}
                        onPageChange={goToPage}
                        onPerPageChange={handlePerPageChange}
                    />
                {/if}
            </div>
        {/if}
    </div>
</div>
