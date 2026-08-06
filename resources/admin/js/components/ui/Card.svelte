<script>
    /** Card — KTUI `kt-card` shell with optional header/footer snippets. */
    let { title = null, class: klass = '', bodyClass = 'kt-card-content', header, footer, children } = $props();
</script>

<div class="kt-card {klass}">
    {#if header || title}
        <div class="kt-card-header">
            {#if header}
                {@render header()}
            {:else}
                <h3 class="kt-card-title">{title}</h3>
            {/if}
        </div>
    {/if}

    <div class={bodyClass}>
        {@render children?.()}
    </div>

    {#if footer}
        <div class="kt-card-footer">
            {@render footer()}
        </div>
    {/if}
</div>
