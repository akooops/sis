import intlTelInput from 'intl-tel-input';

/* Defined in lib/phone.js, not here, and re-exported for the callers that
   already import it from this module: PhoneControl needs the same normalisation
   and must NOT import this file, because that would drag ~90 KB of country
   metadata into every bundle the control appears in. */
import { toE164 } from '@site/lib/phone';

export { toE164 };

/* The stylesheet is imported from site.css inside the components layer —
   importing it here would leave it unlayered and outranking our overrides. */

/**
 * One place to configure the international phone field. libphonenumber is the
 * bulk of this library, so it loads as its own chunk after the field is up —
 * the input is usable immediately and formatting kicks in a moment later.
 *
 * `separateDialCode` is off: the flag alone identifies the country, and the
 * dial code is added when the value is normalised rather than shown twice.
 */
const OPTIONS = {
    initialCountry: 'sa',
    countryOrder: ['sa', 'ae', 'eg'],
    separateDialCode: false,
    formatOnDisplay: true,
    loadUtils: () => import('intl-tel-input/utils'),
};

/**
 * Keeps a hidden field in sync with the E.164 value while the visible field
 * shows the friendly national format. Returns { sync, destroy }.
 */
export function bindPhoneInput(input, onChange) {
    const iti = intlTelInput(input, OPTIONS);

    // Handy for debugging, and lets other code read the parsed number.
    input.iti = iti;

    let debounce;

    const sync = () => onChange(toE164(iti, input.value));

    const onInput = () => {
        window.clearTimeout(debounce);
        debounce = window.setTimeout(sync, 300);
    };

    input.addEventListener('blur', sync);
    input.addEventListener('countrychange', sync);
    input.addEventListener('input', onInput);

    return {
        sync,
        destroy() {
            window.clearTimeout(debounce);
            input.removeEventListener('blur', sync);
            input.removeEventListener('countrychange', sync);
            input.removeEventListener('input', onInput);
            iti.destroy();
        },
    };
}

/**
 * Attach the country selector to an input that manages its own value.
 *
 * For the form renderer, whose phone control is bound to Svelte state rather
 * than to a hidden sibling. Two jobs, and the second one used to be missing:
 *
 * 1. Forward a country change. Picking a country rewrites the input WITHOUT
 *    firing `input`, so the control would keep the old number.
 * 2. Expose the plugin as `input.iti`, which is how PhoneControl reaches
 *    toE164() and answers with the international number.
 *
 * THE VALUE ON SCREEN IS NOT THE ANSWER, and assuming it was is what broke this
 * field. `separateDialCode` is off, so the reasoning went, the dial code stays
 * in the box and the box is already E.164. It is not: with `formatOnDisplay` the
 * plugin renders the NATIONAL format for the selected country, so a visitor who
 * picks the flag and types 0555 123 456 leaves "0555 123 456" in the input.
 * PhoneFormatter::e164() parses with region = null by design, so it cannot read
 * that, and App\Rules\PhoneNumber rejects anything without a leading "+" — the
 * visitor is told their own correctly-entered number is invalid.
 */
export function attachPhoneWidget(input) {
    /*
     * Initialising can empty the box.
     *
     * The utils chunk loads separately, so at init the plugin often cannot parse
     * what is already there and drops it. That is invisible on a blank form and
     * very visible on a repopulated one: a submission rejected for a bad phone
     * number came back with every field refilled EXCEPT the phone, so the visitor
     * was shown "that number is not valid" pointing at an empty box.
     *
     * Captured before, restored after, through the plugin's own setter first so
     * the flag follows the number rather than staying on the default country.
     */
    const initial = input.value;

    const iti = intlTelInput(input, OPTIONS);

    if (initial) {
        if (typeof iti.setNumber === 'function') {
            iti.setNumber(initial);
        }

        if (!input.value) {
            input.value = initial;
        }
    }

    input.iti = iti;

    const forward = () => input.dispatchEvent(new Event('input', { bubbles: true }));

    input.addEventListener('countrychange', forward);

    return {
        destroy() {
            input.removeEventListener('countrychange', forward);
            iti.destroy();
        },
    };
}

/**
 * Plain-Blade phone fields (contact, inquiries). The visible input shows the
 * national format; `data-phone-input` points at the hidden field that carries
 * the E.164 value the server validates.
 *
 *   <input type="tel" data-phone-input="#phone-e164">
 *   <input type="hidden" name="phone" id="phone-e164">
 */
export default function initPhoneInputs(root = document) {
    root.querySelectorAll('[data-phone-input]').forEach((input) => {
        const target = document.querySelector(input.dataset.phoneInput);

        if (!target) {
            return;
        }

        const { sync } = bindPhoneInput(input, (value) => {
            target.value = value;
        });

        // Submitting without ever blurring the field must still send a value,
        // so normalise synchronously on the way out.
        input.form?.addEventListener('submit', sync);
    });
}
