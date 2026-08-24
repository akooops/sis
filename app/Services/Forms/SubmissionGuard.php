<?php

namespace App\Services\Forms;

use App\Services\Analytics\GeoResolver;
use App\Models\Form;
use App\Models\FormBlockedIp;
use Illuminate\Http\Request;

/**
 * The checks that run before a submission is even validated.
 *
 * Ordered deliberately. Country and the caps come BEFORE the honeypot so a
 * legitimate visitor who is simply blocked or too late does not also burn a spam
 * row. The honeypot comes before the captcha because it costs nothing and a bot
 * that trips it should never reach a paid provider.
 *
 * Two of these return a FAKE SUCCESS rather than an error: telling a bot which
 * defence caught it is free tuning data. That is exactly why the row is still
 * written — it is the only trace that the attempt happened.
 */
class SubmissionGuard
{
    public function __construct(protected GeoResolver $geo) {}

    /** Blocked by country, given a resolved code. */
    public function blocksCountry(Form $form, ?string $countryCode): bool
    {
        if ($countryCode === null) {
            return false;
        }

        return $form->blockedCountries()->where('code', $countryCode)->exists();
    }

    /** Blocked by address or range. */
    public function blocksIp(Form $form, ?string $ip): bool
    {
        if (! is_string($ip) || $ip === '') {
            return false;
        }

        return $form->blockedIps
            ->contains(fn (FormBlockedIp $entry) => $entry->matches($ip));
    }

    /** Whether this visitor has already used up their per-visitor allowance. */
    public function overPerUserLimit(Form $form, string $ipHash, ?string $fingerprint): bool
    {
        if (! $form->is_user_limited || ! $form->per_user_limit) {
            return false;
        }

        $query = $form->submissions()->completed();

        /*
         * `both` means a match on EITHER signal counts as the same person — the
         * stricter reading. Requiring both to match is trivially defeated by
         * clearing one of them, which makes the cap decorative.
         */
        match ($form->per_user_limit_by) {
            'fingerprint' => $query->where('fingerprint', $fingerprint),
            'both' => $query->where(fn ($q) => $q->where('ip_hash', $ipHash)->orWhere('fingerprint', $fingerprint)),
            default => $query->where('ip_hash', $ipHash),
        };

        return $query->count() >= $form->per_user_limit;
    }

    /**
     * Whether the honeypot says bot.
     *
     * BRANCHES ON KEY PRESENCE, NOT EMPTINESS, and this is the subtle part.
     * ConvertEmptyStringsToNull is global middleware and TransformsRequest
     * cleans JSON bodies too, so a real person's untouched hidden input arrives
     * as NULL — indistinguishable from a bot that stripped the field entirely.
     *
     *   key missing            -> bot (it never rendered the form)
     *   key present, filled    -> bot (it filled a field no human can see)
     *   key present, null/''   -> human
     */
    public function honeypotTripped(Request $request, string $fieldName): bool
    {
        $bag = $request->input('honeypot');

        if (! is_array($bag) || ! array_key_exists($fieldName, $bag)) {
            return true;
        }

        $value = $bag[$fieldName];

        return $value !== null && trim((string) $value) !== '';
    }

    /** A hashed address — always stored, because it is what enforces the caps. */
    public function hashIp(?string $ip): string
    {
        return hash_hmac('sha256', (string) $ip, config('app.key'));
    }

    /**
     * A device signature from PASSIVE signals only — headers the browser sends
     * anyway, plus what the page reports about the screen. No canvas, WebGL,
     * audio or font probing.
     *
     * Stable for days to weeks and it collides freely across identical corporate
     * laptops. The admin UI calls it "device signature (approximate)" for that
     * reason: it is a speed bump, not an identity.
     */
    public function fingerprint(Request $request, array $client = []): string
    {
        return hash('sha256', implode('|', [
            $request->userAgent() ?? '',
            $request->header('Accept-Language') ?? '',
            $client['timezone'] ?? '',
            $client['screen'] ?? '',
            $client['platform'] ?? '',
            config('app.key'),
        ]));
    }
}
