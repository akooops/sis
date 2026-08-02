<?php

namespace App\Data\Form;

use App\Models\FormWebhook;
use Spatie\LaravelData\Data;

/**
 * A webhook as the admin reads it.
 *
 * NEVER carries `auth_config`. The token / static header values are encrypted at
 * rest, $hidden on the model and ignored by the observer — this is the fourth of
 * those four places, and three would be a leak. What the UI gets instead is
 * `has_auth_secret` (is something stored?) and, for the `headers` type, the
 * header NAMES — the same split IntegrationData makes with `secrets_set`: keys
 * are not secrets, values are.
 */
class FormWebhookData extends Data
{
    public function __construct(
        public string $id,
        public string $form_id,
        public string $name,
        public string $url,
        public string $method,
        public bool $is_enabled,
        public string $auth_type,
        /** Whether a secret is stored — never the secret. */
        public bool $has_auth_secret,
        /** @var array<int, string> header names only, and only for auth_type=headers */
        public array $auth_header_keys,
        public ?string $last_delivered_at,
        public ?string $created_at,
        public ?string $updated_at,
    ) {}

    public static function fromModel(FormWebhook $webhook): self
    {
        $config = $webhook->auth_type === 'headers' ? ($webhook->auth_config ?? []) : [];

        return new self(
            id: $webhook->id,
            form_id: $webhook->form_id,
            name: $webhook->name,
            url: $webhook->url,
            method: $webhook->method,
            is_enabled: (bool) $webhook->is_enabled,
            auth_type: $webhook->auth_type,
            has_auth_secret: $webhook->hasAuthSecret(),
            auth_header_keys: array_values(array_filter(array_keys($config), 'is_string')),
            last_delivered_at: $webhook->last_delivered_at?->toIso8601String(),
            created_at: $webhook->created_at?->toIso8601String(),
            updated_at: $webhook->updated_at?->toIso8601String(),
        );
    }
}
