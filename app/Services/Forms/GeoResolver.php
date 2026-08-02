<?php

namespace App\Services\Forms;

use App\Models\Country;
use Illuminate\Http\Request;

/**
 * Where a visitor is, from a CDN's country header.
 *
 * TRUSTED ONLY BEHIND A RECOGNISED PROXY. These headers are ordinary request
 * headers: any client can set CF-IPCountry to whatever it likes. Honouring one
 * on a direct connection would let anybody walk straight past a country block by
 * claiming to be somewhere else.
 *
 * FAILS OPEN. If geo cannot be resolved the visitor is not blocked — a
 * misconfigured header would otherwise take the whole form offline for everyone,
 * which is a far worse failure than letting a blocked country through. The admin
 * UI says so next to the picker.
 */
class GeoResolver
{
    /** ISO alpha-2, or null when it cannot be trusted or is not present. */
    public function countryCode(Request $request): ?string
    {
        if (! $request->isFromTrustedProxy()) {
            return null;
        }

        foreach (config('forms.geo.country_headers', []) as $header) {
            $value = $request->header($header);

            if (is_string($value) && preg_match('/^[A-Za-z]{2}$/', $value)) {
                // Cloudflare sends XX for "unknown" and T1 for Tor.
                $code = strtoupper($value);

                return in_array($code, ['XX', 'T1'], true) ? null : $code;
            }
        }

        return null;
    }

    public function country(Request $request): ?Country
    {
        $code = $this->countryCode($request);

        return $code ? Country::where('code', $code)->first() : null;
    }

    /** Whether geo is actually usable, for the admin warning banner. */
    public function isConfigured(Request $request): bool
    {
        return $request->isFromTrustedProxy();
    }
}
