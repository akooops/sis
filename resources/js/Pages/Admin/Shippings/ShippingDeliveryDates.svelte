<script>
    import Flatpickr from '../../Shared/Utils/Forms/Flatpickr.svelte';

    export let modeOfTransportation = 'sea';
    export let disabled = false;
    export let errors = {};
    export let showHint = false;

    export let planned_delivery_at_customs = '';
    export let planned_delivery_at_site = '';
    export let actual_delivery_at_customs = '';
    export let actual_delivery_at_site = '';
</script>

<div class="space-y-5">
    {#if showHint}
        <p class="text-xs text-muted-foreground">Dates are suggested from the product supply lead time. You can adjust them before saving.</p>
    {/if}

    {#if modeOfTransportation === 'local'}
        <div class="flex flex-col gap-2">
            <label class="text-sm font-medium text-mono" for="planned-delivery-at-site">Planned Delivery at Site</label>
            <Flatpickr
                id="planned-delivery-at-site"
                placeholder="Select planned delivery at site..."
                bind:value={planned_delivery_at_site}
                {disabled}
            />
            {#if errors.planned_delivery_at_site}
                <p class="text-sm text-destructive">{errors.planned_delivery_at_site}</p>
            {/if}
        </div>
        <div class="flex flex-col gap-2">
            <label class="text-sm font-medium text-mono" for="actual-delivery-at-site">Actual Delivery at Site</label>
            <Flatpickr
                id="actual-delivery-at-site"
                placeholder="Select actual delivery at site..."
                bind:value={actual_delivery_at_site}
                {disabled}
            />
            {#if errors.actual_delivery_at_site}
                <p class="text-sm text-destructive">{errors.actual_delivery_at_site}</p>
            {/if}
        </div>
    {:else}
        <div class="flex flex-col gap-2">
            <label class="text-sm font-medium text-mono" for="planned-delivery-at-customs">Planned Delivery at Customs</label>
            <Flatpickr
                id="planned-delivery-at-customs"
                placeholder="Select planned delivery at customs..."
                bind:value={planned_delivery_at_customs}
                {disabled}
            />
            {#if errors.planned_delivery_at_customs}
                <p class="text-sm text-destructive">{errors.planned_delivery_at_customs}</p>
            {/if}
        </div>
        <div class="flex flex-col gap-2">
            <label class="text-sm font-medium text-mono" for="planned-delivery-at-site-import">Planned Delivery at Site</label>
            <Flatpickr
                id="planned-delivery-at-site-import"
                placeholder="Select planned delivery at site..."
                bind:value={planned_delivery_at_site}
                {disabled}
            />
            {#if errors.planned_delivery_at_site}
                <p class="text-sm text-destructive">{errors.planned_delivery_at_site}</p>
            {/if}
        </div>
        <div class="flex flex-col gap-2">
            <label class="text-sm font-medium text-mono" for="actual-delivery-at-customs">Actual Delivery at Customs</label>
            <Flatpickr
                id="actual-delivery-at-customs"
                placeholder="Select actual delivery at customs..."
                bind:value={actual_delivery_at_customs}
                {disabled}
            />
            {#if errors.actual_delivery_at_customs}
                <p class="text-sm text-destructive">{errors.actual_delivery_at_customs}</p>
            {/if}
        </div>
        <div class="flex flex-col gap-2">
            <label class="text-sm font-medium text-mono" for="actual-delivery-at-site-import">Actual Delivery at Site</label>
            <Flatpickr
                id="actual-delivery-at-site-import"
                placeholder="Select actual delivery at site..."
                bind:value={actual_delivery_at_site}
                {disabled}
            />
            {#if errors.actual_delivery_at_site}
                <p class="text-sm text-destructive">{errors.actual_delivery_at_site}</p>
            {/if}
        </div>
    {/if}
</div>
