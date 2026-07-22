<?php

use App\Http\Controllers\Api\Admin\ActivitiesController;
use App\Http\Controllers\Api\Admin\ApiKeyPermissionsController;
use App\Http\Controllers\Api\Admin\ApiKeysController;
use App\Http\Controllers\Api\Admin\AuthController;
use App\Http\Controllers\Api\Admin\GroupUsersController;
use App\Http\Controllers\Api\Admin\InboxController;
use App\Http\Controllers\Api\Admin\MediaController;
use App\Http\Controllers\Api\Admin\IntegrationsController;
use App\Http\Controllers\Api\Admin\IntegrationTypesController;
use App\Http\Controllers\Api\Admin\NotificationGroupsController;
use App\Http\Controllers\Api\Admin\NotificationsController;
use App\Http\Controllers\Api\Admin\NotificationTypesController;
use App\Http\Controllers\Api\Admin\PermissionsController;
use App\Http\Controllers\Api\Admin\RolePermissionsController;
use App\Http\Controllers\Api\Admin\RolesController;
use App\Http\Controllers\Api\Admin\SessionsController;
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

        // Notifications — inbox (auth-only: every admin has an inbox, no permission gate)
        Route::get('notifications/inbox', [InboxController::class, 'index'])->name('api.v1.admin.notifications.inbox');
        Route::get('notifications/inbox/unread-count', [InboxController::class, 'unreadCount'])->name('api.v1.admin.notifications.unread-count');
        Route::post('notifications/inbox/read-all', [InboxController::class, 'markAllRead'])->name('api.v1.admin.notifications.read-all');
        Route::patch('notifications/inbox/{notificationUser}/read', [InboxController::class, 'markRead'])->name('api.v1.admin.notifications.read');

        // Notification types (read-only reference for the pickers)
        Route::get('notification-types', [NotificationTypesController::class, 'index'])->middleware('verify.permissions:notifications.index')->name('api.v1.admin.notification-types.index');

        // Notifications — compose + sent history (preview/show registered before the {notification} wildcard)
        Route::get('notifications/preview', [NotificationsController::class, 'preview'])->middleware('verify.permissions:notifications.store')->name('api.v1.admin.notifications.preview');
        Route::get('notifications', [NotificationsController::class, 'index'])->middleware('verify.permissions:notifications.index')->name('api.v1.admin.notifications.index');
        Route::get('notifications/{notification}', [NotificationsController::class, 'show'])->middleware('verify.permissions:notifications.index')->name('api.v1.admin.notifications.show');
        Route::post('notifications', [NotificationsController::class, 'store'])->middleware('verify.permissions:notifications.store')->name('api.v1.admin.notifications.store');
        Route::delete('notifications/{notification}', [NotificationsController::class, 'destroy'])->middleware('verify.permissions:notifications.destroy')->name('api.v1.admin.notifications.destroy');

        // Notification Groups
        Route::get('notification-groups', [NotificationGroupsController::class, 'index'])->middleware('verify.permissions:notification-groups.index')->name('api.v1.admin.notification-groups.index');
        Route::get('notification-groups/{notificationGroup}/members', [GroupUsersController::class, 'index'])->middleware('verify.permissions:notification-groups.index')->name('api.v1.admin.notification-groups.members.index');
        Route::get('notification-groups/{notificationGroup}', [NotificationGroupsController::class, 'show'])->middleware('verify.permissions:notification-groups.index')->name('api.v1.admin.notification-groups.show');
        Route::post('notification-groups', [NotificationGroupsController::class, 'store'])->middleware('verify.permissions:notification-groups.store')->name('api.v1.admin.notification-groups.store');
        Route::put('notification-groups/{notificationGroup}', [NotificationGroupsController::class, 'update'])->middleware('verify.permissions:notification-groups.update')->name('api.v1.admin.notification-groups.update');
        Route::delete('notification-groups/{notificationGroup}', [NotificationGroupsController::class, 'destroy'])->middleware('verify.permissions:notification-groups.destroy')->name('api.v1.admin.notification-groups.destroy');

        // Group member delivery integrations
        Route::put('group-users/{groupUser}/integrations', [GroupUsersController::class, 'updateIntegrations'])->middleware('verify.permissions:notification-groups.update')->name('api.v1.admin.group-users.integrations');
    });
});
