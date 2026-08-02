<?php

namespace App\Data\Notification;

use App\Models\IntegrationType;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

class UpdateNotificationGroupData extends Data
{
    /**
     * @param  array<int, string>  $notification_type_ids
     */
    public function __construct(
        public string $name,
        public string $code,
        public ?string $integration_id = null,
        public array $notification_type_ids = [],
    ) {}

    public static function rules(ValidationContext $context): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255', Rule::unique('notification_groups', 'code')->ignore(request()->route('notificationGroup'))],

            'integration_id' => ['nullable', 'string', Rule::exists('integrations', 'id')->where(
                fn ($query) => $query->whereIn('integration_type_id', IntegrationType::query()->where('code', 'email')->select('id'))
            )],
            // The whole catalogue is subscribable — see StoreNotificationGroupData.
            'notification_type_ids' => ['sometimes', 'array'],
            'notification_type_ids.*' => ['string', Rule::exists('notification_types', 'id')],
        ];
    }
}
