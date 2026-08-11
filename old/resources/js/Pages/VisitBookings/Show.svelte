<script>
    import AdminLayout from '../Layouts/AdminLayout.svelte';
    import { onMount } from 'svelte';

    // Props
    export let visitBooking;

    // Define breadcrumbs for this menu item
    const breadcrumbs = [
        {
            title: 'Visit Services Management',
            url: route('admin.visit-services.index'),
            active: false
        },
        {
            title: visitBooking?.visit_service?.name || 'Visit Service',
            url: route('admin.visit-bookings.index', { visitService: visitBooking?.visit_service.id }),
            active: false
        },
        {
            title: visitBooking?.guardian_name || 'Visit Booking Details',
            url: route('admin.visit-bookings.show', { visitBooking: visitBooking?.id }),
            active: true
        }
    ];
    
    const pageTitle = 'Visit Booking Details';

    // Format students array for display
    function formatStudents(students) {
        if (!students || !Array.isArray(students)) return [];
        return students;
    }

    // Format date time
    function formatDateTime(dateString) {
        if (!dateString) return 'N/A';
        return new Date(dateString).toLocaleString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    // Get translation for a field and language
    function getTranslation(field, languageCode) {
        if (translations && translations[field] && translations[field][languageCode]) {
            return translations[field][languageCode];
        }
        return `${field}.${languageCode}`;
    }
</script>

<svelte:head>
    <title>Saud international schools - {pageTitle}</title>
</svelte:head>

<AdminLayout {breadcrumbs} {pageTitle}>
    <!-- Container -->
    <div class="kt-container-fluid">
        <div class="grid gap-5 lg:gap-7.5 w-full">
            <!-- Visit Booking Header -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <h1 class="text-2xl font-bold text-mono">Visit Booking Information</h1>
                    <p class="text-sm text-secondary-foreground">
                        View visit booking details and information
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{route('admin.visit-bookings.index', { visitService: visitBooking?.visit_service.id })}" class="kt-btn kt-btn-outline">
                        <i class="ki-filled ki-arrow-left text-base"></i>
                        Back to Visit Bookings
                    </a>
                    {#if hasPermission('admin.visit-bookings.update')}
                        <a href={route('admin.visit-bookings.edit', { visitBooking: visitBooking?.id })} class="kt-btn kt-btn-primary">
                            <i class="ki-filled ki-pencil text-base"></i>
                            Edit Visit Booking
                        </a>
                    {/if}
                </div>
            </div>

            <!-- Guardian Information Card -->
            <div class="kt-card">
                <div class="kt-card-header">
                    <h4 class="kt-card-title">
                        Guardian Information
                    </h4>
                </div>
                <div class="kt-card-content">
                    <div class="grid gap-4">
                        <div class="flex flex-col gap-1">
                            <span class="text-xs text-secondary-foreground">Name</span>
                            <p class="text-sm text-mono">{visitBooking?.guardian_name}</p>
                        </div>
                        <div class="flex flex-col gap-1">
                            <span class="text-xs text-secondary-foreground">Email</span>
                            <p class="text-sm text-mono">{visitBooking?.email}</p>
                        </div>
                        <div class="flex flex-col gap-1">
                            <span class="text-xs text-secondary-foreground">Phone</span>
                            <p class="text-sm text-mono">{visitBooking?.phone}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Time Slot Information Card -->
            <div class="kt-card">
                <div class="kt-card-header">
                    <h4 class="kt-card-title">
                        Time Slot Information
                    </h4>
                </div>
                <div class="kt-card-content">
                    <div class="grid gap-4">
                        <div class="flex flex-col gap-1">
                            <span class="text-xs text-secondary-foreground">Start Time</span>
                            <p class="text-sm text-mono">{formatDateTime(visitBooking?.visit_time_slot?.starts_at)}</p>
                        </div>
                        <div class="flex flex-col gap-1">
                            <span class="text-xs text-secondary-foreground">End Time</span>
                            <p class="text-sm text-mono">{formatDateTime(visitBooking?.visit_time_slot?.ends_at)}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Students Information Card -->
            <div class="kt-card">
                <div class="kt-card-header">
                    <h4 class="kt-card-title">
                        Students Information
                    </h4>

                    <div class="kt-card-toolbar">
                        <span class="kt-badge kt-badge-outline kt-badge-primary">{visitBooking?.visitors_count} students</span>
                    </div>
                </div>
                <div class="kt-card-content">
                    <div class="grid gap-3">
                        {#each formatStudents(visitBooking?.students) as student, index}
                            <div class="border rounded-lg p-3 bg-muted/20">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="text-xs font-medium text-primary">Student {index + 1}</span>
                                </div>
                                <div class="grid gap-2">
                                    <div class="flex flex-col gap-1">
                                        <span class="text-xs text-secondary-foreground">Name</span>
                                        <p class="text-sm text-mono">{student.name}</p>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <span class="text-xs text-secondary-foreground">Grade</span>
                                        <p class="text-sm text-mono">{student.grade}</p>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <span class="text-xs text-secondary-foreground">School</span>
                                        <p class="text-sm text-mono">{student.school}</p>
                                    </div>
                                </div>
                            </div>
                        {/each}
                    </div>
                </div>
            </div>

            <!-- Timestamps Card -->
            <div class="kt-card">
                <div class="kt-card-header">
                    <h4 class="kt-card-title">
                        Timestamps
                    </h4>
                </div>
                <div class="kt-card-content">
                    <div class="grid gap-4">
                        <div class="flex flex-col gap-1">
                            <span class="text-xs text-secondary-foreground">Created At</span>
                            <p class="text-sm text-mono">
                                {visitBooking?.created_at ? new Date(visitBooking.created_at).toLocaleDateString('en-US', {
                                    year: 'numeric',
                                    month: 'long',
                                    day: 'numeric',
                                    hour: '2-digit',
                                    minute: '2-digit'
                                }) : 'N/A'}
                            </p>
                        </div>
                        <div class="flex flex-col gap-1">
                            <span class="text-xs text-secondary-foreground">Updated At</span>
                            <p class="text-sm text-mono">
                                {visitBooking?.updated_at ? new Date(visitBooking.updated_at).toLocaleDateString('en-US', {
                                    year: 'numeric',
                                    month: 'long',
                                    day: 'numeric',
                                    hour: '2-digit',
                                    minute: '2-digit'
                                }) : 'N/A'}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End of Container -->
</AdminLayout> 