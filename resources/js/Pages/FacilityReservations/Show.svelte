<script>
    import AdminLayout from '../Layouts/AdminLayout.svelte';

    // Props
    export let facilityReservation;

    // Define breadcrumbs for this reservation
    const breadcrumbs = [
        {
            title: 'Facilities Management',
            url: route('admin.facilities.index'),
            active: false
        },
        {
            title: facilityReservation?.facility?.name || 'Facility',
            url: route('admin.facility-reservations.index', { facility: facilityReservation?.facility?.id }),
            active: false
        },
        {
            title: facilityReservation?.name || 'Reservation Details',
            url: route('admin.facility-reservations.show', { facilityReservation: facilityReservation?.id }),
            active: true
        }
    ];

    const pageTitle = 'Reservation Details';

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

    // Contact quick-action links
    $: whatsappLink = facilityReservation?.phone
        ? `https://wa.me/${facilityReservation.phone.replace(/[^0-9]/g, '')}`
        : null;

    $: mailtoLink = facilityReservation?.email
        ? `mailto:${facilityReservation.email}?subject=${encodeURIComponent('Your reservation request - ' + (facilityReservation?.facility?.name || ''))}`
        : null;
</script>

<svelte:head>
    <title>Saud international schools - {pageTitle}</title>
</svelte:head>

<AdminLayout {breadcrumbs} {pageTitle}>
    <!-- Container -->
    <div class="kt-container-fluid">
        <div class="grid gap-5 lg:gap-7.5 w-full">
            <!-- Reservation Header -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex flex-col gap-1">
                    <h1 class="text-2xl font-bold text-mono">Reservation Information</h1>
                    <p class="text-sm text-secondary-foreground">
                        View reservation request details and contact the visitor
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{route('admin.facility-reservations.index', { facility: facilityReservation?.facility?.id })}" class="kt-btn kt-btn-outline">
                        <i class="ki-filled ki-arrow-left text-base"></i>
                        Back to Reservations
                    </a>
                    {#if mailtoLink}
                        <a href={mailtoLink} class="kt-btn kt-btn-outline">
                            <i class="ki-filled ki-sms text-base"></i>
                            Contact via Email
                        </a>
                    {/if}
                    {#if whatsappLink}
                        <a href={whatsappLink} target="_blank" class="kt-btn kt-btn-primary">
                            <i class="ki-filled ki-whatsapp text-base"></i>
                            Contact via WhatsApp
                        </a>
                    {/if}
                </div>
            </div>

            <!-- Visitor Information Card -->
            <div class="kt-card">
                <div class="kt-card-header">
                    <h4 class="kt-card-title">
                        Visitor Information
                    </h4>
                    <div class="kt-card-toolbar">
                        <span class="kt-badge kt-badge-outline kt-badge-primary">{facilityReservation?.guests_count} guest(s)</span>
                    </div>
                </div>
                <div class="kt-card-content">
                    <div class="grid gap-4">
                        <div class="flex flex-col gap-1">
                            <span class="text-xs text-secondary-foreground">Name</span>
                            <p class="text-sm text-mono">{facilityReservation?.name}</p>
                        </div>
                        <div class="flex flex-col gap-1">
                            <span class="text-xs text-secondary-foreground">Email</span>
                            <p class="text-sm text-mono">{facilityReservation?.email}</p>
                        </div>
                        <div class="flex flex-col gap-1">
                            <span class="text-xs text-secondary-foreground">Phone</span>
                            <p class="text-sm text-mono">{facilityReservation?.phone}</p>
                        </div>
                        {#if facilityReservation?.message}
                            <div class="flex flex-col gap-1">
                                <span class="text-xs text-secondary-foreground">Message</span>
                                <p class="text-sm text-mono whitespace-pre-wrap">{facilityReservation?.message}</p>
                            </div>
                        {/if}
                    </div>
                </div>
            </div>

            <!-- Facility & Time Slot Information Card -->
            <div class="kt-card">
                <div class="kt-card-header">
                    <h4 class="kt-card-title">
                        Facility & Time Slot
                    </h4>
                </div>
                <div class="kt-card-content">
                    <div class="grid gap-4">
                        <div class="flex flex-col gap-1">
                            <span class="text-xs text-secondary-foreground">Facility</span>
                            <p class="text-sm text-mono">{facilityReservation?.facility?.name}</p>
                        </div>
                        <div class="flex flex-col gap-1">
                            <span class="text-xs text-secondary-foreground">Start Time</span>
                            <p class="text-sm text-mono">{formatDateTime(facilityReservation?.facility_time_slot?.starts_at)}</p>
                        </div>
                        <div class="flex flex-col gap-1">
                            <span class="text-xs text-secondary-foreground">End Time</span>
                            <p class="text-sm text-mono">{formatDateTime(facilityReservation?.facility_time_slot?.ends_at)}</p>
                        </div>
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
                            <p class="text-sm text-mono">{formatDateTime(facilityReservation?.created_at)}</p>
                        </div>
                        <div class="flex flex-col gap-1">
                            <span class="text-xs text-secondary-foreground">Updated At</span>
                            <p class="text-sm text-mono">{formatDateTime(facilityReservation?.updated_at)}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End of Container -->
</AdminLayout>
