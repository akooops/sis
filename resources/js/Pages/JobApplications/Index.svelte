<script>
    import AdminLayout from '../Layouts/AdminLayout.svelte';
    import Pagination from '../Components/Pagination.svelte';
    import Select2 from '../Components/Forms/Select2.svelte';
    import { onMount, tick } from 'svelte';
    import { page } from '@inertiajs/svelte'

    // Props from the server
    export let jobPosting;
    export let applications;
    export let pagination;

    // Define breadcrumbs for this page
    const breadcrumbs = [
        {
            title: 'Job Postings',
            url: route('admin.job-postings.index'),
            active: false
        },
        {
            title: jobPosting?.getLocalTranslation?.('title') || jobPosting?.name || 'Job',
            url: route('admin.job-applications.index', { jobPosting: jobPosting?.id }),
            active: true
        }
    ];
    
    const pageTitle = 'Job Applications';

    // Reactive variables
    let applicationsList = applications || [];
    let paginationData = pagination || {};
    let loading = false;
    let search = '';
    let perPage = 10;
    let currentPage = 1;
    let searchTimeout;
    let aiScoreFilter = '';
    let showFilters = false;

    // Filter variables
    let educationInstitution = '';
    let educationDegree = '';
    let educationField = '';
    let educationStartYear = '';
    let educationEndYear = '';
    let workCompany = '';
    let workTitle = '';
    let workStartYear = '';
    let workEndYear = '';
    let languageName = '';
    let languageProficiency = '';
    let nationality = '';
    let skills = '';

    // Fetch applications data
    async function fetchApplications() {
        loading = true;
        try {
            const params = new URLSearchParams({
                page: currentPage,
                perPage: perPage,
                search: search
            });
            
            if (aiScoreFilter) {
                params.append('ai_score_min', aiScoreFilter);
            }

            // Add education filters
            if (educationInstitution) {
                params.append('education_institution', educationInstitution);
            }
            if (educationDegree) {
                params.append('education_degree', educationDegree);
            }
            if (educationField) {
                params.append('education_field', educationField);
            }
            if (educationStartYear) {
                params.append('education_start_year', educationStartYear);
            }
            if (educationEndYear) {
                params.append('education_end_year', educationEndYear);
            }

            // Add work experience filters
            if (workCompany) {
                params.append('work_company', workCompany);
            }
            if (workTitle) {
                params.append('work_title', workTitle);
            }
            if (workStartYear) {
                params.append('work_start_year', workStartYear);
            }
            if (workEndYear) {
                params.append('work_end_year', workEndYear);
            }

            // Add language filters
            if (languageName) {
                params.append('language_name', languageName);
            }
            if (languageProficiency) {
                params.append('language_proficiency', languageProficiency);
            }

            // Add personal filters
            if (nationality) {
                params.append('nationality', nationality);
            }
            if (skills) {
                params.append('skills', skills);
            }

            const response = await fetch(route('admin.job-applications.index', {
                jobPosting: jobPosting.id
            }) + '?' + params.toString(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const data = await response.json();
            applicationsList = data.applications;
            paginationData = data.pagination;
            
            // Wait for DOM to update, then initialize menus
            await tick();
            if (window.KTMenu) {
                window.KTMenu.init();
            }
        } catch (error) {
            console.error('Error fetching applications:', error);
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
            fetchApplications();
        }, 500);
    }

    // Handle search input change
    function handleSearchInput(event) {
        search = event.target.value;
        handleSearch();
    }

    // Handle AI score filter change
    function handleAiScoreFilter(event) {
        aiScoreFilter = event.target.value;
        currentPage = 1;
        fetchApplications();
    }

    // Handle filter changes
    function handleFilterChange() {
        currentPage = 1;
        fetchApplications();
    }

    // Clear all filters
    function clearAllFilters() {
        educationInstitution = '';
        educationDegree = '';
        educationField = '';
        educationStartYear = '';
        educationEndYear = '';
        workCompany = '';
        workTitle = '';
        workStartYear = '';
        workEndYear = '';
        languageName = '';
        languageProficiency = '';
        nationality = '';
        skills = '';
        aiScoreFilter = '';
        currentPage = 1;
        fetchApplications();
    }

    // Toggle filters visibility
    function toggleFilters() {
        showFilters = !showFilters;
    }

    // Reactive export URL that updates when filters change
    $: {
        const params = new URLSearchParams();
        
        if (search) params.append('search', search);
        if (aiScoreFilter) params.append('ai_score_min', aiScoreFilter);
        
        // Add education filters
        if (educationInstitution) params.append('education_institution', educationInstitution);
        if (educationDegree) params.append('education_degree', educationDegree);
        if (educationField) params.append('education_field', educationField);
        if (educationStartYear) params.append('education_start_year', educationStartYear);
        if (educationEndYear) params.append('education_end_year', educationEndYear);
        
        // Add work experience filters
        if (workCompany) params.append('work_company', workCompany);
        if (workTitle) params.append('work_title', workTitle);
        if (workStartYear) params.append('work_start_year', workStartYear);
        if (workEndYear) params.append('work_end_year', workEndYear);
        
        // Add language filters
        if (languageName) params.append('language_name', languageName);
        if (languageProficiency) params.append('language_proficiency', languageProficiency);
        
        // Add personal filters
        if (nationality) params.append('nationality', nationality);
        if (skills) params.append('skills', skills);
        
        const baseUrl = route('admin.job-applications.export', { jobPosting: jobPosting.id });
        exportUrl = params.toString() ? `${baseUrl}?${params.toString()}` : baseUrl;
    }

    // Export URL variable
    let exportUrl = route('admin.job-applications.export', { jobPosting: jobPosting.id });

    // Handle pagination
    function goToPage(page) {
        if (page && page !== currentPage) {
            currentPage = page;
            fetchApplications();
        }
    }

    // Handle per page change
    function handlePerPageChange(newPerPage) {
        perPage = newPerPage;
        currentPage = 1;
        fetchApplications();
    }

    // Delete application
    async function deleteApplication(applicationId) {
        if (!confirm('Are you sure you want to delete this application? This action cannot be undone.')) {
            return;
        }

        try {
            const formData = new FormData();
            formData.append('_method', 'DELETE');
            formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'));

            const response = await fetch(route('admin.job-applications.destroy', { application: applicationId }), {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });

            if (response.ok) {
                // Show success toast
                KTToast.show({
                    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check"><polyline points="20,6 9,17 4,12"/></svg>`,
                    message: "Application deleted successfully!",
                    variant: "success",
                    position: "bottom-right",
                });

                // Refresh the applications list
                fetchApplications();
            } else {
                const errorData = await response.json().catch(() => ({}));
                const errorMessage = errorData.message || 'Error deleting application. Please try again.';
                
                KTToast.show({
                    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-alert-circle"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`,
                    message: errorMessage,
                    variant: "error",
                    position: "bottom-right",
                });
            }
        } catch (error) {
            console.error('Error deleting application:', error);
            
            KTToast.show({
                icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-alert-circle"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`,
                message: "Network error. Please check your connection and try again.",
                variant: "error",
                position: "bottom-right",
            });
        }
    }

    // Get AI score badge class
    function getAiScoreBadgeClass(score) {
        if (!score) return 'kt-badge-secondary';
        if (score >= 8) return 'kt-badge-success';
        if (score >= 6) return 'kt-badge-warning';
        return 'kt-badge-danger';
    }

    // Get AI score text
    function getAiScoreText(score) {
        if (!score) return 'Pending score';
        return `${score}/10`;
    }

    onMount(() => {
        // If we have initial data, use it; otherwise fetch
        if (!applications || applications.length === 0) {
            fetchApplications();
        }
    });

    // Flash message handling
    export let success;

    $: if (success) {
        KTToast.show({
            icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check"><polyline points="20,6 9,17 4,12"/></svg>`,
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
            <!-- Job Applications Header -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <h1 class="text-2xl font-bold text-mono">Job Applications</h1>
                    <p class="text-sm text-secondary-foreground">
                        Applications for: {jobPosting?.name}
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{route('admin.job-postings.index')}" class="kt-btn kt-btn-outline">
                        <i class="ki-filled ki-arrow-left text-base"></i>
                        Back to Jobs
                    </a>

                    <a href="{exportUrl}" class="kt-btn kt-btn-success">
                        <i class="ki-filled ki-download text-base"></i>
                        Export CSV
                    </a>
                </div>
            </div>

            <!-- Job Applications Table -->
            <div class="kt-card">
                <div class="kt-card-header">
                    <div class="kt-card-toolbar">
                        <div class="flex items-center gap-4">
                            <div class="kt-input max-w-64 w-64">
                                <i class="ki-filled ki-magnifier"></i>
                                <input 
                                    type="text" 
                                    class="kt-input" 
                                    placeholder="Search applications..." 
                                    bind:value={search}
                                    on:input={handleSearchInput}
                                />
                            </div>
                            <!-- AI Score Filter -->
                            <select 
                                class="kt-select w-auto" 
                                bind:value={aiScoreFilter}
                                on:change={handleAiScoreFilter}
                            >
                                <option value="">All Scores</option>
                                <option value="8">8+ Score</option>
                                <option value="7">7+ Score</option>
                                <option value="6">6+ Score</option>
                                <option value="5">5+ Score</option>
                            </select>
                            
                            <!-- Filter Toggle Button -->
                            <button 
                                class="kt-btn kt-btn-outline"
                                on:click={toggleFilters}
                            >
                                <i class="ki-filled ki-filter text-sm"></i>
                                {showFilters ? 'Hide Filters' : 'Show Filters'}
                            </button>
                            
                            <!-- Clear Filters Button -->
                            {#if educationInstitution || educationDegree || educationField || educationStartYear || educationEndYear || workCompany || workTitle || workStartYear || workEndYear || languageName || languageProficiency || nationality || skills}
                                <button 
                                    class="kt-btn kt-btn-ghost kt-btn-sm"
                                    on:click={clearAllFilters}
                                >
                                    <i class="ki-filled ki-cross text-sm"></i>
                                    Clear All
                                </button>
                            {/if}
                        </div>
                    </div>
                </div>
                
                <!-- Advanced Filters Section -->
                {#if showFilters}
                    <div class="kt-card-body border-t border-gray-200 p-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                            <!-- Education Filters -->
                            <div class="space-y-2">
                                <h4 class="text-sm font-medium text-gray-700">Education</h4>
                                
                                <!-- Education Institution -->
                                <div class="w-full">
                                    <Select2
                                        id="education-institution"
                                        placeholder="Institution..."
                                        bind:value={educationInstitution}
                                        on:select={(e) => { educationInstitution = e.detail.value; handleFilterChange(); }}
                                        on:clear={() => { educationInstitution = ''; handleFilterChange(); }}
                                        data={[]}
                                        ajax={{
                                            url: route('admin.job-applications.filters.education-institutions', { jobPosting: jobPosting.id }),
                                            dataType: 'json',
                                            delay: 300,
                                            data: function(params) {
                                                return {
                                                    search: params.term
                                                };
                                            },
                                            processResults: function(data) {
                                                return {
                                                    results: data.results || []
                                                };
                                            },
                                            cache: true
                                        }}
                                    />
                                </div>
                                
                                <!-- Education Degree -->
                                <div class="w-full">
                                    <Select2
                                        id="education-degree"
                                        placeholder="Degree..."
                                        bind:value={educationDegree}
                                        on:select={(e) => { educationDegree = e.detail.value; handleFilterChange(); }}
                                        on:clear={() => { educationDegree = ''; handleFilterChange(); }}
                                        data={[]}
                                        ajax={{
                                            url: route('admin.job-applications.filters.education-degrees', { jobPosting: jobPosting.id }),
                                            dataType: 'json',
                                            delay: 300,
                                            data: function(params) {
                                                return {
                                                    search: params.term
                                                };
                                            },
                                            processResults: function(data) {
                                                return {
                                                    results: data.results || []
                                                };
                                            },
                                            cache: true
                                        }}
                                    />
                                </div>
                                
                                <!-- Education Field -->
                                <div class="w-full">
                                    <Select2
                                        id="education-field"
                                        placeholder="Field of Study..."
                                        bind:value={educationField}
                                        on:select={(e) => { educationField = e.detail.value; handleFilterChange(); }}
                                        on:clear={() => { educationField = ''; handleFilterChange(); }}
                                        data={[]}
                                        ajax={{
                                            url: route('admin.job-applications.filters.education-fields', { jobPosting: jobPosting.id }),
                                            dataType: 'json',
                                            delay: 300,
                                            data: function(params) {
                                                return {
                                                    search: params.term
                                                };
                                            },
                                            processResults: function(data) {
                                                return {
                                                    results: data.results || []
                                                };
                                            },
                                            cache: true
                                        }}
                                    />
                                </div>
                                
                                <!-- Education Years -->
                                <div class="flex gap-2">
                                    <input 
                                        type="number" 
                                        class="kt-input flex-1" 
                                        placeholder="Start Year" 
                                        bind:value={educationStartYear}
                                        on:input={handleFilterChange}
                                        min="1970"
                                        max="2030"
                                    />
                                    <input 
                                        type="number" 
                                        class="kt-input flex-1" 
                                        placeholder="End Year" 
                                        bind:value={educationEndYear}
                                        on:input={handleFilterChange}
                                        min="1970"
                                        max="2030"
                                    />
                                </div>
                            </div>
                            
                            <!-- Work Experience Filters -->
                            <div class="space-y-2">
                                <h4 class="text-sm font-medium text-gray-700">Work Experience</h4>
                                
                                <!-- Work Company -->
                                <div class="w-full">
                                    <Select2
                                        id="work-company"
                                        placeholder="Company..."
                                        bind:value={workCompany}
                                        on:select={(e) => { workCompany = e.detail.value; handleFilterChange(); }}
                                        on:clear={() => { workCompany = ''; handleFilterChange(); }}
                                        data={[]}
                                        ajax={{
                                            url: route('admin.job-applications.filters.work-companies', { jobPosting: jobPosting.id }),
                                            dataType: 'json',
                                            delay: 300,
                                            data: function(params) {
                                                return {
                                                    search: params.term
                                                };
                                            },
                                            processResults: function(data) {
                                                return {
                                                    results: data.results || []
                                                };
                                            },
                                            cache: true
                                        }}
                                    />
                                </div>
                                
                                <!-- Work Title -->
                                <div class="w-full">
                                    <Select2
                                        id="work-title"
                                        placeholder="Job Title..."
                                        bind:value={workTitle}
                                        on:select={(e) => { workTitle = e.detail.value; handleFilterChange(); }}
                                        on:clear={() => { workTitle = ''; handleFilterChange(); }}
                                        data={[]}
                                        ajax={{
                                            url: route('admin.job-applications.filters.work-titles', { jobPosting: jobPosting.id }),
                                            dataType: 'json',
                                            delay: 300,
                                            data: function(params) {
                                                return {
                                                    search: params.term
                                                };
                                            },
                                            processResults: function(data) {
                                                return {
                                                    results: data.results || []
                                                };
                                            },
                                            cache: true
                                        }}
                                    />
                                </div>
                                
                                <!-- Work Years -->
                                <div class="flex gap-2">
                                    <input 
                                        type="number" 
                                        class="kt-input flex-1" 
                                        placeholder="Start Year" 
                                        bind:value={workStartYear}
                                        on:input={handleFilterChange}
                                        min="1970"
                                        max="2030"
                                    />
                                    <input 
                                        type="number" 
                                        class="kt-input flex-1" 
                                        placeholder="End Year" 
                                        bind:value={workEndYear}
                                        on:input={handleFilterChange}
                                        min="1970"
                                        max="2030"
                                    />
                                </div>
                            </div>
                            
                            <!-- Language Filters -->
                            <div class="space-y-2">
                                <h4 class="text-sm font-medium text-gray-700">Languages</h4>
                                
                                <!-- Language Name -->
                                <div class="w-full">
                                    <Select2
                                        id="language-name"
                                        placeholder="Language..."
                                        bind:value={languageName}
                                        on:select={(e) => { languageName = e.detail.value; handleFilterChange(); }}
                                        on:clear={() => { languageName = ''; handleFilterChange(); }}
                                        data={[]}
                                        ajax={{
                                            url: route('admin.job-applications.filters.languages', { jobPosting: jobPosting.id }),
                                            dataType: 'json',
                                            delay: 300,
                                            data: function(params) {
                                                return {
                                                    search: params.term
                                                };
                                            },
                                            processResults: function(data) {
                                                return {
                                                    results: data.results || []
                                                };
                                            },
                                            cache: true
                                        }}
                                    />
                                </div>
                                
                                <!-- Language Proficiency -->
                                <select 
                                    class="kt-select w-full" 
                                    bind:value={languageProficiency}
                                    on:change={handleFilterChange}
                                >
                                    <option value="">All Proficiency</option>
                                    <option value="basic">Basic</option>
                                    <option value="intermediate">Intermediate</option>
                                    <option value="advanced">Advanced</option>
                                    <option value="native">Native</option>
                                </select>
                            </div>
                            
                            <!-- Personal Filters -->
                            <div class="space-y-2">
                                <h4 class="text-sm font-medium text-gray-700">Personal</h4>
                                
                                <!-- Nationality -->
                                <div class="w-full">
                                    <Select2
                                        id="nationality"
                                        placeholder="Nationality..."
                                        bind:value={nationality}
                                        on:select={(e) => { nationality = e.detail.value; handleFilterChange(); }}
                                        on:clear={() => { nationality = ''; handleFilterChange(); }}
                                        data={[]}
                                        ajax={{
                                            url: route('admin.job-applications.filters.nationalities', { jobPosting: jobPosting.id }),
                                            dataType: 'json',
                                            delay: 300,
                                            data: function(params) {
                                                return {
                                                    search: params.term
                                                };
                                            },
                                            processResults: function(data) {
                                                return {
                                                    results: data.results || []
                                                };
                                            },
                                            cache: true
                                        }}
                                    />
                                </div>
                                
                                <!-- Skills -->
                                <div class="w-full">
                                    <Select2
                                        id="skills"
                                        placeholder="Skills..."
                                        bind:value={skills}
                                        on:select={(e) => { skills = e.detail.value; handleFilterChange(); }}
                                        on:clear={() => { skills = ''; handleFilterChange(); }}
                                        data={[]}
                                        ajax={{
                                            url: route('admin.job-applications.filters.skills', { jobPosting: jobPosting.id }),
                                            dataType: 'json',
                                            delay: 300,
                                            data: function(params) {
                                                return {
                                                    search: params.term
                                                };
                                            },
                                            processResults: function(data) {
                                                return {
                                                    results: data.results || []
                                                };
                                            },
                                            cache: true
                                        }}
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                {/if}
                
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
                                            <span class="kt-table-col-label">#</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[200px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Applicant</span>
                                        </span>
                                    </th>
                                    <th class="w-[200px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Contact</span>
                                        </span>
                                    </th>
                                    <th class="w-[120px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">AI Score</span>
                                        </span>
                                    </th>
                                    <th class="w-[150px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Applied</span>
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
                                                <div class="flex items-center gap-3">
                                                    <div class="flex flex-col gap-1">
                                                        <div class="kt-skeleton w-24 h-4 rounded"></div>
                                                        <div class="kt-skeleton w-16 h-3 rounded"></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="p-4">
                                                <div class="flex flex-col gap-1">
                                                    <div class="kt-skeleton w-20 h-3 rounded"></div>
                                                    <div class="kt-skeleton w-16 h-3 rounded"></div>
                                                </div>
                                            </td>
                                            <td class="p-4">
                                                <div class="kt-skeleton w-12 h-6 rounded"></div>
                                            </td>
                                            <td class="p-4">
                                                <div class="kt-skeleton w-16 h-3 rounded"></div>
                                            </td>
                                            <td class="p-4">
                                                <div class="kt-skeleton w-8 h-8 rounded"></div>
                                            </td>
                                        </tr>
                                    {/each}
                                {:else if !applicationsList || applicationsList.length === 0}
                                    <!-- Empty state -->
                                    <tr>
                                        <td colspan="7" class="p-10">
                                            <div class="flex flex-col items-center justify-center text-center">
                                                <div class="mb-4">
                                                    <i class="ki-filled ki-user text-4xl text-muted-foreground"></i>
                                                </div>
                                                <h3 class="text-lg font-semibold text-mono mb-2">No applications found</h3>
                                                <p class="text-sm text-secondary-foreground mb-4">
                                                    {search || aiScoreFilter ? 'No applications match your search criteria.' : 'No applications have been submitted for this job posting yet.'}
                                                </p>
                                            </div>
                                        </td>
                                    </tr>
                                {:else}
                                    <!-- Actual data rows -->
                                    {#each applicationsList as application}
                                        <tr class="hover:bg-muted/50">
                                            <td>
                                                <input class="kt-checkbox kt-checkbox-sm" type="checkbox" value={application.id}/>
                                            </td>
                                            <td>
                                                <a href={route('admin.job-applications.show', { jobApplication: application.id })} class="text-sm font-medium text-primary hover:text-primary-dark">
                                                    #{application.id}
                                                </a>
                                            </td>
                                            <td>
                                                <div class="flex flex-col gap-1">
                                                    <span class="text-sm font-medium text-mono">
                                                        {application.first_name} {application.last_name}
                                                    </span>
                                                    <span class="text-xs text-secondary-foreground">
                                                        {application.nationality}
                                                    </span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="flex flex-col gap-1">
                                                    <span class="text-sm text-primary">
                                                        {application.email}
                                                    </span>
                                                    <span class="text-xs text-secondary-foreground">
                                                        {application.phone}
                                                    </span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="kt-badge {getAiScoreBadgeClass(application.ai_score)}">
                                                    {getAiScoreText(application.ai_score)}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="text-sm text-secondary-foreground">
                                                    {application.created_at ? new Date(application.created_at).toLocaleDateString('en-US', {
                                                        year: 'numeric',
                                                        month: 'long',
                                                        day: 'numeric',
                                                        hour: '2-digit',
                                                        minute: '2-digit'
                                                    }) : 'N/A'}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <div class="kt-menu flex-inline" data-kt-menu="true">
                                                    <div class="kt-menu-item" data-kt-menu-item-offset="0, 10px" data-kt-menu-item-placement="bottom-end" data-kt-menu-item-placement-rtl="bottom-start" data-kt-menu-item-toggle="dropdown" data-kt-menu-item-trigger="click">
                                                        <button class="kt-menu-toggle kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost">
                                                            <i class="ki-filled ki-dots-vertical text-lg"></i>
                                                        </button>
                                                        <div class="kt-menu-dropdown kt-menu-default w-full max-w-[175px]" data-kt-menu-dismiss="true">
                                                            {#if hasPermission('admin.job-applications.show')}
                                                            <div class="kt-menu-item">
                                                                <a class="kt-menu-link" href={route('admin.job-applications.show', { jobApplication: application.id })}>
                                                                    <span class="kt-menu-icon">
                                                                        <i class="ki-filled ki-search-list"></i>
                                                                    </span>
                                                                    <span class="kt-menu-title">View</span>
                                                                </a>
                                                            </div>
                                                            {/if}
                                                            {#if application.cv}
                                                            <div class="kt-menu-item">
                                                                <a class="kt-menu-link" href={application.cv?.url} target="_blank">
                                                                    <span class="kt-menu-icon">
                                                                        <i class="ki-filled ki-file-down"></i>
                                                                    </span>
                                                                    <span class="kt-menu-title">Download CV</span>
                                                                </a>
                                                            </div>
                                                            {/if}
                                                            {#if hasPermission('admin.job-applications.destroy')}
                                                                <div class="kt-menu-separator"></div>
                                                                <div class="kt-menu-item">
                                                                    <button class="kt-menu-link text-danger" on:click={() => deleteApplication(application.id)}>
                                                                        <span class="kt-menu-icon">
                                                                            <i class="ki-filled ki-trash"></i>
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

                    <!-- Pagination -->
                    {#if paginationData && paginationData.total > 0}
                        <Pagination 
                            pagination={paginationData} 
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