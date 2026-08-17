<?php

namespace App\Services\Forms;

use App\Models\Form;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

/**
 * The per-render token a public form carries.
 *
 * It exists because two of the spam defences need the server to REMEMBER
 * something about the render, and neither can be trusted to the client:
 *
 *  - the honeypot's field name, which is random per render so it cannot be
 *    learned and skipped;
 *  - when the page was rendered, for the minimum-time check. A hidden
 *    `rendered_at` input is client-controlled and therefore worth nothing.
 *
 * Encrypted rather than signed, because the server has to RECOVER those values,
 * not merely verify them. A database row per page view would work too, and would
 * also let a crawler fill a table — so this carries its own state instead.
 *
 * The honest cost: a page with a per-render token can never be full-page cached.
 */
class SubmissionToken
{
    /** Bump when the payload shape changes, so old tokens fail cleanly. */
    protected const VERSION = 1;

    /**
     * @return array{token: string, honeypot: string, session: string}
     */
    public static function mint(Form $form, ?string $session = null): array
    {
        $honeypot = static::honeypotName($form);

        /*
         * CARRIED OVER ON A RE-RENDER, minted fresh otherwise.
         *
         * The sid is what binds an upload to the visitor who made it —
         * PublicFormUpload refuses media whose `form_session` is not the one in
         * the token. A brand new sid on every render therefore ORPHANS every file
         * the visitor has already uploaded, so a form rejected for a typo came
         * back demanding the CV again. Reusing the sid of their own still-valid
         * token keeps the binding intact without widening it: the caller only
         * ever passes back a token it decrypted from this visitor's own session,
         * for this same form.
         */
        $session ??= (string) Str::ulid();

        $token = Crypt::encryptString(json_encode([
            'v' => self::VERSION,
            'form' => $form->id,
            'sid' => $session,
            'iat' => now()->timestamp,
            'hp' => $honeypot,
            'min' => $form->minSubmitSeconds(),
        ]));

        return ['token' => $token, 'honeypot' => $honeypot, 'session' => $session];
    }

    /**
     * Decrypt and sanity-check a token. Null for anything malformed, tampered
     * with, or from a different version — the caller treats that as spam.
     *
     * @return array{form: string, sid: string, iat: int, hp: string, min: int}|null
     */
    public static function read(?string $token): ?array
    {
        if (! is_string($token) || $token === '') {
            return null;
        }

        try {
            $payload = json_decode(Crypt::decryptString($token), true);
        } catch (DecryptException) {
            return null;
        }

        if (! is_array($payload) || ($payload['v'] ?? null) !== self::VERSION) {
            return null;
        }

        foreach (['form', 'sid', 'iat', 'hp', 'min'] as $key) {
            if (! array_key_exists($key, $payload)) {
                return null;
            }
        }

        return $payload;
    }

    /** Whether the token has outlived config('forms.token_ttl') minutes. */
    public static function isStale(array $payload): bool
    {
        $ttl = (int) config('forms.token_ttl', 120);

        return now()->timestamp - (int) $payload['iat'] > $ttl * 60;
    }

    /** Seconds since the page was rendered. */
    public static function elapsed(array $payload): int
    {
        return max(0, now()->timestamp - (int) $payload['iat']);
    }

    /**
     * A field name no real field uses.
     *
     * The collision check is not paranoia: the honeypot is posted alongside the
     * real answers, and a name clash would silently overwrite somebody's answer
     * with an empty string.
     */
    protected static function honeypotName(Form $form): string
    {
        $taken = $form->fields()->pluck('key')->map(fn ($k) => strtolower($k))->all();

        $words = ['address', 'company', 'website', 'nickname', 'fax', 'title', 'subject', 'homepage'];

        do {
            $name = $words[array_rand($words)].'_'.Str::lower(Str::random(6));
        } while (in_array(strtolower($name), $taken, true));

        return $name;
    }
}
