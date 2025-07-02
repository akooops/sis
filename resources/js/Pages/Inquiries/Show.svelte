<script>
    import AdminLayout from '../Layouts/AdminLayout.svelte';
    import { page } from '@inertiajs/svelte';

    export let inquiry;

    const breadcrumbs = [
        {
            title: 'Inquiries',
            url: route('admin.inquiries.index'),
            active: false
        },
        {
            title: `Inquiry #${inquiry.id}`,
            url: route('admin.inquiries.show', { inquiry: inquiry.id }),
            active: true
        }
    ];
    
    const pageTitle = `Inquiry #${inquiry.id}`;
</script>

<svelte:head>
    <title>Saud international schools - {pageTitle}</title>
</svelte:head>

<AdminLayout {breadcrumbs} {pageTitle}>
    <div class="kt-container-fixed">
        <div class="grid gap-5 lg:gap-7.5">
            <div class="kt-card">
                <div class="kt-card-header">
                    <h4 class="kt-card-title">Inquiry Details</h4>
                </div>
                <div class="kt-card-content">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h5 class="font-semibold mb-2">Guardian Information</h5>
                            <div><b>Name:</b> {inquiry.guardian_name}</div>
                            <div><b>Email:</b> {inquiry.email}</div>
                            <div><b>Phone:</b> {inquiry.phone}</div>
                        </div>
                        <div>
                            <h5 class="font-semibold mb-2">Student Information</h5>
                            <div><b>Name:</b> {inquiry.student_name}</div>
                            <div><b>Birthdate:</b> {inquiry.student_birthdate}</div>
                            <div><b>School:</b> {inquiry.student_school}</div>
                        </div>
                        <div>
                            <h5 class="font-semibold mb-2">Application</h5>
                            <div><b>Academic Year Applied:</b> {inquiry.academic_year_applied}</div>
                            <div><b>Grade Applied:</b> {inquiry.grade_applied}</div>
                        </div>
                        <div>
                            <h5 class="font-semibold mb-2">Questions</h5>
                            <div class="whitespace-pre-line">{inquiry.questions}</div>
                        </div>
                        <div>
                            <h5 class="font-semibold mb-2">Meta</h5>
                            <div>
                                <b>Created At:</b> 
                                {inquiry.created_at ? new Date(inquiry.created_at).toLocaleDateString('en-US', {
                                        year: 'numeric',
                                        month: 'long',
                                        day: 'numeric',
                                        hour: '2-digit',
                                        minute: '2-digit'
                                    }) : 'N/A'}
                            </div>
                            <div>
                                <b>Updated At:</b> 
                                {inquiry.updated_at ? new Date(inquiry.updated_at).toLocaleDateString('en-US', {
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
                    <a href={route('admin.inquiries.index')} class="kt-btn kt-btn-outline">Back to Inquiries</a>
                </div>
            </div>
        </div>
    </div>
</AdminLayout> 