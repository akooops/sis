/**
 * E.164 normalisation, with NO dependency on intl-tel-input.
 *
 * It lives apart from site/phone.js for one reason: PhoneControl needs this
 * function and must not pull in the plugin. intl-tel-input plus its country
 * metadata is ~90 KB, and the control is a leaf component that also renders
 * where no plugin is attached at all — the builder, a preview, anywhere the
 * enhancement has not run. So the maths lives here and the plugin lives there.
 *
 * The `iti` argument is duck-typed: anything exposing getNumber() and
 * getSelectedCountry() will do, which is exactly what makes this importable
 * without the library.
 */

/**
 * Normalises whatever was typed to E.164 (`+<dial><number>`).
 *
 * libphonenumber only returns a value once the number parses, so a half-typed
 * number would otherwise normalise to nothing. The fallback builds the same
 * shape by hand from the selected country's dial code.
 *
 * @param {{getNumber: Function, getSelectedCountry?: Function}|null} iti
 * @param {string} raw
 */
export function toE164(iti, raw) {
    const typed = (raw ?? '').trim();

    if (typed === '' || !iti) {
        return typed;
    }

    /*
     * getNumber() needs the utils chunk, which loads separately and may never
     * arrive; until then it returns '' or echoes the raw input back. So it is
     * only trusted when it answers in international form — and the dial-code
     * assembly below is the path that actually runs most of the time, not a
     * rare edge case.
     */
    const parsed = typeof iti.getNumber === 'function' ? iti.getNumber() : '';

    if (parsed && parsed.startsWith('+')) {
        return parsed;
    }

    const dial = selectedDialCode(iti);

    // No country to prefix with: hand back what was typed and let the server
    // reject it, rather than inventing "+" and a number.
    if (!dial) {
        return typed;
    }

    let digits = typed.replace(/\D/g, '');

    // Drop a leading trunk zero, and the dial code if the visitor typed it too.
    digits = digits.replace(/^0+/, '');

    if (digits.startsWith(dial)) {
        digits = digits.slice(dial.length);
    }

    return `+${dial}${digits}`;
}

/**
 * The selected country's dial code, across BOTH names the plugin has used.
 *
 * v24 — the version installed — exposes getSelectedCountryData(). v25 renamed it
 * getSelectedCountry(). This used to call the v25 name only, so on v24 it read
 * `undefined`, found no dial code, and quietly handed the national number
 * straight through: the visitor picked a flag, typed 0555 123 456, and was told
 * their own number was invalid. Reading both means neither an upgrade nor a
 * downgrade can bring that back.
 */
function selectedDialCode(iti) {
    for (const name of ['getSelectedCountryData', 'getSelectedCountry']) {
        if (typeof iti[name] === 'function') {
            const dial = iti[name]()?.dialCode;

            if (dial) {
                return String(dial);
            }
        }
    }

    return '';
}
