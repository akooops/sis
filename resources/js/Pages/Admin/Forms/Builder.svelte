<script>
    /**
     * The form builder.
     *
     * NOT inside IndexCard: that pane mounts in a 750ms fly transform, and a
     * transformed ancestor skews every getBoundingClientRect a drag library
     * takes — the drop target would be measured in the wrong place.
     *
     * ONE explicit save, not autosave. Observers write an audit row per model
     * write, so autosave would log every fumbled drag; a cross-page move rewrites
     * several rows at once and must be atomic; and a live form must never be
     * half-built. The dirty flag plus the navigation guards are what make that
     * safe rather than annoying.
     *
     * NO preview here. The public page mounts FormRenderer for real — a second,
     * subtly different render inside the builder is a promise this canvas cannot
     * keep, so the builder edits and the site renders.
     */
    import AdminLayout from '@/layouts/AdminLayout.svelte';
    import Alert from '@/components/feedback/Alert.svelte';
    import Badge from '@/components/ui/Badge.svelte';
    import Button from '@/components/ui/Button.svelte';
    import Skeleton from '@/components/ui/Skeleton.svelte';
    import Drawer from '@/components/ui/Drawer.svelte';
    import ElementPalette from '@/components/forms-builder/ElementPalette.svelte';
    import PageSection from '@/components/forms-builder/PageSection.svelte';
    import ElementInspector from '@/components/forms-builder/ElementInspector.svelte';
    import { createBuilder } from '@/lib/forms/builder.svelte';
    import { toast } from '@/lib/toast';
    import { confirm } from '@/lib/confirm';
    import { router } from '@inertiajs/svelte';

    let { formId = null } = $props();

    const builder = createBuilder();

    let inspectorOpen = $state(false);
    let wide = $state(true);

    // Skeleton geometry — mirrors the real three-column canvas rather than a
    // generic block, so the page does not reflow into a different shape once the
    // tree lands. Counts are arbitrary; the LAYOUT is the point.
    const SKELETON_PALETTE_GROUPS = [4, 4, 2];
    const SKELETON_PAGE_FIELDS = [3, 2];
    const SKELETON_INSPECTOR_FIELDS = [0, 1, 2, 3, 4];

    // Plain, not $state: tracking it would re-run the effect that sets it.
    let lastLoaded = null;

    $effect(() => {
        if (!formId || formId === lastLoaded) return;
        lastLoaded = formId;

        builder.load(formId).catch((e) => toast.error(e?.message ?? 'Could not load this form.'));
    });

    // The inspector is a rail on a wide screen and a drawer on a narrow one —
    // but only ONE instance either way. Rendering both and hiding one with
    // `hidden` keeps both alive, with two sets of effects fighting each other.
    $effect(() => {
        const mq = window.matchMedia('(min-width: 1280px)');
        const sync = () => (wide = mq.matches);

        sync();
        mq.addEventListener('change', sync);

        return () => mq.removeEventListener('change', sync);
    });

    // Unsaved work must survive a stray click. Covers both exits: a full page
    // unload, and an Inertia visit that never touches the browser's unload.
    $effect(() => {
        const warn = (e) => {
            if (!builder.dirty) return;
            e.preventDefault();
            e.returnValue = '';
        };

        window.addEventListener('beforeunload', warn);
        const off = router.on('before', (event) => {
            if (!builder.dirty) return;
            if (!window.confirm('You have unsaved changes to this form. Leave anyway?')) event.preventDefault();
        });

        return () => {
            window.removeEventListener('beforeunload', warn);
            off();
        };
    });

    const selected = $derived(builder.selected);
    // A page and an element are never both selected — the panel shows one or the
    // other, and "nothing selected" when neither.
    const selectedPage = $derived(builder.selectedPage);
    const selectedPageIndex = $derived(selectedPage ? builder.pages.findIndex((p) => p.id === selectedPage.id) : -1);
    const spec = $derived(
        selected ? (builder.palette.find((p) => p.code === selected.field.type) ?? null) : null,
    );
    const language = $derived(builder.language);
    const formErrors = $derived(builder.errors.form ?? []);

    // Whatever the server said about the selected thing — keyed by its id either
    // way, so the same lookup serves a page and an element.
    const inspectorErrors = $derived(
        builder.errors[selectedPage?.id ?? selected?.field?.id] ?? null,
    );

    function add(type) {
        const pageId = selectedPage?.id ?? selected?.page?.id ?? builder.pages[0]?.id;
        builder.addField(type, pageId);
        if (!wide) inspectorOpen = true;
    }

    function select(id) {
        builder.select(id);
        if (!wide) inspectorOpen = true;
    }

    function selectPage(id) {
        builder.selectPage(id);
        if (!wide) inspectorOpen = true;
    }

    /** A new page has nothing to show on the canvas, so open its settings. */
    function addPage() {
        const page = builder.addPage();
        if (page) selectPage(page.id);
    }

    async function removeField(id) {
        if (!(await confirm({ body: 'Remove this element?', variant: 'destructive' }))) return;
        builder.removeField(id);
    }

    async function removePage(id) {
        if (!(await confirm({ body: 'Remove this page and everything on it?', variant: 'destructive' }))) return;
        builder.removePage(id);
    }

    async function save() {
        try {
            await builder.save();
            toast.success('Form saved.');
        } catch (e) {
            if (e?.status !== 422) {
                toast.error(e?.message ?? 'Something went wrong. Please try again.');

                return;
            }

            const message = Object.values(e.errors ?? {}).flat()[0] ?? 'Some elements need attention.';

            // A change that would destroy stored answers is refused outright —
            // there is no override to offer, so this is just the message.
            toast.error(message);

            // Jump to whatever failed, so the message is never behind a locale
            // or a page the admin cannot see. A page's own settings live in the
            // panel now, so a page-level message needs the page selected.
            const firstId = Object.keys(builder.errors).find((k) => k !== 'form');
            if (firstId) {
                if (builder.pages.some((p) => p.id === firstId)) selectPage(firstId);
                else select(firstId);
            }
        }
    }
</script>

<svelte:head><title>Saud International Schools — {builder.doc?.name ?? 'Form builder'}</title></svelte:head>

<AdminLayout
    title={builder.doc?.name ?? 'Form builder'}
    breadcrumbs={[{ label: 'Forms', href: route('web.admin.forms.index') }, { label: builder.doc?.name ?? 'Builder' }]}
>
    {#if builder.loading}
        {@render loadingCanvas()}
    {:else if !builder.doc}
        <Alert variant="destructive">This form could not be loaded. It may have been deleted.</Alert>
    {:else}
        <div class="flex flex-col gap-4">
            <!-- Toolbar -->
            <div class="flex flex-wrap items-center gap-3 rounded-xl border border-border p-3">
                <button class="kt-btn kt-btn-sm kt-btn-secondary" onclick={() => router.visit(route('web.admin.forms.index'))}>
                    <i class="ki-filled ki-black-left"></i>Forms
                </button>

                <div class="ms-auto flex items-center gap-2">
                    {#if builder.dirty}<Badge variant="warning" size="sm">Unsaved</Badge>{/if}

                    <Button variant="primary" size="sm" loading={builder.saving} disabled={builder.locked} onclick={() => save()}>
                        Save
                    </Button>
                </div>
            </div>

            {#if builder.locked}
                <Alert variant="warning">
                    This form ships with the app. Its pages and fields are locked — the server refuses to change
                    them. Its settings are still editable from the Forms page.
                </Alert>
            {/if}

            {#each formErrors as message}
                <Alert variant="destructive">{message}</Alert>
            {/each}

            <div class="grid gap-4 xl:grid-cols-[272px_minmax(0,1fr)_360px]">
                <aside class="flex flex-col gap-4">
                    <div class="kt-card">
                        <div class="kt-card-header">
                            <h3 class="kt-card-title">Elements</h3>
                            <span class="text-xs text-muted-foreground">Click to add</span>
                        </div>
                        <div class="kt-card-content p-5">
                            <ElementPalette palette={builder.palette} disabled={builder.locked} onadd={add} />
                        </div>
                    </div>
                </aside>

                <!-- ONE card: the language strip and the pages it retranslates are
                     the same thing being looked at, so they share a frame. -->
                <div class="kt-card self-start">
                    <div class="kt-card-header">
                        <h3 class="kt-card-title">Pages</h3>
                        {#if builder.locales.length > 1}
                            <span class="text-xs text-muted-foreground">The copy below is edited in the selected language</span>
                        {/if}
                    </div>
                    <div class="kt-card-content flex flex-col gap-4 p-5">
                        {#if builder.locales.length > 1}
                            <!-- One switcher for the whole canvas, above the pages it
                                 retranslates. Per-element language tabs would be 9
                                 locales x N elements of panels, and the real unit of
                                 work is "translate this form into Arabic". -->
                            <div class="kt-tabs kt-tabs-line overflow-x-auto" role="tablist" aria-label="Language">
                                {#each builder.locales as l (l.code)}
                                    <!-- The BARE data-kt-tab-toggle attribute is what Metronic
                                         styles the active tab off — the class alone is not enough. -->
                                    <button
                                        type="button"
                                        role="tab"
                                        data-kt-tab-toggle
                                        class="kt-tab-toggle {builder.locale === l.code ? 'active' : ''}"
                                        aria-selected={builder.locale === l.code}
                                        onclick={() => builder.setLocale(l.code)}
                                    >{l.name}</button>
                                {/each}
                            </div>
                        {/if}

                        {#each builder.pages as page, index (page.id)}
                            <PageSection
                                {page}
                                {index}
                                pages={builder.pages}
                                locale={builder.locale}
                                fallbackLocale={builder.defaultLocale}
                                selectedId={builder.selectedId}
                                selected={builder.selectedPageId === page.id}
                                errors={builder.errors}
                                locked={builder.locked}
                                canRemove={builder.pages.length > 1}
                                onfields={(id, fields) => builder.setFields(id, fields)}
                                onselect={select}
                                onselectpage={selectPage}
                                onstep={(id, delta) => builder.stepField(id, delta)}
                                onmove={(id, pageId) => pageId && builder.moveFieldToPage(id, pageId)}
                                onduplicate={(id) => builder.duplicateField(id)}
                                onremove={removeField}
                                onremovepage={removePage}
                            />
                        {/each}

                        {#if !builder.locked}
                            <button class="kt-btn kt-btn-secondary self-start" onclick={addPage}>
                                <i class="ki-filled ki-plus"></i>Add a page
                            </button>
                        {/if}
                    </div>
                </div>

                {#if wide}
                    <aside class="flex flex-col gap-4 rounded-xl border border-border p-4">
                        <h2 class="text-sm font-medium text-mono">Settings</h2>
                        {@render inspector()}
                    </aside>
                {/if}
            </div>
        </div>
    {/if}

    {#if !wide}
        <Drawer bind:open={inspectorOpen} title={selectedPage ? 'Page settings' : 'Element settings'} width="w-[400px]">
            {@render inspector()}
        </Drawer>
    {/if}
</AdminLayout>

{#snippet inspector()}
    <!-- ONE inspector, whichever is selected. It knows both shapes and draws its
         own empty state, so there is nothing to branch on here. -->
    <ElementInspector
        page={selectedPage}
        index={selectedPageIndex}
        field={selectedPage ? null : (selected?.field ?? null)}
        {spec}
        pages={builder.pages}
        locale={builder.locale}
        isRtl={!!language?.is_rtl}
        isDefaultLocale={builder.locale === builder.defaultLocale}
        locked={builder.locked}
        errors={inspectorErrors}
        onpatch={(id, patch) => (selectedPage ? builder.patchPage(id, patch) : builder.patchField(id, patch))}
        onaddoption={(id) => builder.addOption(id)}
        onremoveoption={(id, optionId) => builder.removeOption(id, optionId)}
        onpatchoption={(id, optionId, patch) => builder.patchOption(id, optionId, patch)}
    />
{/snippet}

{#snippet loadingCanvas()}
    <div class="flex flex-col gap-4" aria-busy="true" aria-label="Loading the form">
        <!-- Toolbar -->
        <div class="flex flex-wrap items-center gap-3 rounded-xl border border-border p-3">
            <Skeleton class="h-8 w-24 rounded-md" />
            <div class="ms-auto flex items-center gap-2">
                <Skeleton class="h-8 w-20 rounded-md" />
            </div>
        </div>

        <div class="grid gap-4 xl:grid-cols-[272px_minmax(0,1fr)_360px]">
            <!-- Palette -->
            <aside class="flex flex-col gap-4">
                <div class="kt-card">
                    <div class="kt-card-header">
                        <Skeleton class="h-4 w-20 rounded" />
                    </div>
                    <div class="kt-card-content flex flex-col gap-5 p-5">
                        {#each SKELETON_PALETTE_GROUPS as tiles, group (group)}
                            <div class="flex flex-col gap-2">
                                <Skeleton class="h-3 w-16 rounded" />
                                <div class="grid grid-cols-2 gap-2">
                                    {#each Array.from({ length: tiles }, (_, i) => i) as tile (tile)}
                                        <Skeleton class="h-9 rounded-lg" />
                                    {/each}
                                </div>
                            </div>
                        {/each}
                    </div>
                </div>
            </aside>

            <!-- The Pages card: language strip, then the pages, all in one frame -->
            <div class="kt-card self-start">
                <div class="kt-card-header">
                    <Skeleton class="h-4 w-24 rounded" />
                </div>
                <div class="kt-card-content flex flex-col gap-4 p-5">
                    <div class="flex items-center gap-6">
                        <Skeleton class="h-4 w-16 rounded" />
                        <Skeleton class="h-4 w-14 rounded" />
                        <Skeleton class="h-4 w-16 rounded" />
                    </div>

                    {#each SKELETON_PAGE_FIELDS as fields, page (page)}
                        <section class="flex flex-col gap-3 rounded-xl border border-border p-4">
                            <header class="flex items-center gap-2">
                                <Skeleton class="h-5 w-16 rounded-full" />
                                <Skeleton class="h-4 w-32 rounded" />
                            </header>
                            <div class="flex flex-col gap-2">
                                {#each Array.from({ length: fields }, (_, i) => i) as field (field)}
                                    <Skeleton class="h-14 rounded-lg" />
                                {/each}
                            </div>
                        </section>
                    {/each}
                </div>
            </div>

            <!-- Inspector rail -->
            <aside class="hidden flex-col gap-4 rounded-xl border border-border p-4 xl:flex">
                <Skeleton class="h-4 w-20 rounded" />
                {#each SKELETON_INSPECTOR_FIELDS as field (field)}
                    <div class="flex flex-col gap-1.5">
                        <Skeleton class="h-3 w-24 rounded" />
                        <Skeleton class="h-9 rounded-md" />
                    </div>
                {/each}
            </aside>
        </div>
    </div>
{/snippet}
