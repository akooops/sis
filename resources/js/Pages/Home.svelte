<script>
    /**
     * Home — authenticated landing on the new admin shell. A placeholder until
     * the domain dashboards land; proves AdminLayout + Sidebar + Topbar end-to-end.
     */
    import AdminLayout from '@/layouts/AdminLayout.svelte';
    import PageHeader from '@/components/layout/PageHeader.svelte';
    import Card from '@/components/ui/Card.svelte';
    import { authUser } from '@/lib/permissions';

    const user = authUser();
</script>

<svelte:head>
    <title>Novonordisk Supply Chain Management System</title>
</svelte:head>

<AdminLayout breadcrumbs={[{ label: 'Dashboard' }]}>
    <PageHeader
        title={`Welcome${user ? `, ${user.firstname ?? user.username}` : ''}`}
        subtitle="The admin modules are being rebuilt on the new component system."
    />

    <Card>
        <p class="text-sm text-secondary-foreground">
            Use the sidebar to navigate. Modules light up as they are migrated.
        </p>
    </Card>
</AdminLayout>
