<?php

namespace App\Data\Form;

use App\Data\Notification\NotificationGroupData;
use App\Models\FormNotificationGroup;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;

/**
 * One form → one notification group link.
 *
 * Both ends are lazy because this resource is read from both sides: the form's
 * page wants the group, the group's page wants the form. Whichever the caller
 * eager-loads is the one that ships.
 */
class FormNotificationGroupData extends Data
{
    public function __construct(
        public string $id,
        public string $form_id,
        public string $notification_group_id,
        public ?string $created_at,
        public ?string $updated_at,
        public Lazy|FormData $form,
        public Lazy|NotificationGroupData $group,
    ) {}

    public static function fromModel(FormNotificationGroup $link): self
    {
        return new self(
            id: $link->id,
            form_id: $link->form_id,
            notification_group_id: $link->notification_group_id,
            created_at: $link->created_at?->toIso8601String(),
            updated_at: $link->updated_at?->toIso8601String(),
            form: Lazy::whenLoaded('form', $link, fn () => FormData::from($link->form)),
            group: Lazy::whenLoaded('group', $link, fn () => NotificationGroupData::from($link->group)),
        );
    }
}
