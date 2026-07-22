<?php

namespace App\Data\Notification;

use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

class UpdateNotificationGroupData extends Data
{
    /**
     * @param  array<int, string>  $notification_type_ids
     * @param  array<int, string>  $user_ids
     */
    public function __construct(
        public string $name,
        public string $code,
        public ?string $description,
        public array $notification_type_ids = [],
        public array $user_ids = [],
    ) {}

    public static function rules(ValidationContext $context): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9._-]+$/', Rule::unique('notification_groups', 'code')->ignore(request()->route('notificationGroup'))],
            'description' => ['nullable', 'string'],
            'notification_type_ids' => ['sometimes', 'array'],
            'notification_type_ids.*' => ['string', 'exists:notification_types,id'],
            'user_ids' => ['sometimes', 'array'],
            'user_ids.*' => ['string', 'exists:users,id'],
        ];
    }
}
