<script>
    import AdminLayout from '../Layouts/AdminLayout.svelte';
    import Pagination from '../Components/Pagination.svelte';
    import Select2 from '../Components/Forms/Select2.svelte';
    import { onMount, tick } from 'svelte';
    import { page } from '@inertiajs/svelte'

    // Define breadcrumbs for this page
    const breadcrumbs = [
        {
            title: 'Settings',
            url: route('admin.settings.index'),
            active: false
        },
        {
            title: 'Index',
            url: route('admin.settings.index'),
            active: true
        }
    ];
    
    const pageTitle = 'Settings';

    // Reactive variables
    let settings = [];
    let pagination = {};
    let loading = true;
    let search = '';
    let perPage = 10;
    let currentPage = 1;
    let searchTimeout;
    let editingSettings = new Set();
    let originalContent = {};
    


    // Fetch settings data
    async function fetchSettings() {
        loading = true;
        try {
            const params = new URLSearchParams({
                page: currentPage,
                perPage: perPage,
                search: search
            });
            
            const response = await fetch(`${route('admin.settings.index')}?${params}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const data = await response.json();
            settings = data.settings;
            pagination = data.pagination;
            
            // Store original content for comparison and initialize updating state
            settings.forEach(setting => {
                // Handle array values that come as JSON strings
                if (setting.type === 'array' && typeof setting.value === 'string') {
                    try {
                        setting.value = JSON.parse(setting.value);
                    } catch {
                        setting.value = [];
                    }
                }
                originalContent[setting.id] = setting.value;
                setting.updating = false; // Initialize updating state
            });
            
            // Wait for DOM to update, then initialize menus
            await tick();
            if (window.KTMenu) {
                window.KTMenu.init();
            }
        } catch (error) {
            console.error('Error fetching settings:', error);
        } finally {
            loading = false;
        }
    }

    // Handle search with debouncing
    function handleSearch() {
        // Clear existing timeout
        if (searchTimeout) {
            clearTimeout(searchTimeout);
        }
        
        // Set new timeout for 500ms
        searchTimeout = setTimeout(() => {
            currentPage = 1;
            fetchSettings();
        }, 500);
    }

    // Handle search input change
    function handleSearchInput(event) {
        search = event.target.value;
        handleSearch();
    }

    // Handle pagination
    function goToPage(page) {
        if (page && page !== currentPage) {
            currentPage = page;
            fetchSettings();
        }
    }

    // Handle per page change
    function handlePerPageChange(newPerPage) {
        perPage = newPerPage;
        currentPage = 1;
        fetchSettings();
    }

    // Handle value change
    function handleValueChange(setting, newValue) {
        // Handle different value types
        if (setting.type === 'array') {
            setting.value = Array.isArray(newValue) ? newValue : [newValue];
        } else if (setting.type === 'page') {
            setting.value = newValue.data?.id || newValue;
        } else {
            setting.value = newValue;
        }
        
        // Compare with original content
        const originalValue = originalContent[setting.id];
        let currentValue = setting.value;
        
        // For arrays, ensure we're comparing arrays
        if (setting.type === 'array') {
            if (typeof originalValue === 'string') {
                try {
                    const parsed = JSON.parse(originalValue);
                    originalContent[setting.id] = Array.isArray(parsed) ? parsed : [];
                } catch {
                    originalContent[setting.id] = [];
                }
            }
        }
        
        if (JSON.stringify(currentValue) !== JSON.stringify(originalContent[setting.id])) {
            editingSettings.add(setting.id);
        } else {
            editingSettings.delete(setting.id);
        }
    }

    // Toggle page selection mode
    function togglePageSelection(setting) {
        setting.showingSelect = !setting.showingSelect;
        settings = settings; // Trigger reactivity
    }

    // Update setting
    async function updateSetting(setting) {
        // Set loading state for this specific setting
        setting.updating = true;
        settings = settings; // Trigger reactivity
        
        try {
            const formData = new FormData();
            
            // Handle different value types for submission
            let valueToSend = setting.value;
            if (setting.type === 'array') {
                valueToSend = JSON.stringify(setting.value);
            }
            
            formData.append('value', valueToSend);

            const response = await fetch(route('admin.settings.update', { setting: setting.id }), {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                },
                body: formData
            });

            if (response.ok) {
                const data = await response.json();
                
                // Update original content
                if (setting.type === 'array') {
                    originalContent[setting.id] = [...setting.value]; // Create a copy of the array
                } else {
                    originalContent[setting.id] = setting.value;
                }
                editingSettings.delete(setting.id);

                // Show success toast
                KTToast.show({
                    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-info-icon lucide-info"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>`,
                    message: data.message || "Setting updated successfully!",
                    variant: "success",
                    position: "bottom-right",
                });
            } else {
                const errorData = await response.json().catch(() => ({}));
                const errorMessage = errorData.message || 'Error updating setting. Please try again.';
                
                KTToast.show({
                    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-info-icon lucide-info"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>`,
                    message: errorMessage,
                    variant: "destructive",
                    position: "bottom-right",
                });
            }
        } catch (error) {
            console.error('Error updating setting:', error);
            
            KTToast.show({
                icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-info-icon lucide-info"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>`,
                message: "Network error. Please check your connection and try again.",
                variant: "destructive",
                position: "bottom-right",
            });
        } finally {
            // Clear loading state
            setting.updating = false;
            settings = settings; 
        }
    }

    // Add array item
    function addArrayItem(setting) {
        setting.value = [...setting.value, ''];
        settings = settings; 
        handleValueChange(setting, setting.value);
    }

    // Remove array item
    function removeArrayItem(setting, index) {
        setting.value = setting.value.filter((_, i) => i !== index);
        settings = settings; 
        handleValueChange(setting, setting.value);
    }

    // Update array item
    function updateArrayItem(setting, index, value) {
        setting.value[index] = value;
        settings = settings; 
        handleValueChange(setting, setting.value);
    }

    onMount(() => {
        fetchSettings();
    });

    // Flash message handling
    export let success;

    $: if (success) {
        KTToast.show({
            icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-info-icon lucide-info"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>`,
            message: success,
            variant: "success",
            position: "bottom-right",
        });
    }
</script>

<svelte:head>
    <title>Saud international schools - {pageTitle}</title>
</svelte:head>

<AdminLayout {breadcrumbs} {pageTitle}>
    <!-- Container -->
    <div class="kt-container-fixed">
        <div class="grid gap-5 lg:gap-7.5">
            <!-- Settings Header -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <h1 class="text-2xl font-bold text-mono">Settings Management</h1>
                    <p class="text-sm text-secondary-foreground">
                        Manage system settings and configurations
                    </p>
                </div>
            </div>

            <!-- Settings Table -->
            <div class="kt-card">
                <div class="kt-card-header">
                    <div class="kt-card-toolbar">
                        <div class="kt-input max-w-64 w-64">
                            <i class="ki-filled ki-magnifier"></i>
                            <input 
                                type="text" 
                                class="kt-input" 
                                placeholder="Search settings..." 
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
                                        <input class="kt-checkbox kt-checkbox-sm" type="checkbox"/>
                                    </th>
                                    <th class="w-[80px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">ID</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[200px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Key</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[250px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Description</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[300px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Value</span>
                                        </span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {#if loading}
                                    <!-- Loading skeleton rows -->
                                    {#each Array(perPage) as _, i}
                                        <tr>
                                            <td class="p-4">
                                                <div class="kt-skeleton w-4 h-4 rounded"></div>
                                            </td>
                                            <td class="p-4">
                                                <div class="kt-skeleton w-20 h-4 rounded"></div>
                                            </td>
                                            <td class="p-4">
                                                <div class="kt-skeleton w-24 h-4 rounded"></div>
                                            </td>
                                            <td class="p-4">
                                                <div class="kt-skeleton w-32 h-4 rounded"></div>
                                            </td>
                                            <td class="p-4">
                                                <div class="kt-skeleton w-32 h-4 rounded"></div>
                                            </td>
                                        </tr>
                                    {/each}
                                {:else if settings.length === 0}
                                    <!-- Empty state -->
                                    <tr>
                                        <td colspan="8" class="p-10">
                                            <div class="flex flex-col items-center justify-center text-center">
                                                <div class="mb-4">
                                                    <i class="ki-filled ki-setting-3 text-4xl text-muted-foreground"></i>
                                                </div>
                                                <h3 class="text-lg font-semibold text-mono mb-2">No settings found</h3>
                                                <p class="text-sm text-secondary-foreground mb-4">
                                                    {search ? 'No settings match your search criteria.' : 'No settings available.'}
                                                </p>
                                            </div>
                                        </td>
                                    </tr>
                                {:else}
                                    <!-- Actual data rows -->
                                    {#each settings as setting}
                                        <tr class="hover:bg-muted/50">
                                            <td>
                                                <input class="kt-checkbox kt-checkbox-sm" type="checkbox" value={setting.id}/>
                                            </td>
                                            <td>
                                                <span class="text-sm font-medium text-mono">#{setting.id}</span>
                                            </td>
                                            <td>
                                                <span class="text-sm font-medium text-mono">
                                                    {setting.key}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="text-sm text-secondary-foreground">
                                                    {setting.description}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="flex items-center gap-2">
                                                    {#if setting.type === 'text'}
                                                        <input
                                                            type="text"
                                                            class="kt-input flex-1"
                                                            value={setting.value || ''}
                                                            on:input={(e) => handleValueChange(setting, e.target.value)}
                                                        />
                                                    {:else if setting.type === 'page'}
                                                        {#if setting.page && !setting.showingSelect}
                                                            <div class="flex items-center gap-2 flex-1">
                                                                <span class="kt-badge kt-badge-outline kt-badge-success flex-1 text-left">
                                                                    <i class="ki-filled ki-abstract-26 text-sm me-1"></i>
                                                                    {setting.page.name}
                                                                </span>
                                                                <button 
                                                                    class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost"
                                                                    on:click={() => togglePageSelection(setting)}
                                                                    title="Change page selection"
                                                                >
                                                                    <i class="ki-filled ki-cross text-sm"></i>
                                                                </button>
                                                            </div>
                                                        {:else}
                                                            <div class="flex-1">
                                                                <Select2
                                                                    id="page-select-{setting.id}"
                                                                    placeholder="Select a page..."
                                                                    bind:value={setting.value}
                                                                    on:select={(e) => {
                                                                        handleValueChange(setting, e.detail);
                                                                        // Update the page data for the badge
                                                                        setting.page = {
                                                                            id: e.detail.data.id,
                                                                            name: e.detail.data.text
                                                                        };
                                                                        setting.showingSelect = false;
                                                                        settings = settings; // Trigger reactivity
                                                                    }}
                                                                    data={[]}
                                                                    ajax={{
                                                                        url: route('admin.pages.index'),
                                                                        dataType: 'json',
                                                                        delay: 300,
                                                                        data: function(params) {
                                                                            return {
                                                                                search: params.term,
                                                                                perPage: 10
                                                                            };
                                                                        },
                                                                        processResults: function(data) {
                                                                            return {
                                                                                results: data.pages.map(page => ({
                                                                                    id: page.id,
                                                                                    text: page.name
                                                                                }))
                                                                            };
                                                                        },
                                                                        cache: true
                                                                    }}
                                                                />
                                                            </div>
                                                        {/if}
                                                    {:else if setting.type === 'array'}
                                                        <div class="flex flex-col gap-2 flex-1">
                                                            {#each setting.value as item, index}
                                                                <div class="flex items-center gap-2">
                                                                    <input
                                                                        type="text"
                                                                        class="kt-input flex-1"
                                                                        placeholder="Enter value..."
                                                                        value={item}
                                                                        on:input={(e) => updateArrayItem(setting, index, e.target.value)}
                                                                    />
                                                                    <button
                                                                        type="button"
                                                                        class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost text-destructive hover:text-destructive"
                                                                        on:click={() => removeArrayItem(setting, index)}
                                                                        title="Remove item"
                                                                    >
                                                                        <i class="ki-filled ki-cross text-sm"></i>
                                                                    </button>
                                                                </div>
                                                            {/each}
                                                            <button
                                                                type="button"
                                                                class="kt-btn kt-btn-sm kt-btn-outline w-fit"
                                                                on:click={() => addArrayItem(setting)}
                                                            >
                                                                <i class="ki-filled ki-plus text-sm"></i>
                                                                Add Item
                                                            </button>
                                                        </div>
                                                    {/if}
                                                    <button
                                                        class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost"
                                                        on:click={() => updateSetting(setting)}
                                                        disabled={setting.updating}
                                                    >
                                                        {#if setting.updating}
                                                            <i class="ki-outline ki-loading text-sm animate-spin"></i>
                                                        {:else}
                                                            <i class="ki-filled ki-save-2 text-sm"></i>
                                                        {/if}
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    {/each}
                                {/if}
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
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
    <!-- End of Container -->
</AdminLayout> 