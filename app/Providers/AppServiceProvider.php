<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

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
        Blade::if('haspermission', function ($permission) {
            if (!config('app.enable_permissions')) {
                return true;
            }
            
            $user = auth()->user();
            if (!$user) {
                return false;
            }
            
            return $user->hasPermission($permission);
        });
    }
}
