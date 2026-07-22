<?php

namespace App\Data\Notification;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

class StoreNotificationGroupUserData extends Data
{
    /**
     * @param  array<int, string>  $users
     */
    public function __construct(
        public array $users,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        return [
            'users' => ['required', 'array'],
            'users.*' => ['string', 'exists:users,id'],
        ];
    }
}
