<?php

namespace App\Data\Form;

use App\Models\FormWebhook;
use App\Services\Forms\WebhookConfig;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/**
 * A full replace, like UpdateFormData — every field is sent every time, and the
 * one exception is the secret, which the form cannot send because it is never
 * shown. Blank there means "keep what is stored" (see WebhookConfig).
 *
 * The form a webhook belongs to is fixed: a webhook is configured against the
 * form whose submissions it delivers, and moving it would silently re-point it
 * at another form's data.
 */
class UpdateFormWebhookData extends Data
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
        $webhook = request()->route('formWebhook');
        $webhook = $webhook instanceof FormWebhook ? $webhook : null;

        return WebhookConfig::rules($webhook, $context->payload);
    }

    /**
     * @return array<string, string>
     */
    public static function attributes(...$args): array
    {
        return WebhookConfig::attributes(request()->all());
    }
}
