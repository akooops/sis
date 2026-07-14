<script>
    /**
     * IndexCard — the Metronic list-card shell shared by every module index:
     * `kt-card kt-card-grid` > inner card > `kt-card-header` toolbar, then a
     * fly-in swap between the table and the create/edit form (Metronic UX).
     *
     *   <IndexCard {showForm} {toolbar} {form} {table} />
     * The `toolbar` snippet receives `showForm` so it can swap Add ⇄ Cancel.
     */
    import { fly } from 'svelte/transition';

    let { showForm = false, toolbar, form, table } = $props();
</script>

<div class="grid gap-5 lg:gap-7.5">
    <div class="kt-card kt-card-grid min-w-full overflow-hidden">
        <div class="kt-card w-full border-0">
            <div class="kt-card-header">
                <div class="kt-card-toolbar flex items-center justify-between w-full">
                    {@render toolbar(showForm)}
                </div>
            </div>

            {#if showForm}
                <div class="kt-card-content p-5" in:fly={{ x: '100%', duration: 750 }}>
                    {@render form()}
                </div>
            {:else}
                <div class="kt-card-content p-0" in:fly={{ x: '-100%', duration: 750 }}>
                    {@render table()}
                </div>
            {/if}
        </div>
    </div>
</div>
