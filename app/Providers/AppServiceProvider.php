<?php

namespace App\Providers;

use App\Contracts\Integrations\SendsMail;
use App\Models\Achievement;
use App\Models\Album;
use App\Models\ApiKey;
use App\Models\ApiKeyPermission;
use App\Models\Article;
use App\Models\Banner;
use App\Models\Brand;
use App\Models\BrandAsset;
use App\Models\BrandAssetGroup;
use App\Models\Calendar;
use App\Models\Candidate;
use App\Models\Category;
use App\Models\Cluster;
use App\Models\ContactDetail;
use App\Models\Country;
use App\Models\Document;
use App\Models\Event;
use App\Models\Form;
use App\Models\FormBlockedCountry;
use App\Models\FormBlockedIp;
use App\Models\FormField;
use App\Models\FormFieldOption;
use App\Models\FormNotificationGroup;
use App\Models\FormPage;
use App\Models\FormSubmission;
use App\Models\FormWebhook;
use App\Models\Grade;
use App\Models\Integration;
use App\Models\JobApplication;
use App\Models\JobOffer;
use App\Models\Language;
use App\Models\Media;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Newsletter;
use App\Models\NewsletterGroup;
use App\Models\NewsletterGroupSubscriber;
use App\Models\NewsletterNewsletterGroup;
use App\Models\Notification;
use App\Models\NotificationGroup;
use App\Models\NotificationGroupNotificationType;
use App\Models\NotificationGroupUser;
use App\Models\NotificationUser;
use App\Models\Page;
use App\Models\Partner;
use App\Models\Permission;
use App\Models\Program;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\Session;
use App\Models\Setting;
use App\Models\Stream;
use App\Models\User;
use App\Models\UserRole;
use App\Observers\AchievementObserver;
use App\Observers\AlbumObserver;
use App\Observers\ApiKeyObserver;
use App\Observers\ApiKeyPermissionObserver;
use App\Observers\ArticleObserver;
use App\Observers\BannerObserver;
use App\Observers\BrandAssetGroupObserver;
use App\Observers\BrandAssetObserver;
use App\Observers\BrandObserver;
use App\Observers\CalendarObserver;
use App\Observers\CandidateObserver;
use App\Observers\CategoryObserver;
use App\Observers\ClusterObserver;
use App\Observers\ContactDetailObserver;
use App\Observers\CountryObserver;
use App\Observers\DocumentObserver;
use App\Observers\EventObserver;
use App\Observers\FormBlockedCountryObserver;
use App\Observers\FormBlockedIpObserver;
use App\Observers\FormFieldObserver;
use App\Observers\FormFieldOptionObserver;
use App\Observers\FormNotificationGroupObserver;
use App\Observers\FormObserver;
use App\Observers\FormPageObserver;
use App\Observers\FormSubmissionObserver;
use App\Observers\FormWebhookObserver;
use App\Observers\GradeObserver;
use App\Observers\IntegrationObserver;
use App\Observers\JobApplicationObserver;
use App\Observers\JobOfferObserver;
use App\Observers\LanguageObserver;
use App\Observers\MediaObserver;
use App\Observers\MenuItemObserver;
use App\Observers\MenuObserver;
use App\Observers\NewsletterGroupObserver;
use App\Observers\NewsletterGroupSubscriberObserver;
use App\Observers\NewsletterNewsletterGroupObserver;
use App\Observers\NewsletterObserver;
use App\Observers\NotificationGroupNotificationTypeObserver;
use App\Observers\NotificationGroupObserver;
use App\Observers\NotificationGroupUserObserver;
use App\Observers\NotificationObserver;
use App\Observers\NotificationUserObserver;
use App\Observers\PageObserver;
use App\Observers\PartnerObserver;
use App\Observers\PermissionObserver;
use App\Observers\ProgramObserver;
use App\Observers\RoleObserver;
use App\Observers\RolePermissionObserver;
use App\Observers\SettingObserver;
use App\Observers\StreamObserver;
use App\Observers\UserObserver;
use App\Observers\UserRoleObserver;
use App\Services\Forms\FieldTypeRegistry;
use App\Services\Integrations\Registry;
use App\Services\Sessions\SessionHandler;
use App\Services\Translations\TranslationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session as SessionFacade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Spatie\Activitylog\Facades\CauserResolver;
use Spatie\Translatable\Facades\Translatable;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // One per request: it caches the resolved driver map.
        $this->app->singleton(Registry::class);

        // Same reason: it caches the resolved element map.
        $this->app->singleton(FieldTypeRegistry::class);

        // One per request: it memoises the lang files it reads.
        $this->app->singleton(TranslationService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerViewNamespaces();
        $this->resolveActivityCauser();
        $this->useOurSessionTable();
        $this->useIntegrationMailer();
        $this->useTranslationFallback();

        User::observe(UserObserver::class);
        Role::observe(RoleObserver::class);
        Permission::observe(PermissionObserver::class);
        ApiKey::observe(ApiKeyObserver::class);
        Media::observe(MediaObserver::class);
        UserRole::observe(UserRoleObserver::class);
        RolePermission::observe(RolePermissionObserver::class);
        ApiKeyPermission::observe(ApiKeyPermissionObserver::class);
        Integration::observe(IntegrationObserver::class);
        Notification::observe(NotificationObserver::class);
        NotificationGroup::observe(NotificationGroupObserver::class);
        NotificationGroupNotificationType::observe(NotificationGroupNotificationTypeObserver::class);
        NotificationGroupUser::observe(NotificationGroupUserObserver::class);
        NotificationUser::observe(NotificationUserObserver::class);
        Language::observe(LanguageObserver::class);
        Setting::observe(SettingObserver::class);
        Page::observe(PageObserver::class);
        Article::observe(ArticleObserver::class);
        Album::observe(AlbumObserver::class);
        Brand::observe(BrandObserver::class);
        BrandAssetGroup::observe(BrandAssetGroupObserver::class);
        BrandAsset::observe(BrandAssetObserver::class);
        Event::observe(EventObserver::class);
        Category::observe(CategoryObserver::class);
        Achievement::observe(AchievementObserver::class);
        Partner::observe(PartnerObserver::class);
        Document::observe(DocumentObserver::class);
        Banner::observe(BannerObserver::class);
        Calendar::observe(CalendarObserver::class);
        ContactDetail::observe(ContactDetailObserver::class);
        Newsletter::observe(NewsletterObserver::class);
        NewsletterGroup::observe(NewsletterGroupObserver::class);
        NewsletterGroupSubscriber::observe(NewsletterGroupSubscriberObserver::class);
        NewsletterNewsletterGroup::observe(NewsletterNewsletterGroupObserver::class);
        Menu::observe(MenuObserver::class);
        MenuItem::observe(MenuItemObserver::class);
        JobOffer::observe(JobOfferObserver::class);
        Candidate::observe(CandidateObserver::class);
        JobApplication::observe(JobApplicationObserver::class);
        Cluster::observe(ClusterObserver::class);
        /*
         * DELIBERATELY UNOBSERVED: CandidateMatch, CandidateCluster,
         * JobOfferCluster.
         *
         * All three are written in bulk by the scoring and clustering queues —
         * every candidate against every posting in a shared pool, rebuilt on a
         * schedule — so an observer would add thousands of audit rows a night
         * recording that a machine did exactly what it is supposed to. Same
         * reasoning that keeps Session out of the log. What a PERSON does to a
         * pool is audited on Cluster itself.
         */
        Country::observe(CountryObserver::class);
        Program::observe(ProgramObserver::class);
        Stream::observe(StreamObserver::class);
        Grade::observe(GradeObserver::class);
        Form::observe(FormObserver::class);
        FormPage::observe(FormPageObserver::class);
        FormField::observe(FormFieldObserver::class);
        FormFieldOption::observe(FormFieldOptionObserver::class);
        FormWebhook::observe(FormWebhookObserver::class);
        FormBlockedCountry::observe(FormBlockedCountryObserver::class);
        FormBlockedIp::observe(FormBlockedIpObserver::class);
        FormNotificationGroup::observe(FormNotificationGroupObserver::class);
        FormSubmission::observe(FormSubmissionObserver::class);
    }

    /**
     * The two view roots, each behind its own namespace.
     *
     * resources/ is split into admin/ and site/ — two apps, two Vite bundles,
     * two audiences. Blade is namespaced to match, so `site::layout` can never
     * silently resolve to an admin file (or the reverse) once both halves grow
     * a `layout` or a `pages/show`. config/view.php still lists both paths, so
     * an unprefixed name resolves too; prefer the prefix in our own code.
     */
    protected function registerViewNamespaces(): void
    {
        View::addNamespace('admin', resource_path('admin/views'));
        View::addNamespace('site', resource_path('site/views'));
    }

    /**
     * How a translatable model resolves a locale it has no value for. v6 ships no
     * config file, so this is the only place it can be set.
     *
     * fallbackAny suits a CMS: a page translated only into Arabic should still
     * render. Page CONTENT only — the UI catalogue is lang/*.php, read by __().
     */
    protected function useTranslationFallback(): void
    {
        Translatable::fallback(
            fallbackLocale: config('app.fallback_locale'),
            fallbackAny: true,
        );
    }

    /**
     * Make the active email integration the default mailer for the request.
     * config/mail.php stays the fallback, so Mail::send call-sites are untouched.
     *
     * Single-tenant assumption: overriding the one default mailer at boot is safe.
     * Multi-tenant would set this per job, so one tenant's secret cannot leak.
     */
    protected function useIntegrationMailer(): void
    {
        try {
            if (! Schema::hasTable('integrations')) {
                return;
            }

            $integration = Integration::activeFor('email');

            if (! $integration) {
                return;
            }

            $driver = $integration->resolveDriver();

            if (! $driver instanceof SendsMail) {
                return;
            }

            $config = $integration->config ?? [];

            config([
                'mail.mailers.integration' => $driver->mailerConfig($config),
                'mail.default' => 'integration',
            ]);

            $from = $driver->mailFrom($config);

            if (! empty($from['address'])) {
                config(['mail.from' => [
                    'address' => $from['address'],
                    'name' => $from['name'] ?: config('mail.from.name'),
                ]]);
            }
        } catch (Throwable) {
            // A broken integration must never take down boot; fall back to file config.
        }
    }

    /**
     * Swap Laravel's database session handler for ours, so the table is a ULID +
     * timestamps model. callCustomCreator wraps it in a Store, so cookie and
     * encryption config are untouched.
     */
    protected function useOurSessionTable(): void
    {
        SessionFacade::extend('database', function ($app) {
            return new SessionHandler(
                $app['db']->connection(config('session.connection')),
                config('session.table', 'sessions'),
                (int) config('session.lifetime', 120),
                $app,
            );
        });
    }

    /**
     * Who is acting: a session user, or an API key (VerifyAuth stashes the resolved
     * key on the request). Console and queue work has no causer.
     */
    protected function resolveActivityCauser(): void
    {
        CauserResolver::resolveUsing(function (Model|int|string|null $subject = null) {
            if ($subject instanceof Model) {
                return $subject;
            }

            if ($subject !== null) {
                return null;
            }

            $apiKey = request()->attributes->get('apiKey');

            return $apiKey instanceof ApiKey ? $apiKey : auth('sanctum')->user();
        });
    }
}
