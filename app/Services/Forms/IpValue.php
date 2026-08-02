<?php

namespace App\Services\Forms;

/**
 * The one place a blocked address is parsed and canonicalised.
 *
 * A blocked entry is compared against request()->ip() as a STRING when it is a
 * single address (FormBlockedIp::matches uses strcasecmp), so what is stored has
 * to be the same spelling PHP hands us — otherwise the row sits in the table
 * matching nothing, which is worse than no row at all because it looks like a
 * block that works.
 *
 * Canonicalising through inet_pton()/inet_ntop() is the same parsing
 * FormBlockedIp::inRange() already does, so validation, storage and matching all
 * agree by construction:
 *
 *   " 203.0.113.5 "        -> 203.0.113.5
 *   2001:DB8:0:0:0:0:0:1   -> 2001:db8::1        (compressed, lower-cased)
 *   10.0.0.5/24            -> 10.0.0.0/24        (host bits masked off)
 *   10.0.0.0/033           -> null               (not a plain decimal prefix)
 *
 * Masking the host bits is what makes 10.0.0.5/24 and 10.0.0.0/24 ONE row: they
 * are the same range, CIDR containment ignores the host bits either way, and
 * without it the (form_id, value) unique index would happily hold both.
 */
class IpValue
{
    /** The canonical form, or null when the value is not an address or range. */
    public static function normalise(?string $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        [$address, $prefix] = array_pad(explode('/', $value, 2), 2, null);

        $packed = @inet_pton(trim((string) $address));

        if ($packed === false) {
            return null;
        }

        if ($prefix === null) {
            $canonical = @inet_ntop($packed);

            return $canonical === false ? null : strtolower($canonical);
        }

        $prefix = trim($prefix);

        // ctype_digit, not is_numeric: "24abc", "+24", " 24 " and "2.4" are all
        // typos, and (int) would quietly turn each of them into a valid-looking
        // range the admin never asked for.
        if ($prefix === '' || ! ctype_digit($prefix)) {
            return null;
        }

        $bits = (int) $prefix;

        if ($bits > strlen($packed) * 8) {
            return null;
        }

        $canonical = @inet_ntop(self::mask($packed, $bits));

        return $canonical === false ? null : strtolower($canonical).'/'.$bits;
    }

    public static function isValid(?string $value): bool
    {
        return self::normalise($value) !== null;
    }

    /**
     * Whether a (normalised) value is a range. Derived, never asked of the
     * admin — a checkbox that restates the slash they just typed is only ever a
     * chance for the two to disagree.
     */
    public static function isCidr(?string $value): bool
    {
        return is_string($value) && str_contains($value, '/');
    }

    /** The number of leading bits, or null for a single address. */
    public static function prefixLength(?string $value): ?int
    {
        if (! self::isCidr($value)) {
            return null;
        }

        return (int) explode('/', (string) $value, 2)[1];
    }

    /** Zero every bit past the prefix, on the packed address so v4 and v6 share it. */
    private static function mask(string $packed, int $bits): string
    {
        $wholeBytes = intdiv($bits, 8);
        $remainder = $bits % 8;

        $network = substr($packed, 0, $wholeBytes);

        if ($remainder > 0) {
            $network .= chr(ord($packed[$wholeBytes]) & (0xFF << (8 - $remainder) & 0xFF));
        }

        return str_pad($network, strlen($packed), chr(0));
    }
}
