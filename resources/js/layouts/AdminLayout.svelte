<script>
    /**
     * AdminLayout — Metronic demo1 shell. The body carries `demo1 kt-sidebar-fixed
     * kt-header-fixed` (app.blade.php), so this only composes Sidebar + Topbar +
     * content + footer and mounts the toast/confirm hosts once.
     */
    import Sidebar from '@/components/layout/Sidebar.svelte';
    import Topbar from '@/components/layout/Topbar.svelte';
    import Toasts from '@/components/feedback/Toasts.svelte';
    import ConfirmDialog from '@/components/feedback/ConfirmDialog.svelte';
    import { initKt } from '@/lib/kt';

    let { breadcrumbs = [], children } = $props();

    // Initialise the Metronic chrome (sidebar accordion/collapse, topbar dropdowns,
    // sticky header, theme switch) once the shell is mounted.
    $effect(() => {
        initKt();
    });
</script>

<div class="flex grow">
    <Sidebar />

    <div class="kt-wrapper flex grow flex-col">
        <Topbar {breadcrumbs} />

        <main class="grow pt-5" id="content" role="content">
            <div class="kt-container-fixed">
                {@render children?.()}
            </div>
        </main>

        <footer class="kt-footer">
            <div class="kt-container-fixed">
                <div class="flex flex-col md:flex-row justify-center md:justify-between items-center gap-3 py-5">
                    <div class="flex order-2 md:order-1 gap-2 font-normal text-sm">
                        <span class="text-secondary-foreground">{new Date().getFullYear()}©</span>
                        <span class="text-secondary-foreground">Novonordisk</span>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</div>

<Toasts />
<ConfirmDialog />
