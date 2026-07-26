<script>
    /**
     * InlineEdit — an always-visible editable cell: an input with its own Save
     * button. No click-to-open step; the whole column reads as a form, which is
     * what you want when the page exists to edit every row.
     *
     *   <InlineEdit value={row.value} placeholder="Untranslated"
     *               onsave={(v) => save(row, v)} />
     *
     * Save is disabled until the input differs from `value`, so an untouched row
     * can't fire a pointless write. Enter saves too. `onsave` may return a
     * promise; it is awaited, and a rejection leaves the typed value in place
     * with the message on the field so nothing is lost.
     *
     * Deliberately NOT part of DataTable: the table stays presentational and any
     * page can drop this into its own `cells` snippet. Note it stops click
     * propagation — DataTable puts onRowClick on every <td>, and a click into
     * the input must not also open a row.
     */
    import Spinner from '@/components/ui/Spinner.svelte';

    let { value = '', placeholder = '—', disabled = false, onsave } = $props();

    let draft = $state(value ?? '');
    let saving = $state(false);
    let saved = $state(false);
    let error = $state(null);
    let tickTimer = null;

    // Plain variable, NOT $state: this is a comparison only. Re-seed the draft
    // when the row's value changes from outside (page change, sort, refresh) —
    // guarded by the compare so an unrelated re-render can't wipe what the user
    // is halfway through typing.
    let lastValue = value;

    $effect(() => {
        if (value !== lastValue) {
            lastValue = value;
            draft = value ?? '';
            error = null;
        }
    });

    const dirty = $derived(draft !== (value ?? ''));

    async function save() {
        if (disabled || saving || !dirty) return;

        saving = true;
        error = null;

        try {
            await onsave?.(draft);
            saved = true;
            clearTimeout(tickTimer);
            tickTimer = setTimeout(() => (saved = false), 1500);
        } catch (e) {
            // Keep the typed value on screen so the failure costs nothing.
            error = e?.message ?? 'Could not save. Please try again.';
        } finally {
            saving = false;
        }
    }

    function onKeydown(event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            save();
        }
    }

    $effect(() => () => clearTimeout(tickTimer));
</script>

<!-- svelte-ignore a11y_no_static_element_interactions, a11y_click_events_have_key_events -->
<div class="flex w-full items-center gap-2" onclick={(e) => e.stopPropagation()}>
    <input
        class="kt-input kt-input-sm min-w-0 grow {error ? 'border-destructive' : ''}"
        bind:value={draft}
        disabled={disabled || saving}
        {placeholder}
        onkeydown={onKeydown}
        aria-invalid={!!error}
        aria-label="Translation value"
    />

    {#if !disabled}
        <button
            type="button"
            class="kt-btn kt-btn-sm kt-btn-icon {dirty ? 'kt-btn-primary' : 'kt-btn-secondary'} shrink-0"
            disabled={!dirty || saving}
            onclick={save}
            aria-label="Save"
            title={error ?? (dirty ? 'Save' : 'No changes to save')}
        >
            {#if saving}
                <Spinner size="sm" />
            {:else if saved}
                <!-- Double check for "saved", so it reads apart from the single
                     check that means "click to save". -->
                <i class="ki-filled ki-double-check text-success"></i>
            {:else}
                <i class="ki-filled ki-check"></i>
            {/if}
        </button>
    {/if}

    {#if error}
        <i class="ki-filled ki-cross-circle shrink-0 text-destructive" title={error}></i>
    {/if}
</div>
