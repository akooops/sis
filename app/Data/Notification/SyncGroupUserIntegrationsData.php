<?php

namespace App\Data\Notification;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/**
 * Input for setting a membership's delivery integrations. The controller further
 * restricts these to email/sms integrations (never ai).
 */
class SyncGroupUserIntegrationsData extends Data
{
    /**
     * @param  array<int, string>  $integration_ids
     */
    public function __construct(
        public array $integration_ids = [],
    ) {}

    public static function rules(ValidationContext $context): array
    {
        return [
            'integration_ids' => ['sometimes', 'array'],
            'integration_ids.*' => ['string', 'exists:integrations,id'],
        ];
    }
}
