<?php

namespace App\Data\Form;

use App\Services\Forms\WebhookConfig;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/**
 * `form_id` is NOT here: the form comes from the route, so a caller cannot post
 * a webhook onto a form they navigated away from.
 *
 * `auth` is the raw secret input ({token} | {headers:[{key,value}]}), separate
 * from the stored `auth_config` — the controller folds one into the other so
 * nothing here ever has to be echoed back.
 */
class StoreFormWebhookData extends Data
{
    public function __construct(
        public string $name,
        public string $url,
        public string $method,
        public bool $is_enabled,
        public string $auth_type,
        /** @var array<string, mixed> */
        public array $auth = [],
    ) {}

    public static function rules(ValidationContext $context): array
    {
        // Nothing is stored yet, so every secret the type needs is required.
        return WebhookConfig::rules(null, $context->payload);
    }

    /**
     * @return array<string, string>
     */
    public static function attributes(...$args): array
    {
        return WebhookConfig::attributes(request()->all());
    }
}
