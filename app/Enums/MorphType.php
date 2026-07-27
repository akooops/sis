<?php

namespace App\Enums;

use App\Models\Achievement;
use App\Models\Album;
use App\Models\ApiKey;
use App\Models\Article;
use App\Models\Category;
use App\Models\Document;
use App\Models\Event;
use App\Models\Grade;
use App\Models\Integration;
use App\Models\Language;
use App\Models\Media;
use App\Models\Notification;
use App\Models\NotificationGroup;
use App\Models\NotificationType;
use App\Models\Page;
use App\Models\Partner;
use App\Models\Permission;
use App\Models\Program;
use App\Models\Role;
use App\Models\Stream;
use App\Models\TranslationKey;
use App\Models\User;

/**
 * Public aliases for the models in polymorphic columns (activity_log.subject,
 * media.model).
 *
 * The DB stores class names; this translates at the API boundary only, so
 * clients filter by `api_key` and not by a namespace we might refactor.
 * Deliberately not a Relation::morphMap — that would rewrite what is stored.
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
    case Language = 'language';
    case TranslationKey = 'translation_key';
    case Page = 'page';
    case Article = 'article';
    case Album = 'album';
    case Event = 'event';
    case Category = 'category';
    case Achievement = 'achievement';
    case Partner = 'partner';
    case Document = 'document';
    case Program = 'program';
    case Stream = 'stream';
    case Grade = 'grade';

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
            self::Language => Language::class,
            self::TranslationKey => TranslationKey::class,
            self::Page => Page::class,
            self::Article => Article::class,
            self::Album => Album::class,
            self::Event => Event::class,
            self::Category => Category::class,
            self::Achievement => Achievement::class,
            self::Partner => Partner::class,
            self::Document => Document::class,
            self::Program => Program::class,
            self::Stream => Stream::class,
            self::Grade => Grade::class,
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
