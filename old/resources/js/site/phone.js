import intlTelInput from 'intl-tel-input';

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
 * Normalises whatever was typed to E.164 (`+<dial><number>`).
 *
 * libphonenumber only returns a value once the number parses, so a half-typed
 * number would otherwise submit an empty hidden field. The fallback builds the
 * same shape by hand from the selected country's dial code.
 */
export function toE164(iti, raw) {
    const typed = raw.trim();

    if (typed === '') {
        return '';
    }

    // getNumber() echoes the raw input back when it cannot parse it yet, so
    // only trust a value that is already in international form.
    const parsed = iti.getNumber();

    if (parsed && parsed.startsWith('+')) {
        return parsed;
    }

    // v29 names this getSelectedCountry(); it returns the full country object.
    const country = typeof iti.getSelectedCountry === 'function' ? iti.getSelectedCountry() : null;
    const dial = country?.dialCode ?? '';

    let digits = typed.replace(/\D/g, '');

    // Drop a leading trunk zero, and the dial code if the user typed it too.
    digits = digits.replace(/^0+/, '');

    if (dial && digits.startsWith(dial)) {
        digits = digits.slice(dial.length);
    }

    return `+${dial}${digits}`;
}

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
