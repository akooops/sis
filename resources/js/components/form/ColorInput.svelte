<script>
    /**
     * ColorInput — a hex colour, as a swatch + a text field + a preset row.
     *
     *   <ColorInput bind:value={form.data.color} invalid={!!form.errors.color} />
     *
     * The value is always normalised to uppercase #RRGGBB, because that is the
     * only form the server accepts. The native picker can't express anything
     * else, but the text field can, so it normalises on blur rather than on every
     * keystroke — otherwise typing `#1b8` would be rewritten under the cursor.
     */
    let {
        value = $bindable('#1B84FF'),
        invalid = false,
        presets = ['#1B84FF', '#17C653', '#F6C000', '#F8285A', '#7239EA', '#0D9394', '#5C5C5C', '#111827'],
    } = $props();

    const HEX = /^#[0-9A-Fa-f]{6}$/;

    // What the native swatch shows: it only accepts valid hex, and would silently
    // fall back to black while the text field holds a half-typed value.
    const swatch = $derived(HEX.test(value ?? '') ? value : '#000000');

    function normalise() {
        let next = String(value ?? '').trim();
        if (next && !next.startsWith('#')) next = `#${next}`;
        // Allow the #abc shorthand — expand it rather than reject it.
        if (/^#[0-9A-Fa-f]{3}$/.test(next)) {
            next = `#${next[1]}${next[1]}${next[2]}${next[2]}${next[3]}${next[3]}`;
        }
        if (HEX.test(next)) value = next.toUpperCase();
    }
</script>

<div class="flex flex-col gap-2">
    <div class="flex items-center gap-2">
        <input
            type="color"
            value={swatch}
            class="size-9 shrink-0 cursor-pointer rounded-lg border border-border bg-transparent p-0.5"
            oninput={(e) => (value = e.currentTarget.value.toUpperCase())}
            aria-label="Pick a colour"
        />
        <input
            type="text"
            {value}
            class="kt-input font-mono uppercase {invalid ? 'kt-input-error border-destructive' : ''}"
            maxlength="7"
            placeholder="#1B84FF"
            oninput={(e) => (value = e.currentTarget.value)}
            onblur={normalise}
            aria-invalid={invalid}
        />
    </div>

    <div class="flex flex-wrap items-center gap-1.5">
        {#each presets as preset (preset)}
            <button
                type="button"
                class="size-6 rounded-md border transition {value?.toUpperCase() === preset ? 'border-mono ring-2 ring-primary/40' : 'border-border'}"
                style="background-color: {preset}"
                title={preset}
                aria-label={preset}
                onclick={() => (value = preset)}
            ></button>
        {/each}
    </div>
</div>
