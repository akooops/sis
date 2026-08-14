<script>
    /**
     * A phone number.
     *
     * A plain tel input, deliberately NOT importing intl-tel-input: that library
     * is ~90 KB plus its country metadata, and this is a leaf control that also
     * renders where no enhancement runs at all. On the public site
     * site/forms.js calls attachPhoneWidget() after mount, which adds the country
     * selector and hangs the plugin on the element as `input.iti`.
     *
     * WHAT IS DISPLAYED IS NOT WHAT IS ANSWERED. With the selector attached the
     * box shows the national format for the chosen country — "0555 123 456" —
     * while the answer this control emits is the international one,
     * "+213555123456". That is the only form the server accepts:
     * PhoneFormatter::e164() parses with region = null on purpose, so a number
     * must carry its own country code, and App\Rules\PhoneNumber rejects
     * anything without a leading "+".
     *
     * With no plugin attached the value passes through untouched and the visitor
     * types the country code themselves, exactly as before.
     */
    import { translate } from '@site/lib/forms/i18n';
    import { toE164 } from '@site/lib/phone';

    let {
        field,
        value = '',
        locale = 'en',
        fallbackLocale = 'en',
        disabled = false,
        id = undefined,
        invalid = false,
        describedBy = undefined,
        onchange = null,
        onfocus = null,
        onblur = null,
    } = $props();

    let el = $state(null);

    const placeholder = $derived(translate(field?.placeholder, locale, fallbackLocale) || '+213555123456');

    /** What is in the box, as the server wants to receive it. */
    function normalised() {
        return el ? toE164(el.iti ?? null, el.value) : '';
    }

    /**
     * Plain `let`s, deliberately not `$state`: flipping either must NOT re-run
     * the effect below, only change what it does the next time the answer moves.
     */
    let seeded = false;
    let dirty = false;

    /**
     * True only while this component is writing the box itself.
     *
     * setNumber() moves the flag to match the number, attachPhoneWidget forwards
     * that `countrychange` as a synthetic `input`, and the renderer clears a
     * field's error as soon as it sees one — so repopulating the number silently
     * deleted the very message that brought the visitor back to the page. A
     * programmatic write is not the visitor typing, and must announce nothing.
     */
    let seeding = false;

    /*
     * The box is written to EXACTLY ONCE, to repopulate a rejected submission,
     * and after that it belongs to the visitor and the plugin. This mirrors
     * admin/js/components/form/PhoneInput.svelte, which is the same widget solved
     * the same way — uncontrolled input, seeded through setNumber(), E.164 out.
     *
     * A plain `value={value}` binding cannot work once the answer and the display
     * are different strings: every keystroke emits "+966555…" and the binding
     * writes that straight back over the national number the visitor is halfway
     * through typing, taking their caret with it. intl-tel-input listens for
     * `input` on this same element and rewrites the value itself, so a second
     * unconditional writer makes the field a race between two handlers.
     *
     * Waiting for a NON-EMPTY value is the part that is easy to get wrong: the
     * parent seeds `answers` from old() in its own effect, a tick after this
     * mounts, so a latch that closed on the first render closed on '' — and the
     * rejected submission came back with every field repopulated except the
     * phone, under a red message pointing at an empty box.
     *
     * `dirty` guards the mirror image of that: once the visitor has typed, the
     * non-empty value is THEIRS, and seeding from it would overwrite what they
     * can see with its E.164 form.
     */
    $effect(() => {
        const next = value ?? '';

        if (! el || seeded || dirty || next === '') {
            return;
        }

        seeded = true;
        seeding = true;

        try {
            // Through the plugin when it is up, so the flag follows the number
            // instead of staying on the default country.
            if (typeof el.iti?.setNumber === 'function') {
                el.iti.setNumber(next);
            } else {
                el.value = next;
            }
        } finally {
            // The forwarded `input` arrives synchronously, but the microtask
            // covers a plugin version that defers it.
            queueMicrotask(() => {
                seeding = false;
            });
        }
    });
</script>

<input
    bind:this={el}
    {id}
    type="tel"
    class="sisf-input"
    {placeholder}
    {disabled}
    inputmode="tel"
    autocomplete="tel"
    required={!!field?.is_required}
    aria-invalid={invalid || undefined}
    aria-describedby={describedBy}
    oninput={() => {
        // Our own write, echoed back by the plugin — not the visitor typing.
        if (seeding) {
            return;
        }

        dirty = true;
        onchange?.(field, normalised());
    }}
    onfocus={() => onfocus?.(field)}
    onblur={() => {
        // Re-normalise on the way out. libphonenumber loads as its own chunk, so
        // the first keystrokes fall back to building the number from the dial
        // code by hand; once it has arrived getNumber() is authoritative and
        // this is the moment to take its answer.
        onchange?.(field, normalised());
        onblur?.(field);
    }}
/>
