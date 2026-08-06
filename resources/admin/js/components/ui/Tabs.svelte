<script>
    /**
     * Tabs — controlled tab bar. Pass `tabs=[{id,label,icon?}]` and bind `active`;
     * render the active panel yourself, or pass a `panel` snippet that receives
     * the active id.
     *
     *   <Tabs {tabs} bind:active />
     *   {#if active === 'library'} … {/if}
     */
    let { tabs = [], active = $bindable(tabs[0]?.id), panel } = $props();
</script>

<div class="kt-tabs kt-tabs-line" role="tablist">
    {#each tabs as tab (tab.id)}
        <!-- `data-kt-tab-toggle` is required for Metronic's active styling
             (`&[data-kt-tab-toggle].active`); the class alone isn't enough. KTUI's
             JS only binds containers with `data-kt-tabs`, so this stays CSS-only. -->
        <button
            type="button"
            role="tab"
            data-kt-tab-toggle
            class="kt-tab-toggle {active === tab.id ? 'active' : ''}"
            aria-selected={active === tab.id}
            onclick={() => (active = tab.id)}
        >
            {#if tab.icon}<i class="{tab.icon} me-1.5"></i>{/if}
            {tab.label}
        </button>
    {/each}
</div>

{#if panel}
    <div class="pt-4">
        {@render panel(active)}
    </div>
{/if}
