<?php

use App\Http\Controllers\Api\Admin\ActivitiesController;
use App\Http\Controllers\Api\Admin\ApiKeyPermissionsController;
use App\Http\Controllers\Api\Admin\ApiKeysController;
use App\Http\Controllers\Api\Admin\AuthController;
use App\Http\Controllers\Api\Admin\MediaController;
use App\Http\Controllers\Api\Admin\IntegrationsController;
use App\Http\Controllers\Api\Admin\IntegrationTypesController;
use App\Http\Controllers\Api\Admin\LanguagesController;
use App\Http\Controllers\Api\Admin\NotificationGroupsController;
use App\Http\Controllers\Api\Admin\NotificationGroupUsersController;
use App\Http\Controllers\Api\Admin\NotificationsController;
use App\Http\Controllers\Api\Admin\NotificationTypesController;
use App\Http\Controllers\Api\Admin\PagesController;
use App\Http\Controllers\Api\Admin\PermissionsController;
use App\Http\Controllers\Api\Admin\RolePermissionsController;
use App\Http\Controllers\Api\Admin\RolesController;
use App\Http\Controllers\Api\Admin\SessionsController;
use App\Http\Controllers\Api\Admin\TranslationKeysController;
use App\Http\Controllers\Api\Admin\TranslationsController;
use App\Http\Controllers\Api\Admin\UserRolesController;
use App\Http\Controllers\Api\Admin\UsersController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1
|--------------------------------------------------------------------------
| Authenticated via a single X-API-KEY header or a Sanctum session (verify.auth).
| Admin routes additionally gate on a permission code (verify.permissions:*).
*/
Route::prefix('v1/admin/auth')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->name('api.v1.admin.auth.login');
    Route::get('azure/redirect', [AuthController::class, 'redirectToAzure'])->name('api.v1.admin.auth.azure.redirect');
    Route::get('azure/callback', [AuthController::class, 'handleAzureCallback'])->name('api.v1.admin.auth.azure.callback');

    Route::post('logout', [AuthController::class, 'logout'])->middleware('verify.auth')->name('api.v1.admin.auth.logout');
});

Route::prefix('v1')->middleware('verify.auth')->group(function () {

    Route::prefix('admin')->group(function () {
        // Media
        Route::post('media', [MediaController::class, 'store'])->name('api.v1.admin.media.store');

        // Users
        Route::get('users', [UsersController::class, 'index'])->middleware('verify.permissions:users.index')->name('api.v1.admin.users.index');
        Route::post('users', [UsersController::class, 'store'])->middleware('verify.permissions:users.store')->name('api.v1.admin.users.store');
        Route::get('users/{user}', [UsersController::class, 'show'])->middleware('verify.permissions:users.show')->name('api.v1.admin.users.show');
        Route::put('users/{user}', [UsersController::class, 'update'])->middleware('verify.permissions:users.update')->name('api.v1.admin.users.update');
        Route::delete('users/{user}', [UsersController::class, 'destroy'])->middleware('verify.permissions:users.destroy')->name('api.v1.admin.users.destroy');
        Route::post('users/{user}/approve', [UsersController::class, 'approve'])->middleware('verify.permissions:users.approve')->name('api.v1.admin.users.approve');
        Route::post('users/{user}/reject', [UsersController::class, 'reject'])->middleware('verify.permissions:users.reject')->name('api.v1.admin.users.reject');
        Route::post('users/{user}/verify', [UsersController::class, 'verify'])->middleware('verify.permissions:users.verify')->name('api.v1.admin.users.verify');
        Route::post('users/{user}/logout-devices', [UsersController::class, 'logoutDevices'])->middleware('verify.permissions:users.logout-devices')->name('api.v1.admin.users.logout-devices');

        // Sessions
        Route::get('sessions/{user}', [SessionsController::class, 'index'])->middleware('verify.permissions:sessions.index')->name('api.v1.admin.sessions.index');
        Route::delete('sessions/{session}', [SessionsController::class, 'destroy'])->middleware('verify.permissions:sessions.destroy')->name('api.v1.admin.sessions.destroy');

        // User Roles
        Route::get('user-roles/{user}', [UserRolesController::class, 'index'])->middleware('verify.permissions:user-roles.index')->name('api.v1.admin.user-roles.index');
        Route::post('user-roles/{user}', [UserRolesController::class, 'store'])->middleware('verify.permissions:user-roles.store')->name('api.v1.admin.user-roles.store');
        Route::delete('user-roles/{userRole}', [UserRolesController::class, 'destroy'])->middleware('verify.permissions:user-roles.destroy')->name('api.v1.admin.user-roles.destroy');

        // Roles
        Route::get('roles', [RolesController::class, 'index'])->middleware('verify.permissions:roles.index')->name('api.v1.admin.roles.index');
        Route::post('roles', [RolesController::class, 'store'])->middleware('verify.permissions:roles.store')->name('api.v1.admin.roles.store');
        Route::get('roles/{role}', [RolesController::class, 'show'])->middleware('verify.permissions:roles.show')->name('api.v1.admin.roles.show');
        Route::put('roles/{role}', [RolesController::class, 'update'])->middleware('verify.permissions:roles.update')->name('api.v1.admin.roles.update');
        Route::delete('roles/{role}', [RolesController::class, 'destroy'])->middleware('verify.permissions:roles.destroy')->name('api.v1.admin.roles.destroy');

        // Role Permissions
        Route::get('role-permissions/{role}', [RolePermissionsController::class, 'index'])->middleware('verify.permissions:role-permissions.index')->name('api.v1.admin.role-permissions.index');
        Route::post('role-permissions/{role}', [RolePermissionsController::class, 'store'])->middleware('verify.permissions:role-permissions.store')->name('api.v1.admin.role-permissions.store');
        Route::delete('role-permissions/{rolePermission}', [RolePermissionsController::class, 'destroy'])->middleware('verify.permissions:role-permissions.destroy')->name('api.v1.admin.role-permissions.destroy');

        // Permissions
        Route::get('permissions', [PermissionsController::class, 'index'])->middleware('verify.permissions:permissions.index')->name('api.v1.admin.permissions.index');
        Route::get('permissions/{permission}', [PermissionsController::class, 'show'])->middleware('verify.permissions:permissions.show')->name('api.v1.admin.permissions.show');

        // API Keys
        Route::get('api-keys', [ApiKeysController::class, 'index'])->middleware('verify.permissions:api-keys.index')->name('api.v1.admin.api-keys.index');
        Route::post('api-keys', [ApiKeysController::class, 'store'])->middleware('verify.permissions:api-keys.store')->name('api.v1.admin.api-keys.store');
        Route::get('api-keys/{apiKey}', [ApiKeysController::class, 'show'])->middleware('verify.permissions:api-keys.show')->name('api.v1.admin.api-keys.show');
        Route::put('api-keys/{apiKey}', [ApiKeysController::class, 'update'])->middleware('verify.permissions:api-keys.update')->name('api.v1.admin.api-keys.update');
        Route::delete('api-keys/{apiKey}', [ApiKeysController::class, 'destroy'])->middleware('verify.permissions:api-keys.destroy')->name('api.v1.admin.api-keys.destroy');
        Route::post('api-keys/{apiKey}/rotate', [ApiKeysController::class, 'rotate'])->middleware('verify.permissions:api-keys.rotate')->name('api.v1.admin.api-keys.rotate');
        Route::post('api-keys/{apiKey}/revoke', [ApiKeysController::class, 'revoke'])->middleware('verify.permissions:api-keys.revoke')->name('api.v1.admin.api-keys.revoke');

        // Media
        Route::get('media', [MediaController::class, 'index'])->middleware('verify.permissions:media.index')->name('api.v1.admin.media.index');
        Route::patch('media/{media}/detach', [MediaController::class, 'detach'])->middleware('verify.permissions:media.detach')->name('api.v1.admin.media.detach');

        // Activities 
        Route::get('activities', [ActivitiesController::class, 'index'])->middleware('verify.permissions:activities.index')->name('api.v1.admin.activities.index');

        // API Key Permissions
        Route::get('api-key-permissions/{apiKey}', [ApiKeyPermissionsController::class, 'index'])->middleware('verify.permissions:api-key-permissions.index')->name('api.v1.admin.api-key-permissions.index');
        Route::post('api-key-permissions/{apiKey}', [ApiKeyPermissionsController::class, 'store'])->middleware('verify.permissions:api-key-permissions.store')->name('api.v1.admin.api-key-permissions.store');
        Route::delete('api-key-permissions/{apiKeyPermission}', [ApiKeyPermissionsController::class, 'destroy'])->middleware('verify.permissions:api-key-permissions.destroy')->name('api.v1.admin.api-key-permissions.destroy');

        // Integrations
        Route::get('integration-types', [IntegrationTypesController::class, 'index'])->middleware('verify.permissions:integrations.index')->name('api.v1.admin.integration-types.index');
        Route::get('integration-types/{integrationType}/drivers', [IntegrationTypesController::class, 'drivers'])->middleware('verify.permissions:integrations.index')->name('api.v1.admin.integration-types.drivers');

        Route::get('integrations', [IntegrationsController::class, 'index'])->middleware('verify.permissions:integrations.index')->name('api.v1.admin.integrations.index');
        Route::get('integrations/{integration}', [IntegrationsController::class, 'show'])->middleware('verify.permissions:integrations.index')->name('api.v1.admin.integrations.show');
        Route::post('integrations', [IntegrationsController::class, 'store'])->middleware('verify.permissions:integrations.store')->name('api.v1.admin.integrations.store');
        Route::put('integrations/{integration}', [IntegrationsController::class, 'update'])->middleware('verify.permissions:integrations.update')->name('api.v1.admin.integrations.update');
        Route::delete('integrations/{integration}', [IntegrationsController::class, 'destroy'])->middleware('verify.permissions:integrations.destroy')->name('api.v1.admin.integrations.destroy');
        Route::patch('integrations/{integration}/toggle', [IntegrationsController::class, 'toggle'])->middleware('verify.permissions:integrations.update')->name('api.v1.admin.integrations.toggle');

        // Notifications 
        Route::get('notification', [NotificationsController::class, 'index'])->name('api.v1.admin.notifications.index');
        Route::get('notification/unread-count', [NotificationsController::class, 'unreadCount'])->name('api.v1.admin.notifications.unread-count');
        Route::post('notification/read-all', [NotificationsController::class, 'markAllRead'])->name('api.v1.admin.notifications.read-all');
        Route::patch('notification/{notification}/read', [NotificationsController::class, 'markRead'])->name('api.v1.admin.notifications.read');
        Route::delete('notification/{notification}', [NotificationsController::class, 'destroy'])->name('api.v1.admin.notifications.destroy');

        // Notification types
        Route::get('notification-types', [NotificationTypesController::class, 'index'])->middleware('verify.permissions:notification-groups.index')->name('api.v1.admin.notification-types.index');

        // Notification Groups
        Route::get('notification-groups', [NotificationGroupsController::class, 'index'])->middleware('verify.permissions:notification-groups.index')->name('api.v1.admin.notification-groups.index');
        Route::get('notification-groups/{notificationGroup}', [NotificationGroupsController::class, 'show'])->middleware('verify.permissions:notification-groups.index')->name('api.v1.admin.notification-groups.show');
        Route::post('notification-groups', [NotificationGroupsController::class, 'store'])->middleware('verify.permissions:notification-groups.store')->name('api.v1.admin.notification-groups.store');
        Route::put('notification-groups/{notificationGroup}', [NotificationGroupsController::class, 'update'])->middleware('verify.permissions:notification-groups.update')->name('api.v1.admin.notification-groups.update');
        Route::delete('notification-groups/{notificationGroup}', [NotificationGroupsController::class, 'destroy'])->middleware('verify.permissions:notification-groups.destroy')->name('api.v1.admin.notification-groups.destroy');

        // Group members
        Route::get('notification-group-users/{notificationGroup}', [NotificationGroupUsersController::class, 'index'])->middleware('verify.permissions:notification-group-users.index')->name('api.v1.admin.notification-group-users.index');
        Route::post('notification-group-users/{notificationGroup}', [NotificationGroupUsersController::class, 'store'])->middleware('verify.permissions:notification-group-users.store')->name('api.v1.admin.notification-group-users.store');
        Route::delete('notification-group-users/{notificationGroupUser}', [NotificationGroupUsersController::class, 'destroy'])->middleware('verify.permissions:notification-group-users.destroy')->name('api.v1.admin.notification-group-users.destroy');

        // Languages
        Route::get('languages', [LanguagesController::class, 'index'])->middleware('verify.permissions:languages.index')->name('api.v1.admin.languages.index');
        Route::get('languages/{language}', [LanguagesController::class, 'show'])->middleware('verify.permissions:languages.index')->name('api.v1.admin.languages.show');
        Route::post('languages', [LanguagesController::class, 'store'])->middleware('verify.permissions:languages.store')->name('api.v1.admin.languages.store');
        Route::put('languages/{language}', [LanguagesController::class, 'update'])->middleware('verify.permissions:languages.update')->name('api.v1.admin.languages.update');
        Route::delete('languages/{language}', [LanguagesController::class, 'destroy'])->middleware('verify.permissions:languages.destroy')->name('api.v1.admin.languages.destroy');

        // Translation keys
        Route::get('translation-keys', [TranslationKeysController::class, 'index'])->middleware('verify.permissions:translations.index')->name('api.v1.admin.translation-keys.index');
        Route::get('translation-key-groups', [TranslationKeysController::class, 'groups'])->middleware('verify.permissions:translations.index')->name('api.v1.admin.translation-key-groups.index');

        // Translations
        Route::get('translations/{language}', [TranslationsController::class, 'index'])->middleware('verify.permissions:translations.index')->name('api.v1.admin.translations.index');
        Route::put('translations/{language}/{translationKey}', [TranslationsController::class, 'update'])->middleware('verify.permissions:translations.update')->name('api.v1.admin.translations.update');

        // Pages
        Route::get('pages', [PagesController::class, 'index'])->middleware('verify.permissions:pages.index')->name('api.v1.admin.pages.index');
        Route::get('pages/{page}', [PagesController::class, 'show'])->middleware('verify.permissions:pages.index')->name('api.v1.admin.pages.show');
        Route::post('pages', [PagesController::class, 'store'])->middleware('verify.permissions:pages.store')->name('api.v1.admin.pages.store');
        Route::put('pages/{page}', [PagesController::class, 'update'])->middleware('verify.permissions:pages.update')->name('api.v1.admin.pages.update');
        Route::delete('pages/{page}', [PagesController::class, 'destroy'])->middleware('verify.permissions:pages.destroy')->name('api.v1.admin.pages.destroy');
    });
});
