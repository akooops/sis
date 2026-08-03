<?php

namespace App\Enums;

use App\Models\Achievement;
use App\Models\Album;
use App\Models\ApiKey;
use App\Models\Article;
use App\Models\Banner;
use App\Models\Brand;
use App\Models\BrandAsset;
use App\Models\BrandAssetGroup;
use App\Models\Calendar;
use App\Models\Category;
use App\Models\ContactDetail;
use App\Models\Country;
use App\Models\Document;
use App\Models\Event;
use App\Models\Form;
use App\Models\FormBlockedIp;
use App\Models\FormField;
use App\Models\FormPage;
use App\Models\FormSubmission;
use App\Models\FormWebhook;
use App\Models\Grade;
use App\Models\Integration;
use App\Models\JobOffer;
use App\Models\Language;
use App\Models\Media;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Newsletter;
use App\Models\NewsletterGroup;
use App\Models\NewsletterGroupSubscriber;
use App\Models\Notification;
use App\Models\NotificationGroup;
use App\Models\NotificationType;
use App\Models\Page;
use App\Models\Partner;
use App\Models\Permission;
use App\Models\Program;
use App\Models\Role;
use App\Models\Setting;
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
    case Setting = 'setting';
    case Page = 'page';
    case Article = 'article';
    case Album = 'album';
    case Brand = 'brand';
    case BrandAssetGroup = 'brand_asset_group';
    case BrandAsset = 'brand_asset';
    case Event = 'event';
    case Category = 'category';
    case Achievement = 'achievement';
    case Partner = 'partner';
    case Document = 'document';
    case Banner = 'banner';
    case Calendar = 'calendar';
    case ContactDetail = 'contact_detail';
    case Newsletter = 'newsletter';
    case NewsletterGroup = 'newsletter_group';
    case NewsletterGroupSubscriber = 'newsletter_group_subscriber';
    case Menu = 'menu';
    case MenuItem = 'menu_item';
    case JobOffer = 'job_offer';
    case Country = 'country';
    case Program = 'program';
    case Stream = 'stream';
    case Grade = 'grade';
    case Form = 'form';
    case FormPage = 'form_page';
    case FormField = 'form_field';
    case FormWebhook = 'form_webhook';
    case FormBlockedIp = 'form_blocked_ip';
    case FormSubmission = 'form_submission';

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
            self::Setting => Setting::class,
            self::Page => Page::class,
            self::Article => Article::class,
            self::Album => Album::class,
            self::Brand => Brand::class,
            self::BrandAssetGroup => BrandAssetGroup::class,
            self::BrandAsset => BrandAsset::class,
            self::Event => Event::class,
            self::Category => Category::class,
            self::Achievement => Achievement::class,
            self::Partner => Partner::class,
            self::Document => Document::class,
            self::Banner => Banner::class,
            self::Calendar => Calendar::class,
            self::ContactDetail => ContactDetail::class,
            self::Newsletter => Newsletter::class,
            self::NewsletterGroup => NewsletterGroup::class,
            self::NewsletterGroupSubscriber => NewsletterGroupSubscriber::class,
            self::Menu => Menu::class,
            self::MenuItem => MenuItem::class,
            self::JobOffer => JobOffer::class,
            self::Country => Country::class,
            self::Program => Program::class,
            self::Stream => Stream::class,
            self::Grade => Grade::class,
            self::Form => Form::class,
            self::FormPage => FormPage::class,
            self::FormField => FormField::class,
            self::FormWebhook => FormWebhook::class,
            self::FormBlockedIp => FormBlockedIp::class,
            self::FormSubmission => FormSubmission::class,
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
