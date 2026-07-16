<script>
    /**
     * SearchBar — debounced search input. Calls `onsearch(value)` after the user
     * stops typing. Bindable `value` for external reset.
     *
     *   <SearchBar onsearch={(v) => list.setSearch(v)} />
     */

    let { value = $bindable(''), placeholder = null, debounceMs = 400, onsearch } = $props();

    let timer;

    function handleInput(event) {
        value = event.currentTarget.value;
        clearTimeout(timer);
        timer = setTimeout(() => onsearch?.(value), debounceMs);
    }

    function clear() {
        value = '';
        clearTimeout(timer);
        onsearch?.('');
    }
</script>

<div class="kt-input max-w-[240px]">
    <i class="ki-filled ki-magnifier text-muted-foreground"></i>
    <input
        type="text"
        placeholder={placeholder ?? 'Search'}
        {value}
        oninput={handleInput}
    />
    {#if value}
        <button type="button" class="text-muted-foreground" onclick={clear} aria-label="Clear">
            <i class="ki-filled ki-cross"></i>
        </button>
    {/if}
</div>
