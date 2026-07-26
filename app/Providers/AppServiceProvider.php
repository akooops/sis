<?php

namespace App\Providers;

use App\Contracts\Integrations\SendsMail;
use App\Models\Album;
use App\Models\ApiKey;
use App\Models\ApiKeyPermission;
use App\Models\Article;
use App\Models\Event;
use App\Models\Media;
use App\Models\Integration;
use App\Models\Language;
use App\Models\Notification;
use App\Models\Page;
use App\Models\NotificationGroup;
use App\Models\NotificationGroupNotificationType;
use App\Models\NotificationGroupUser;
use App\Models\NotificationUser;
use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\Session;
use App\Models\User;
use App\Models\UserRole;
use App\Observers\AlbumObserver;
use App\Observers\ApiKeyObserver;
use App\Observers\ApiKeyPermissionObserver;
use App\Observers\ArticleObserver;
use App\Observers\EventObserver;
use App\Observers\MediaObserver;
use App\Observers\IntegrationObserver;
use App\Observers\LanguageObserver;
use App\Observers\NotificationGroupNotificationTypeObserver;
use App\Observers\PageObserver;
use App\Observers\NotificationGroupObserver;
use App\Observers\NotificationGroupUserObserver;
use App\Observers\NotificationObserver;
use App\Observers\NotificationUserObserver;
use App\Observers\PermissionObserver;
use App\Observers\RoleObserver;
use App\Observers\RolePermissionObserver;
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
        // One registry instance per request — it caches the resolved driver map.
        $this->app->singleton(Registry::class);

        // Likewise one translation service per request — it memoises the lang
        // files it has read, so a page of keys costs one read per group.
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
    }

    /**
     * How a translatable model resolves a locale it has no value for.
     * spatie/laravel-translatable v6 ships no config file, so this is the only
     * place it can be set.
     *
     * fallbackAny is the right call for a CMS: a page translated only into Arabic
     * should still render something rather than an empty <title> when someone
     * asks for a locale nobody has filled in.
     *
     * Note this governs page CONTENT only. The UI string catalogue is unrelated —
     * that still lives in lang/*.php and is read by __()/@lang() (see CLAUDE.md).
     */
    protected function useTranslationFallback(): void
    {
        Translatable::fallback(
            fallbackLocale: config('app.fallback_locale'),
            fallbackAny: true,
        );
    }

    /**
     * If an email integration is configured and enabled, make it the default
     * mailer for the request. File config (config/mail.php) stays the fallback
     * when no DB integration exists, so existing Mail::send call-sites are
     * untouched.
     *
     * Single-tenant assumption: a boot-time override of the one default mailer is
     * safe here. A multi-tenant app would set this per-job instead, so one
     * tenant's secret can't leak into another job on the same worker.
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
            // A broken integration must never take down app boot — fall back to
            // file config silently.
        }
    }

    /**
     * Swap Laravel's database session handler for ours, so the sessions table
     * can be a ULID + timestamps model like everything else instead of PHP's
     * session id and a unix integer. SessionManager::callCustomCreator wraps the
     * handler in a Store, so cookie/encryption config is untouched.
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
     * Teach the activity log who is acting: a request may be authenticated by a
     * session user or by an API key (VerifyAuth stashes the resolved key on the
     * request), and console/queue work has no causer at all.
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
