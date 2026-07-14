<script>
    /**
     * IdBadge — compact record id shown as `#abcdef` (first 6 chars) in the brand
     * primary colour. When `onclick` is given it's a button that opens the record
     * (e.g. the view drawer); the click is stopped from bubbling to the row.
     *
     *   <IdBadge id={row.id} onclick={() => view(row)} />
     */
    let { id = '', onclick = null, chars = 6 } = $props();

    const short = $derived(String(id ?? '').slice(0, chars));
</script>

{#if onclick}
    <button
        type="button"
        class="text-primary font-medium font-mono text-2sm hover:underline"
        title={id}
        onclick={(e) => {
            e.stopPropagation();
            onclick();
        }}
    >
        #{short}
    </button>
{:else}
    <span class="text-primary font-medium font-mono text-2sm" title={id}>#{short}</span>
{/if}
