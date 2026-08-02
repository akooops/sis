/**
 * Client-side ULID generation.
 *
 * The builder mints ids BEFORE saving, on purpose. Every model in this app uses
 * HasUlids, so a client-supplied id is legitimate — and it is what stops a save
 * from re-keying every card. If the server assigned the ids, each save would
 * hand back new ones, Svelte's keyed {#each} would tear down and rebuild every
 * element, and the admin would lose their scroll position, their selection and
 * whatever field had focus.
 *
 * Crockford base32, 10 characters of timestamp then 16 of randomness — the same
 * shape Laravel's `ulid` validation rule expects.
 */
const ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

function encodeTime(now, length) {
    let out = '';

    for (let i = length - 1; i >= 0; i -= 1) {
        out = ALPHABET[now % 32] + out;
        now = Math.floor(now / 32);
    }

    return out;
}

function encodeRandom(length) {
    const bytes = new Uint8Array(length);

    // crypto is available in every browser this admin supports; the fallback
    // exists so a non-DOM context (a test runner) does not explode.
    if (globalThis.crypto?.getRandomValues) {
        globalThis.crypto.getRandomValues(bytes);
    } else {
        for (let i = 0; i < length; i += 1) bytes[i] = Math.floor(Math.random() * 256);
    }

    let out = '';
    for (let i = 0; i < length; i += 1) out += ALPHABET[bytes[i] % 32];

    return out;
}

export function ulid(now = Date.now()) {
    return encodeTime(now, 10) + encodeRandom(16);
}
