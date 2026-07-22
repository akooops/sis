<?php

namespace App\Enums;

use App\Models\ApiKey;
use App\Models\Integration;
use App\Models\Media;
use App\Models\Notification;
use App\Models\NotificationGroup;
use App\Models\NotificationType;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;

/**
 * Short public aliases for the models that appear in polymorphic columns
 * (activity_log.subject/causer, media.model).
 *
 * The database stores fully-qualified class names; this only translates at the
 * API boundary, so clients filter by `api_key` rather than by a namespace we
 * would then be unable to refactor. Deliberately not a Relation::morphMap —
 * enforcing one would rewrite what gets stored and break existing rows.
 */
enum MorphType: string
{
    case User = 'user';
    case Role = 'role';
    case Permission = 'permission';
    case ApiKey = 'api_key';
    case Media = 'media';
    case Integration = 'integration';
    case Notification = 'notification';
    case NotificationGroup = 'notification_group';
    case NotificationType = 'notification_type';

    /** @return class-string */
    public function class(): string
    {
        return match ($this) {
            self::User => User::class,
            self::Role => Role::class,
            self::Permission => Permission::class,
            self::ApiKey => ApiKey::class,
            self::Media => Media::class,
            self::Integration => Integration::class,
            self::Notification => Notification::class,
            self::NotificationGroup => NotificationGroup::class,
            self::NotificationType => NotificationType::class,
        };
    }

    public static function fromClass(?string $class): ?self
    {
        if ($class === null) {
            return null;
        }

        foreach (self::cases() as $case) {
            if ($case->class() === $class) {
                return $case;
            }
        }

        return null;
    }

    /** The class an alias maps to, or null if the alias is unknown. */
    public static function classFor(?string $alias): ?string
    {
        return $alias === null ? null : self::tryFrom($alias)?->class();
    }

    /** The alias for a class, ready to hand to a client. */
    public static function aliasFor(?string $class): ?string
    {
        return self::fromClass($class)?->value;
    }
}
