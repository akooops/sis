<script>
    import AdminLayout from '../Layouts/AdminLayout.svelte';
    import Pagination from '../Components/Pagination.svelte';
    import { onMount, tick } from 'svelte';

    const breadcrumbs = [
        {
            title: 'Nationalities',
            url: route('admin.nationalities.index'),
            active: false
        },
        {
            title: 'Index',
            url: route('admin.nationalities.index'),
            active: true
        }
    ];

    const pageTitle = 'Nationalities';

    let nationalities = [];
    let pagination = {};
    let loading = true;
    let search = '';
    let perPage = 10;
    let currentPage = 1;
    let searchTimeout;

    async function fetchNationalities() {
        loading = true;
        try {
            const response = await fetch(route('admin.nationalities.index', {
                page: currentPage,
                perPage: perPage,
                search: search
            }), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();
            nationalities = data.nationalities;
            pagination = data.pagination;

            await tick();
            if (window.KTMenu) {
                window.KTMenu.init();
            }
        } catch (error) {
            console.error('Error fetching nationalities:', error);
        } finally {
            loading = false;
        }
    }

    function handleSearch() {
        if (searchTimeout) {
            clearTimeout(searchTimeout);
        }

        searchTimeout = setTimeout(() => {
            currentPage = 1;
            fetchNationalities();
        }, 500);
    }

    function handleSearchInput(event) {
        search = event.target.value;
        handleSearch();
    }

    function goToPage(page) {
        if (page && page !== currentPage) {
            currentPage = page;
            fetchNationalities();
        }
    }

    function handlePerPageChange(newPerPage) {
        perPage = newPerPage;
        currentPage = 1;
        fetchNationalities();
    }

    async function deleteNationality(nationalityId) {
        if (!confirm('Are you sure you want to delete this nationality? This action cannot be undone.')) {
            return;
        }

        try {
            const formData = new FormData();
            formData.append('_method', 'DELETE');
            formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'));

            const response = await fetch(route('admin.nationalities.destroy', { nationality: nationalityId }), {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });

            if (response.ok) {
                KTToast.show({
                    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-info-icon lucide-info"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>`,
                    message: 'Nationality deleted successfully!',
                    variant: 'success',
                    position: 'bottom-right',
                });

                fetchNationalities();
            } else {
                KTToast.show({
                    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-info-icon lucide-info"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>`,
                    message: 'Error deleting nationality. Please try again.',
                    variant: 'destructive',
                    position: 'bottom-right',
                });
            }
        } catch (error) {
            console.error('Error deleting nationality:', error);
            KTToast.show({
                icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-info-icon lucide-info"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>`,
                message: 'Network error. Please check your connection and try again.',
                variant: 'destructive',
                position: 'bottom-right',
            });
        }
    }

    onMount(() => {
        fetchNationalities();
    });

    export let success;

    $: if (success) {
        KTToast.show({
            icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-info-icon lucide-info"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>`,
            message: success,
            variant: 'success',
            position: 'bottom-right',
        });
    }
</script>

<svelte:head>
    <title>Saud international schools - {pageTitle}</title>
</svelte:head>

<AdminLayout {breadcrumbs} {pageTitle}>
    <div class="kt-container-fixed">
        <div class="grid gap-5 lg:gap-7.5">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <h1 class="text-2xl font-bold text-mono">Nationalities Management</h1>
                    <p class="text-sm text-secondary-foreground">
                        Manage available nationalities for job applications
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    {#if hasPermission('admin.nationalities.store')}
                        <a href={route('admin.nationalities.create')} class="kt-btn kt-btn-primary">
                            <i class="ki-filled ki-plus text-base"></i>
                            Add New Nationality
                        </a>
                    {/if}
                </div>
            </div>

            <div class="kt-card">
                <div class="kt-card-header">
                    <div class="kt-card-toolbar">
                        <div class="kt-input max-w-64 w-64">
                            <i class="ki-filled ki-magnifier"></i>
                            <input
                                type="text"
                                class="kt-input"
                                placeholder="Search nationalities..."
                                bind:value={search}
                                on:input={handleSearchInput}
                            />
                        </div>
                    </div>
                </div>

                <div class="kt-card-content p-0">
                    <div class="kt-scrollable-x-auto">
                        <table class="kt-table kt-table-border table-fixed">
                            <thead>
                                <tr>
                                    <th class="w-[50px]">
                                        <input class="kt-checkbox kt-checkbox-sm" type="checkbox" />
                                    </th>
                                    <th class="w-[80px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">ID</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[180px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Name</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[180px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Code</span>
                                        </span>
                                    </th>
                                    <th class="w-[80px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Actions</span>
                                        </span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {#if loading}
                                    {#each Array(perPage) as _, i}
                                        <tr>
                                            <td class="p-4"><div class="kt-skeleton w-4 h-4 rounded"></div></td>
                                            <td class="p-4"><div class="kt-skeleton w-8 h-4 rounded"></div></td>
                                            <td class="p-4"><div class="kt-skeleton w-40 h-4 rounded"></div></td>
                                            <td class="p-4"><div class="kt-skeleton w-12 h-6 rounded"></div></td>
                                            <td class="p-4"><div class="kt-skeleton w-8 h-8 rounded"></div></td>
                                        </tr>
                                    {/each}
                                {:else if nationalities.length === 0}
                                    <tr>
                                        <td colspan="5" class="p-10">
                                            <div class="flex flex-col items-center justify-center text-center">
                                                <div class="mb-4">
                                                    <i class="ki-filled ki-document text-4xl text-muted-foreground"></i>
                                                </div>
                                                <h3 class="text-lg font-semibold text-mono mb-2">No nationalities found</h3>
                                                <p class="text-sm text-secondary-foreground mb-4">
                                                    {search ? 'No nationalities match your search criteria.' : 'Get started by creating your first nationality.'}
                                                </p>
                                                {#if hasPermission('admin.nationalities.store')}
                                                    <a href={route('admin.nationalities.create')} class="kt-btn kt-btn-primary">
                                                        <i class="ki-filled ki-plus text-base"></i>
                                                        Create First Nationality
                                                    </a>
                                                {/if}
                                            </div>
                                        </td>
                                    </tr>
                                {:else}
                                    {#each nationalities as nationality}
                                        <tr class="hover:bg-muted/50">
                                            <td><input class="kt-checkbox kt-checkbox-sm" type="checkbox" value={nationality.id} /></td>
                                            <td><span class="text-sm font-medium text-mono">#{nationality.id}</span></td>
                                            <td>
                                                <span class="text-sm text-mono">{nationality.name}</span>
                                            </td>
                                            <td>
                                                <span class="kt-badge kt-badge-outline kt-badge-primary">
                                                    {nationality.code}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <div class="kt-menu flex-inline" data-kt-menu="true">
                                                    <div class="kt-menu-item" data-kt-menu-item-offset="0, 10px" data-kt-menu-item-placement="bottom-end" data-kt-menu-item-placement-rtl="bottom-start" data-kt-menu-item-toggle="dropdown" data-kt-menu-item-trigger="click">
                                                        <button class="kt-menu-toggle kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" aria-label="Open actions menu">
                                                            <i class="ki-filled ki-dots-vertical text-lg"></i>
                                                        </button>
                                                        <div class="kt-menu-dropdown kt-menu-default w-full max-w-[175px]" data-kt-menu-dismiss="true">
                                                            {#if hasPermission('admin.nationalities.show')}
                                                                <div class="kt-menu-item">
                                                                    <a class="kt-menu-link" href={route('admin.nationalities.show', { nationality: nationality.id })}>
                                                                        <span class="kt-menu-icon"><i class="ki-filled ki-search-list"></i></span>
                                                                        <span class="kt-menu-title">View</span>
                                                                    </a>
                                                                </div>
                                                            {/if}
                                                            {#if hasPermission('admin.nationalities.update')}
                                                                <div class="kt-menu-item">
                                                                    <a class="kt-menu-link" href={route('admin.nationalities.edit', { nationality: nationality.id })}>
                                                                        <span class="kt-menu-icon"><i class="ki-filled ki-pencil"></i></span>
                                                                        <span class="kt-menu-title">Edit</span>
                                                                    </a>
                                                                </div>
                                                            {/if}
                                                            {#if hasPermission('admin.nationalities.destroy')}
                                                                <div class="kt-menu-separator"></div>
                                                                <div class="kt-menu-item">
                                                                    <button class="kt-menu-link" on:click={() => deleteNationality(nationality.id)}>
                                                                        <span class="kt-menu-icon"><i class="ki-filled ki-trash"></i></span>
                                                                        <span class="kt-menu-title">Remove</span>
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
            </div>
        </div>
    </div>
</AdminLayout>
