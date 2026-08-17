<?php

namespace App\Services\Forms;

use App\Models\FormSubmission;
use App\Models\FormWebhook;

/**
 * Builds the body and the auth headers of one delivery.
 *
 * THE BODY IS NOT CONFIGURABLE. Every delivery sends the same four keys, so the
 * receiver's schema is identical on every request and it can be written once:
 * the submission id, the form slug, the submitted time, and the whole answer set
 * under `data`. Reshaping that into whatever the receiving system expects is
 * that system's job — this app does not own its payload format.
 *
 * `id` is the ULID and it is the submission's only identifier: it is what the
 * visitor was shown on the thank-you page, so a receiver quoting it back is
 * quoting the same string the person has.
 *
 * `data` IS NO LONGER FLAT SCALARS ONLY. A repeatable group answers with a list
 * of objects keyed by child key:
 *
 *     "data": { "email": "…", "education": [{"institution": "…", "degree": "…"}] }
 *
 * The envelope is unchanged and a form without groups sends exactly what it
 * always did — but a receiver that assumed every value was a string or a list of
 * strings needs to know before a group is added to a form it listens to. A file
 * inside a group is a media id in that nested object, the same as anywhere else.
 */
class WebhookPayload
{
    /** @return array<string, mixed> */
    public static function build(FormSubmission $submission): array
    {
        return [
            'id' => $submission->id,
            'form' => $submission->form?->slug,
            'submitted_at' => $submission->submitted_at?->toIso8601String(),
            'data' => $submission->data ?? [],
        ];
    }

    /**
     * Auth headers for a delivery.
     *
     * @return array<string, string>
     */
    public static function headers(FormWebhook $webhook, string $body): array
    {
        $config = $webhook->auth_config ?? [];
        $headers = ['Content-Type' => 'application/json', 'Accept' => 'application/json'];

        return match ($webhook->auth_type) {
            'bearer' => $headers + ['Authorization' => 'Bearer '.($config['token'] ?? '')],
            'headers' => $headers + array_filter(
                $config,
                fn ($v, $k) => is_string($k) && is_scalar($v),
                ARRAY_FILTER_USE_BOTH,
            ),
            default => $headers,
        };
    }
}
