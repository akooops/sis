<?php

namespace App\Data\Notification;

use App\Models\NotificationType;
use Spatie\LaravelData\Data;

class NotificationTypeData extends Data
{
    public function __construct(
        public string $id,
        public string $code,
        public string $name,
        public ?string $icon,
        public int $sort,
    ) {}

    public static function fromModel(NotificationType $type): self
    {
        return new self(
            id: $type->id,
            code: $type->code,
            name: $type->name,
            icon: $type->icon,
            sort: $type->sort,
        );
    }
}
