<script>
    import AdminLayout from '../Layouts/AdminLayout.svelte';
    import { page } from '@inertiajs/svelte';

    export let contactSubmission;

    const breadcrumbs = [
        {
            title: 'Contact Submissions',
            url: route('admin.contact-submissions.index'),
            active: false
        },
        {
            title: `Submission #${contactSubmission.id}`,
            url: route('admin.contact-submissions.show', { contactSubmission: contactSubmission.id }),
            active: true
        }
    ];
    
    const pageTitle = `Contact Submission #${contactSubmission.id}`;
</script>

<svelte:head>
    <title>Saud international schools - {pageTitle}</title>
</svelte:head>

<AdminLayout {breadcrumbs} {pageTitle}>
    <div class="kt-container-fixed">
        <div class="grid gap-5 lg:gap-7.5">
            <div class="kt-card">
                <div class="kt-card-header">
                    <h4 class="kt-card-title">Contact Submission Details</h4>
                </div>
                <div class="kt-card-content">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h5 class="font-semibold mb-2">Contact Information</h5>
                            <div><b>Name:</b> {contactSubmission.name}</div>
                            <div><b>Email:</b> {contactSubmission.email}</div>
                            <div><b>Phone:</b> {contactSubmission.phone}</div>
                        </div>
                        <div>
                            <h5 class="font-semibold mb-2">Message Details</h5>
                            <div><b>Subject:</b> {contactSubmission.subject}</div>
                            <div><b>Message:</b></div>
                            <div class="whitespace-pre-line mt-2 p-3 bg-gray-100 rounded">{contactSubmission.message}</div>
                        </div>
                        <div>
                            <h5 class="font-semibold mb-2">Meta</h5>
                            <div>
                                <b>Created At:</b> 
                                {contactSubmission.created_at ? new Date(contactSubmission.created_at).toLocaleDateString('en-US', {
                                        year: 'numeric',
                                        month: 'long',
                                        day: 'numeric',
                                        hour: '2-digit',
                                        minute: '2-digit'
                                    }) : 'N/A'}
                            </div>
                            <div>
                                <b>Updated At:</b> 
                                {contactSubmission.updated_at ? new Date(contactSubmission.updated_at).toLocaleDateString('en-US', {
                                        year: 'numeric',
                                        month: 'long',
                                        day: 'numeric',
                                        hour: '2-digit',
                                        minute: '2-digit'
                                    }) : 'N/A'}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="kt-card-footer flex justify-end">
                    <a href={route('admin.contact-submissions.index')} class="kt-btn kt-btn-outline">Back to Contact Submissions</a>
                </div>
            </div>
        </div>
    </div>
</AdminLayout> 