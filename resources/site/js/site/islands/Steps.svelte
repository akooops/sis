<script>
    /** Chevron progress strip. Only the current step is visible on phones. */
    let { steps = [], current = 1 } = $props();
</script>

<div class="steps mb-6">
    {#each steps as label, index}
        <div
            class="step flex-1"
            class:is-active={current === index + 1}
            class:is-done={current > index + 1}
        >
            <span>{label}</span>
        </div>
    {/each}
</div>
