<script>
    import AdminLayout from '../Layouts/AdminLayout.svelte';
    import { onMount, tick } from 'svelte';
    import { router } from '@inertiajs/svelte';
    import Select2 from '../Components/Forms/Select2.svelte';

    // Props from the server
    export let grades;

    // Define breadcrumbs for this page
    const breadcrumbs = [
        {
            title: 'Grades',
            url: route('admin.grades.index'),
            active: false
        },
        {
            title: 'Order',
            url: route('admin.grades.order-page'),
            active: true
        }
    ];
    
    const pageTitle = 'Order Grades';

    // Loading state
    let loading = false;
    let sortableList;
    let sortableInstance = null;

    // Program filter state
    let selectedProgram = null;
    let filteredGrades = grades;
    let programSelectComponent;

    // Initialize sortable functionality
    function initializeSortable() {
        if (!sortableList) return;
        
        // Destroy existing sortable instance if it exists
        if (sortableInstance) {
            sortableInstance.destroy();
            sortableInstance = null;
        }
        
        // Wait a bit to ensure DOM is ready
        setTimeout(() => {
            // Check if Sortable is available
            if (typeof window.Sortable !== 'undefined') {
                sortableInstance = new window.Sortable(sortableList, {
                    animation: 150,
                    ghostClass: 'sortable-ghost',
                    chosenClass: 'sortable-chosen',
                    dragClass: 'sortable-drag',
                    onEnd: function(evt) {
                        updateOrder();
                    }
                });
            } else if (typeof Sortable !== 'undefined') {
                sortableInstance = new Sortable(sortableList, {
                    animation: 150,
                    ghostClass: 'sortable-ghost',
                    chosenClass: 'sortable-chosen',
                    dragClass: 'sortable-drag',
                    onEnd: function(evt) {
                        updateOrder();
                    }
                });
            } else {
                console.error('SortableJS not loaded. Please ensure SortableJS is included in your app.blade.php');
                // Show error message to user
                KTToast.show({
                    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-alert-circle"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`,
                    message: 'SortableJS not loaded. Please refresh the page.',
                    variant: "error",
                    position: "bottom-right",
                });
            }
        }, 100);
    }

    // Handle program selection
    function handleProgramSelect(event) {
        selectedProgram = event.detail.data;
        filterGrades();
        // Initialize sortable after filtering
        setTimeout(() => {
            initializeSortable();
        }, 200);
    }

    // Handle program clear
    function handleProgramClear() {
        selectedProgram = null;
        filteredGrades = grades;
        // Destroy sortable instance when clearing
        if (sortableInstance) {
            sortableInstance.destroy();
            sortableInstance = null;
        }
    }

    // Filter grades by program
    function filterGrades() {
        if (selectedProgram) {
            filteredGrades = grades.filter(grade => grade.program_id == selectedProgram.id);
        } else {
            filteredGrades = grades;
        }
    }

    // Handle order update
    async function updateOrder() {
        loading = true;
        
        const items = [];
        const children = Array.from(sortableList.children);
        
        children.forEach((el, index) => {
            if (el.dataset.id) {
                items.push({
                    id: el.dataset.id,
                    order: index + 1
                });
            }
        });
        
        try {
            const response = await fetch(route('admin.grades.order'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ order: items })
            });
            
            const data = await response.json();
            
            if (data.status === 'success') {
                // Show success toast
                KTToast.show({
                    icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check"><polyline points="20,6 9,17 4,12"/></svg>`,
                    message: 'Grade order updated successfully!',
                    variant: "success",
                    position: "bottom-right",
                });
            } else {
                throw new Error(data.message || 'Failed to update order');
            }
        } catch (error) {
            console.error('Error updating order:', error);
            // Show error toast
            KTToast.show({
                icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-alert-circle"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`,
                message: error.message || 'Failed to update grade order',
                variant: "error",
                position: "bottom-right",
            });
        } finally {
            loading = false;
        }
    }

    // Initialize sortable after mount
    onMount(async () => {
        await tick();
        // Sortable will be initialized when a program is selected
    });
</script>

<svelte:head>
    <title>Saud international schools - {pageTitle}</title>
</svelte:head>

<AdminLayout {breadcrumbs} {pageTitle}>
    <!-- Container -->
    <div class="kt-container-fixed">
        <div class="grid gap-5 lg:gap-7.5">
            <!-- Grade Header -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <h1 class="text-2xl font-bold text-mono">Order Grades</h1>
                    <p class="text-sm text-secondary-foreground">
                        Drag and drop grades to reorder them
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{route('admin.grades.index')}" class="kt-btn kt-btn-outline">
                        <i class="ki-filled ki-arrow-left text-base"></i>
                        Back to Grades
                    </a>
                </div>
            </div>

            <!-- Program Filter Card -->
            <div class="kt-card w-full">
                <div class="kt-card-header">
                    <h4 class="kt-card-title">Filter by Program</h4>
                    <p class="text-sm text-secondary-foreground">
                        Select a program to filter grades for ordering
                    </p>
                </div>
                <div class="kt-card-content">
                    <div class="grid gap-4">
                        {#if selectedProgram}
                            <!-- Program Badge -->
                            <div class="flex items-center gap-2">
                                <span class="kt-badge kt-badge-outline kt-badge-primary">
                                    <i class="ki-filled ki-abstract-26 text-sm me-1"></i>
                                    Program: {selectedProgram.text}
                                </span>
                                <button 
                                    type="button"
                                    class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost"
                                    on:click={handleProgramClear}
                                    title="Clear program selection"
                                >
                                    <i class="ki-filled ki-cross text-sm"></i>
                                </button>
                            </div>
                            <p class="text-sm text-secondary-foreground">
                                Showing {filteredGrades.length} grade(s) for this program
                            </p>
                        {:else}
                            <!-- Program Filter -->
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-medium text-mono" for="program-select">
                                    Select Program
                                </label>
                                <Select2
                                    bind:this={programSelectComponent}
                                    id="program-select"
                                    placeholder="Select program to filter grades..."
                                    on:select={handleProgramSelect}
                                    on:clear={handleProgramClear}
                                    ajax={{
                                        url: route('admin.programs.index'),
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
                                                results: data.programs.map(program => ({
                                                    id: program.id,
                                                    text: program.name
                                                }))
                                            };
                                        },
                                        cache: true
                                    }}
                                />
                            </div>
                        {/if}
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="kt-card w-full">
                <div class="kt-card-content">
                    <!-- Sortable List -->
                    <div class="w-full">
                        {#if selectedProgram}
                            {#if filteredGrades && filteredGrades.length > 0}
                                <ul bind:this={sortableList} class="sortable-list">
                                    {#each filteredGrades as grade}
                                        <li class="list-group-item" data-id={grade.id}>
                                            <div class="flex items-center">
                                                <i class="ki-filled ki-arrows-move handle"></i>
                                                <div class="flex-1">
                                                    <span class="font-medium text-mono">{grade.name}</span>
                                                    <small class="text-secondary-foreground ml-2">(ID: {grade.id})</small>
                                                </div>
                                                {#if loading}
                                                    <div class="ml-3">
                                                        <i class="ki-outline ki-loading text-base animate-spin text-primary"></i>
                                                    </div>
                                                {/if}
                                            </div>
                                        </li>
                                    {/each}
                                </ul>
                            {:else}
                                <div class="text-center py-8">
                                    <i class="ki-filled ki-files text-4xl text-secondary-foreground mb-4"></i>
                                    <p class="text-secondary-foreground">
                                        No grades found for program "{selectedProgram.text}".
                                    </p>
                                    <button 
                                        type="button"
                                        class="kt-btn kt-btn-outline mt-4"
                                        on:click={handleProgramClear}
                                    >
                                        <i class="ki-filled ki-cross text-base"></i>
                                        Clear Filter
                                    </button>
                                </div>
                            {/if}
                        {:else}
                            <div class="text-center py-8">
                                <i class="ki-filled ki-abstract-26 text-4xl text-secondary-foreground mb-4"></i>
                                <p class="text-secondary-foreground mb-2">
                                    Please select a program to order its grades.
                                </p>
                                <p class="text-sm text-muted-foreground">
                                    Choose a program from the filter above to start ordering grades.
                                </p>
                            </div>
                        {/if}
                    </div>
                </div>
            </div>
        </div>
    </div>
</AdminLayout> 