<?php

namespace App\Providers;

use App\Models\ApiKey;
use App\Models\ApiKeyPermission;
use App\Models\Media;
use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\Session;
use App\Models\User;
use App\Models\UserRole;
use App\Observers\ApiKeyObserver;
use App\Observers\ApiKeyPermissionObserver;
use App\Observers\MediaObserver;
use App\Observers\PermissionObserver;
use App\Observers\RoleObserver;
use App\Observers\RolePermissionObserver;
use App\Observers\UserObserver;
use App\Observers\UserRoleObserver;
use App\Services\Sessions\SessionHandler;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Session as SessionFacade;
use Illuminate\Support\ServiceProvider;
use Spatie\Activitylog\Facades\CauserResolver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->resolveActivityCauser();
        $this->useOurSessionTable();

        User::observe(UserObserver::class);
        Role::observe(RoleObserver::class);
        Permission::observe(PermissionObserver::class);
        ApiKey::observe(ApiKeyObserver::class);
        Media::observe(MediaObserver::class);
        UserRole::observe(UserRoleObserver::class);
        RolePermission::observe(RolePermissionObserver::class);
        ApiKeyPermission::observe(ApiKeyPermissionObserver::class);
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
