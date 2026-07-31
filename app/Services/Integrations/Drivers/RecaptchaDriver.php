<?php

namespace App\Services\Integrations\Drivers;

use App\Contracts\Integrations\Driver;
use App\Contracts\Integrations\VerifiesCaptcha;
use App\Data\Integration\CaptchaResultData;
use App\Data\Integration\FieldData;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Google reCAPTCHA, v2 (checkbox) and v3 (score) behind one schema.
 *
 * Nothing here throws — unlike the AI driver, this runs on public forms, where an
 * exception would 500 the page. Misconfiguration, network failure and a malformed
 * body all return unavailable(), which the caller treats as retry, never as bot.
 */
class RecaptchaDriver implements Driver, VerifiesCaptcha
{
    protected const VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    public function code(): string
    {
        return 'recaptcha';
    }

    public function type(): string
    {
        return 'captcha';
    }

    public function label(): string
    {
        return 'Google reCAPTCHA';
    }

    public function icon(): string
    {
        return 'ki-shield-tick';
    }

    /**
     * @return array<int, FieldData>
     */
    public function schema(): array
    {
        return [
            // Not secret: the site key is rendered into the page HTML.
            new FieldData(key: 'site_key', label: 'Site key', type: 'text', required: true),
            new FieldData(key: 'secret_key', label: 'Secret key', type: 'password', required: true, secret: true),
            // Required, or the Select renders clearable and a null here would read
            // back as v2 — silently skipping the score check on a v3 integration.
            new FieldData(key: 'version', label: 'Version', type: 'select', required: true, default: 'v2', options: [
                ['value' => 'v2', 'label' => 'v2 (checkbox)'],
                ['value' => 'v3', 'label' => 'v3 (score)'],
            ]),
            new FieldData(key: 'score_threshold', label: 'Score threshold', type: 'number', default: 0.5, help: 'v3 only: submissions scoring below this are rejected. 0 is certainly a bot, 1 certainly a human.'),
        ];
    }

    public function verify(string $token, array $config, ?string $ip = null): CaptchaResultData
    {
        $secret = $config['secret_key'] ?? null;

        if (! $secret) {
            Log::channel('integrations')->error('reCAPTCHA verify skipped: no secret key configured.');

            return CaptchaResultData::unavailable();
        }

        $payload = ['secret' => $secret, 'response' => $token];

        if ($ip !== null) {
            $payload['remoteip'] = $ip;
        }

        try {
            $response = Http::asForm()
                ->timeout((int) config('integrations.timeout', 15))
                ->post(self::VERIFY_URL, $payload);
        } catch (Throwable) {
            Log::channel('integrations')->error('reCAPTCHA verify failed: could not reach provider.');

            return CaptchaResultData::unavailable();
        }

        if (! $response->successful()) {
            Log::channel('integrations')->warning('reCAPTCHA verify rejected', ['status' => $response->status()]);

            return CaptchaResultData::unavailable();
        }

        $json = $response->json();

        if (! is_array($json) || ! array_key_exists('success', $json)) {
            Log::channel('integrations')->warning('reCAPTCHA verify returned a malformed body.');

            return CaptchaResultData::unavailable();
        }

        if (! $json['success']) {
            // Diagnostic only — invalid-input-secret is our bug, timeout-or-duplicate is theirs.
            Log::channel('integrations')->warning('reCAPTCHA challenge failed', ['errors' => $json['error-codes'] ?? []]);

            return CaptchaResultData::failed();
        }

        if (($config['version'] ?? 'v2') !== 'v3') {
            return CaptchaResultData::passed();
        }

        $score = (float) ($json['score'] ?? 0);
        $threshold = (float) ($config['score_threshold'] ?? 0.5);

        if ($score < $threshold) {
            Log::channel('integrations')->warning('reCAPTCHA score below threshold', ['score' => $score, 'threshold' => $threshold]);

            return CaptchaResultData::failed($score);
        }

        return CaptchaResultData::passed($score);
    }
}
