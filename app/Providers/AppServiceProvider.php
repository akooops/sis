<?php

namespace App\Providers;

use App\Contracts\Integrations\SendsMail;
use App\Models\Achievement;
use App\Models\Album;
use App\Models\ApiKey;
use App\Models\ApiKeyPermission;
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
use App\Models\Stream;
use App\Models\User;
use App\Models\UserRole;
use App\Observers\AchievementObserver;
use App\Observers\AlbumObserver;
use App\Observers\ApiKeyObserver;
use App\Observers\ApiKeyPermissionObserver;
use App\Observers\ArticleObserver;
use App\Observers\CategoryObserver;
use App\Observers\DocumentObserver;
use App\Observers\EventObserver;
use App\Observers\GradeObserver;
use App\Observers\IntegrationObserver;
use App\Observers\LanguageObserver;
use App\Observers\MediaObserver;
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
use App\Observers\StreamObserver;
use App\Observers\UserObserver;
use App\Observers\UserRoleObserver;
use App\Services\Integrations\Registry;
use App\Services\Sessions\SessionHandler;
use App\Services\Translations\TranslationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session as SessionFacade;
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

        // One per request: it memoises the lang files it reads.
        $this->app->singleton(TranslationService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
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
        Page::observe(PageObserver::class);
        Article::observe(ArticleObserver::class);
        Album::observe(AlbumObserver::class);
        Event::observe(EventObserver::class);
        Category::observe(CategoryObserver::class);
        Achievement::observe(AchievementObserver::class);
        Partner::observe(PartnerObserver::class);
        Document::observe(DocumentObserver::class);
        Program::observe(ProgramObserver::class);
        Stream::observe(StreamObserver::class);
        Grade::observe(GradeObserver::class);
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
