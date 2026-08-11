<script>
    import AdminLayout from '../Layouts/AdminLayout.svelte';
    import Pagination from '../Components/Pagination.svelte';
    import Select2 from '../Components/Forms/Select2.svelte';
    import { onMount, tick } from 'svelte';
    import { page } from '@inertiajs/svelte'

    // Define breadcrumbs for this page
    const breadcrumbs = [
        {
            title: 'Language Keys',
            url: route('admin.language-keys.index'),
            active: false
        },
        {
            title: 'Index',
            url: route('admin.language-keys.index'),
            active: true
        }
    ];
    
    const pageTitle = 'Language Keys';

    // Reactive variables
    let languageKeys = [];
    let pagination = {};
    let loading = true;
    let search = '';
    let perPage = 10;
    let currentPage = 1;
    let searchTimeout;
    let selectedLanguage = null;
    let editingKeys = new Set();
    let originalContent = {};

    // Fetch languages for the selector
    async function fetchLanguages() {
        // This is now handled by AJAX in the Select2 component
        languagesLoading = false;
    }

    // Fetch language keys data
    async function fetchLanguageKeys() {
        if (!selectedLanguage) {
            languageKeys = [];
            pagination = {};
            loading = false;
            return;
        }

        loading = true;
        try {
            const params = new URLSearchParams({
                page: currentPage,
                perPage: perPage,
                search: search,
                language_id: selectedLanguage
            });
            
            const response = await fetch(`${route('admin.language-keys.index')}?${params}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const data = await response.json();
            languageKeys = data.languageKeys;
            pagination = data.pagination;
            
            // Store original content for comparison and initialize updating state
            languageKeys.forEach(key => {
                originalContent[key.id] = key.content;
                key.updating = false; // Initialize updating state
            });
            
            // Wait for DOM to update, then initialize menus
            await tick();
            if (window.KTMenu) {
                window.KTMenu.init();
            }
        } catch (error) {
            console.error('Error fetching language keys:', error);
        } finally {
            loading = false;
        }
    }

    // Watch for language selection changes
    $: if (selectedLanguage) {
        currentPage = 1;
        editingKeys.clear();
        originalContent = {};
        fetchLanguageKeys();
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
            fetchLanguageKeys();
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
            fetchLanguageKeys();
        }
    }

    // Handle per page change
    function handlePerPageChange(newPerPage) {
        perPage = newPerPage;
        currentPage = 1;
        fetchLanguageKeys();
    }

    // Handle content change
    function handleContentChange(languageKey, newContent) {
        languageKey.content = newContent;
        if (newContent !== originalContent[languageKey.id]) {
            editingKeys.add(languageKey.id);
        } else {
            editingKeys.delete(languageKey.id);
        }
    }

    // Update translation
    async function updateTranslation(languageKey) {
        // Set loading state for this specific language key
        languageKey.updating = true;
        languageKeys = languageKeys; // Trigger reactivity
        
        try {
            const formData = new FormData();
            formData.append('content', languageKey.content);
            formData.append('language_id', selectedLanguage);
            formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'));

            const response = await fetch(route('admin.language-keys.update-translation', { languageKey: languageKey.id }), {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });

            if (response.ok) {
                const data = await response.json();
                
                // Update original content
                originalContent[languageKey.id] = languageKey.content;
                editingKeys.delete(languageKey.id);

                // Show success toast
                KTToast.show({
                    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-info-icon lucide-info"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>`,
                    message: data.message || "Translation updated successfully!",
                    variant: "success",
                    position: "bottom-right",
                });
            } else {
                const errorData = await response.json().catch(() => ({}));
                const errorMessage = errorData.message || 'Error updating translation. Please try again.';
                
                KTToast.show({
                    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-info-icon lucide-info"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>`,
                    message: errorMessage,
                    variant: "destructive",
                    position: "bottom-right",
                });
            }
        } catch (error) {
            console.error('Error updating translation:', error);
            
            KTToast.show({
                icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-info-icon lucide-info"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>`,
                message: "Network error. Please check your connection and try again.",
                variant: "destructive",
                position: "bottom-right",
            });
        } finally {
            // Clear loading state
            languageKey.updating = false;
            languageKeys = languageKeys; // Trigger reactivity
        }
    }

    onMount(() => {
        // No need to fetch languages as it's handled by AJAX
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
            <!-- Language Keys Header -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <h1 class="text-2xl font-bold text-mono">Language Keys Management</h1>
                    <p class="text-sm text-secondary-foreground">
                        Manage translations for your website content
                    </p>
                </div>
            </div>

            <!-- Language Selector -->
            <div class="kt-card">
                <div class="kt-card-header">
                    <h4 class="kt-card-title">Select Language</h4>
                </div>
                <div class="kt-card-content">
                    <div class="max-w-md">
                        <Select2
                            id="language-filter"
                            placeholder="Choose a language to manage translations..."
                            bind:value={selectedLanguage}
                            data={[]}
                            ajax={{
                                url: route('admin.languages.index'),
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
                                        results: data.languages.map(language => ({
                                            id: language.id,
                                            text: `${language.name} (${language.code})`
                                        }))
                                    };
                                },
                                cache: true
                            }}
                        />
                    </div>
                </div>
            </div>

            <!-- Language Keys Table -->
            {#if selectedLanguage}
                <div class="kt-card">
                    <div class="kt-card-header">
                        <div class="kt-card-toolbar">
                            <div class="kt-input max-w-64 w-64">
                                <i class="ki-filled ki-magnifier"></i>
                                <input 
                                    type="text" 
                                    class="kt-input" 
                                    placeholder="Search language keys..." 
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
                                        <th class="min-w-[300px]">
                                            <span class="kt-table-col">
                                                <span class="kt-table-col-label">Content</span>
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
                                                    <div class="kt-skeleton w-8 h-4 rounded"></div>
                                                </td>
                                                <td class="p-4">
                                                    <div class="kt-skeleton w-24 h-4 rounded"></div>
                                                </td>
                                                <td class="p-4">
                                                    <div class="kt-skeleton w-32 h-4 rounded"></div>
                                                </td>
                                            </tr>
                                        {/each}
                                    {:else if languageKeys.length === 0}
                                        <!-- Empty state -->
                                        <tr>
                                            <td colspan="5" class="p-10">
                                                <div class="flex flex-col items-center justify-center text-center">
                                                    <div class="mb-4">
                                                        <i class="ki-filled ki-document text-4xl text-muted-foreground"></i>
                                                    </div>
                                                    <h3 class="text-lg font-semibold text-mono mb-2">No language keys found</h3>
                                                    <p class="text-sm text-secondary-foreground mb-4">
                                                        {search ? 'No language keys match your search criteria.' : 'No language keys available for this language.'}
                                                    </p>
                                                </div>
                                            </td>
                                        </tr>
                                    {:else}
                                        <!-- Actual data rows -->
                                        {#each languageKeys as languageKey}
                                            <tr class="hover:bg-muted/50">
                                                <td>
                                                    <input class="kt-checkbox kt-checkbox-sm" type="checkbox" value={languageKey.id}/>
                                                </td>
                                                <td>
                                                    <span class="text-sm font-medium text-mono">#{languageKey.id}</span>
                                                </td>
                                                <td>
                                                    <span class="text-sm font-medium text-mono">
                                                        {languageKey.key}
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="flex items-center gap-2">
                                                        <input
                                                            type="text"
                                                            class="kt-input flex-1"
                                                            value={languageKey.content}
                                                            on:input={(e) => handleContentChange(languageKey, e.target.value)}
                                                        />
                                                        <button
                                                            class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost"
                                                            on:click={() => updateTranslation(languageKey)}
                                                            disabled={languageKey.updating}
                                                        >
                                                            {#if languageKey.updating}
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
            {/if}
        </div>
    </div>
    <!-- End of Container -->
</AdminLayout> 