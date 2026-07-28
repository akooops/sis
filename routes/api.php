<?php

use App\Http\Controllers\Api\Admin\AchievementsController;
use App\Http\Controllers\Api\Admin\ActivitiesController;
use App\Http\Controllers\Api\Admin\AlbumsController;
use App\Http\Controllers\Api\Admin\ApiKeyPermissionsController;
use App\Http\Controllers\Api\Admin\ApiKeysController;
use App\Http\Controllers\Api\Admin\ArticlesController;
use App\Http\Controllers\Api\Admin\AuthController;
use App\Http\Controllers\Api\Admin\BannersController;
use App\Http\Controllers\Api\Admin\CategoriesController;
use App\Http\Controllers\Api\Admin\CountriesController;
use App\Http\Controllers\Api\Admin\DocumentsController;
use App\Http\Controllers\Api\Admin\EventsController;
use App\Http\Controllers\Api\Admin\GradesController;
use App\Http\Controllers\Api\Admin\IntegrationsController;
use App\Http\Controllers\Api\Admin\IntegrationTypesController;
use App\Http\Controllers\Api\Admin\JobOffersController;
use App\Http\Controllers\Api\Admin\LanguagesController;
use App\Http\Controllers\Api\Admin\MediaController;
use App\Http\Controllers\Api\Admin\MenuItemsController;
use App\Http\Controllers\Api\Admin\MenusController;
use App\Http\Controllers\Api\Admin\NotificationGroupsController;
use App\Http\Controllers\Api\Admin\NotificationGroupUsersController;
use App\Http\Controllers\Api\Admin\NotificationsController;
use App\Http\Controllers\Api\Admin\NotificationTypesController;
use App\Http\Controllers\Api\Admin\PagesController;
use App\Http\Controllers\Api\Admin\PartnersController;
use App\Http\Controllers\Api\Admin\PermissionsController;
use App\Http\Controllers\Api\Admin\ProgramsController;
use App\Http\Controllers\Api\Admin\RolePermissionsController;
use App\Http\Controllers\Api\Admin\RolesController;
use App\Http\Controllers\Api\Admin\SessionsController;
use App\Http\Controllers\Api\Admin\StreamsController;
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
        Route::get('media', [MediaController::class, 'index'])->middleware('verify.permissions:media.index')->name('api.v1.admin.media.index');
        Route::patch('media/{media}/detach', [MediaController::class, 'detach'])->middleware('verify.permissions:media.detach')->name('api.v1.admin.media.detach');
        Route::delete('media/{media}', [MediaController::class, 'destroy'])->middleware('verify.permissions:media.destroy')->name('api.v1.admin.media.destroy');

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

        // Articles
        Route::get('articles', [ArticlesController::class, 'index'])->middleware('verify.permissions:articles.index')->name('api.v1.admin.articles.index');
        Route::get('articles/{article}', [ArticlesController::class, 'show'])->middleware('verify.permissions:articles.index')->name('api.v1.admin.articles.show');
        Route::post('articles', [ArticlesController::class, 'store'])->middleware('verify.permissions:articles.store')->name('api.v1.admin.articles.store');
        Route::put('articles/{article}', [ArticlesController::class, 'update'])->middleware('verify.permissions:articles.update')->name('api.v1.admin.articles.update');
        Route::delete('articles/{article}', [ArticlesController::class, 'destroy'])->middleware('verify.permissions:articles.destroy')->name('api.v1.admin.articles.destroy');

        // Albums
        Route::get('albums', [AlbumsController::class, 'index'])->middleware('verify.permissions:albums.index')->name('api.v1.admin.albums.index');
        Route::get('albums/{album}', [AlbumsController::class, 'show'])->middleware('verify.permissions:albums.index')->name('api.v1.admin.albums.show');
        Route::post('albums', [AlbumsController::class, 'store'])->middleware('verify.permissions:albums.store')->name('api.v1.admin.albums.store');
        Route::put('albums/{album}', [AlbumsController::class, 'update'])->middleware('verify.permissions:albums.update')->name('api.v1.admin.albums.update');
        Route::delete('albums/{album}', [AlbumsController::class, 'destroy'])->middleware('verify.permissions:albums.destroy')->name('api.v1.admin.albums.destroy');

        // Events
        Route::get('events', [EventsController::class, 'index'])->middleware('verify.permissions:events.index')->name('api.v1.admin.events.index');
        Route::get('events/{event}', [EventsController::class, 'show'])->middleware('verify.permissions:events.index')->name('api.v1.admin.events.show');
        Route::post('events', [EventsController::class, 'store'])->middleware('verify.permissions:events.store')->name('api.v1.admin.events.store');
        Route::put('events/{event}', [EventsController::class, 'update'])->middleware('verify.permissions:events.update')->name('api.v1.admin.events.update');
        Route::delete('events/{event}', [EventsController::class, 'destroy'])->middleware('verify.permissions:events.destroy')->name('api.v1.admin.events.destroy');

        // Categories
        Route::get('categories', [CategoriesController::class, 'index'])->middleware('verify.permissions:categories.index')->name('api.v1.admin.categories.index');
        Route::get('categories/{category}', [CategoriesController::class, 'show'])->middleware('verify.permissions:categories.index')->name('api.v1.admin.categories.show');
        Route::post('categories', [CategoriesController::class, 'store'])->middleware('verify.permissions:categories.store')->name('api.v1.admin.categories.store');
        Route::put('categories/{category}', [CategoriesController::class, 'update'])->middleware('verify.permissions:categories.update')->name('api.v1.admin.categories.update');
        Route::delete('categories/{category}', [CategoriesController::class, 'destroy'])->middleware('verify.permissions:categories.destroy')->name('api.v1.admin.categories.destroy');

        // Achievements
        Route::get('achievements', [AchievementsController::class, 'index'])->middleware('verify.permissions:achievements.index')->name('api.v1.admin.achievements.index');
        Route::get('achievements/{achievement}', [AchievementsController::class, 'show'])->middleware('verify.permissions:achievements.index')->name('api.v1.admin.achievements.show');
        Route::post('achievements', [AchievementsController::class, 'store'])->middleware('verify.permissions:achievements.store')->name('api.v1.admin.achievements.store');
        Route::put('achievements/{achievement}', [AchievementsController::class, 'update'])->middleware('verify.permissions:achievements.update')->name('api.v1.admin.achievements.update');
        Route::delete('achievements/{achievement}', [AchievementsController::class, 'destroy'])->middleware('verify.permissions:achievements.destroy')->name('api.v1.admin.achievements.destroy');

        // Partners
        Route::get('partners', [PartnersController::class, 'index'])->middleware('verify.permissions:partners.index')->name('api.v1.admin.partners.index');
        Route::get('partners/{partner}', [PartnersController::class, 'show'])->middleware('verify.permissions:partners.index')->name('api.v1.admin.partners.show');
        Route::post('partners', [PartnersController::class, 'store'])->middleware('verify.permissions:partners.store')->name('api.v1.admin.partners.store');
        Route::post('partners/reorder', [PartnersController::class, 'reorder'])->middleware('verify.permissions:partners.reorder')->name('api.v1.admin.partners.reorder');
        Route::put('partners/{partner}', [PartnersController::class, 'update'])->middleware('verify.permissions:partners.update')->name('api.v1.admin.partners.update');
        Route::delete('partners/{partner}', [PartnersController::class, 'destroy'])->middleware('verify.permissions:partners.destroy')->name('api.v1.admin.partners.destroy');

        // Documents
        Route::get('documents', [DocumentsController::class, 'index'])->middleware('verify.permissions:documents.index')->name('api.v1.admin.documents.index');
        Route::get('documents/{document}', [DocumentsController::class, 'show'])->middleware('verify.permissions:documents.index')->name('api.v1.admin.documents.show');
        Route::post('documents', [DocumentsController::class, 'store'])->middleware('verify.permissions:documents.store')->name('api.v1.admin.documents.store');
        Route::put('documents/{document}', [DocumentsController::class, 'update'])->middleware('verify.permissions:documents.update')->name('api.v1.admin.documents.update');
        Route::delete('documents/{document}', [DocumentsController::class, 'destroy'])->middleware('verify.permissions:documents.destroy')->name('api.v1.admin.documents.destroy');

        // Banners
        Route::get('banners', [BannersController::class, 'index'])->middleware('verify.permissions:banners.index')->name('api.v1.admin.banners.index');
        Route::get('banners/{banner}', [BannersController::class, 'show'])->middleware('verify.permissions:banners.index')->name('api.v1.admin.banners.show');
        Route::post('banners', [BannersController::class, 'store'])->middleware('verify.permissions:banners.store')->name('api.v1.admin.banners.store');
        // Declared before banners/{banner} so 'reorder' is not bound as an id.
        Route::post('banners/reorder', [BannersController::class, 'reorder'])->middleware('verify.permissions:banners.reorder')->name('api.v1.admin.banners.reorder');
        Route::put('banners/{banner}', [BannersController::class, 'update'])->middleware('verify.permissions:banners.update')->name('api.v1.admin.banners.update');
        Route::delete('banners/{banner}', [BannersController::class, 'destroy'])->middleware('verify.permissions:banners.destroy')->name('api.v1.admin.banners.destroy');

        // Menus
        Route::get('menus', [MenusController::class, 'index'])->middleware('verify.permissions:menus.index')->name('api.v1.admin.menus.index');
        Route::get('menus/{menu}', [MenusController::class, 'show'])->middleware('verify.permissions:menus.index')->name('api.v1.admin.menus.show');
        Route::post('menus', [MenusController::class, 'store'])->middleware('verify.permissions:menus.store')->name('api.v1.admin.menus.store');
        Route::put('menus/{menu}', [MenusController::class, 'update'])->middleware('verify.permissions:menus.update')->name('api.v1.admin.menus.update');
        Route::delete('menus/{menu}', [MenusController::class, 'destroy'])->middleware('verify.permissions:menus.destroy')->name('api.v1.admin.menus.destroy');
        // Nested read: the same index, with the menu pinned server-side.
        Route::get('menus/{menu}/items', [MenuItemsController::class, 'forMenu'])->middleware('verify.permissions:menu-items.index')->name('api.v1.admin.menus.items.index');

        // Menu items
        Route::get('menu-items', [MenuItemsController::class, 'index'])->middleware('verify.permissions:menu-items.index')->name('api.v1.admin.menu-items.index');
        Route::get('menu-items/{menu_item}', [MenuItemsController::class, 'show'])->middleware('verify.permissions:menu-items.index')->name('api.v1.admin.menu-items.show');
        Route::post('menu-items', [MenuItemsController::class, 'store'])->middleware('verify.permissions:menu-items.store')->name('api.v1.admin.menu-items.store');
        // Declared before menu-items/{menu_item} so 'reorder' is not bound as an id.
        Route::post('menu-items/reorder', [MenuItemsController::class, 'reorder'])->middleware('verify.permissions:menu-items.reorder')->name('api.v1.admin.menu-items.reorder');
        Route::put('menu-items/{menu_item}', [MenuItemsController::class, 'update'])->middleware('verify.permissions:menu-items.update')->name('api.v1.admin.menu-items.update');
        Route::delete('menu-items/{menu_item}', [MenuItemsController::class, 'destroy'])->middleware('verify.permissions:menu-items.destroy')->name('api.v1.admin.menu-items.destroy');

        // Programs
        Route::get('programs', [ProgramsController::class, 'index'])->middleware('verify.permissions:programs.index')->name('api.v1.admin.programs.index');
        Route::get('programs/{program}', [ProgramsController::class, 'show'])->middleware('verify.permissions:programs.index')->name('api.v1.admin.programs.show');
        Route::post('programs', [ProgramsController::class, 'store'])->middleware('verify.permissions:programs.store')->name('api.v1.admin.programs.store');
        Route::post('programs/reorder', [ProgramsController::class, 'reorder'])->middleware('verify.permissions:programs.reorder')->name('api.v1.admin.programs.reorder');
        Route::put('programs/{program}', [ProgramsController::class, 'update'])->middleware('verify.permissions:programs.update')->name('api.v1.admin.programs.update');
        Route::delete('programs/{program}', [ProgramsController::class, 'destroy'])->middleware('verify.permissions:programs.destroy')->name('api.v1.admin.programs.destroy');

        Route::get('programs/{program}/streams', [StreamsController::class, 'forProgram'])->middleware('verify.permissions:streams.index')->name('api.v1.admin.programs.streams.index');
        Route::get('programs/{program}/grades', [GradesController::class, 'forProgram'])->middleware('verify.permissions:grades.index')->name('api.v1.admin.programs.grades.index');

        // Streams
        Route::get('streams', [StreamsController::class, 'index'])->middleware('verify.permissions:streams.index')->name('api.v1.admin.streams.index');
        Route::get('streams/{stream}', [StreamsController::class, 'show'])->middleware('verify.permissions:streams.index')->name('api.v1.admin.streams.show');
        Route::post('streams', [StreamsController::class, 'store'])->middleware('verify.permissions:streams.store')->name('api.v1.admin.streams.store');
        Route::post('streams/reorder', [StreamsController::class, 'reorder'])->middleware('verify.permissions:streams.reorder')->name('api.v1.admin.streams.reorder');
        Route::put('streams/{stream}', [StreamsController::class, 'update'])->middleware('verify.permissions:streams.update')->name('api.v1.admin.streams.update');
        Route::delete('streams/{stream}', [StreamsController::class, 'destroy'])->middleware('verify.permissions:streams.destroy')->name('api.v1.admin.streams.destroy');

        // Grades
        Route::get('grades', [GradesController::class, 'index'])->middleware('verify.permissions:grades.index')->name('api.v1.admin.grades.index');
        Route::get('grades/{grade}', [GradesController::class, 'show'])->middleware('verify.permissions:grades.index')->name('api.v1.admin.grades.show');
        Route::post('grades', [GradesController::class, 'store'])->middleware('verify.permissions:grades.store')->name('api.v1.admin.grades.store');
        Route::post('grades/reorder', [GradesController::class, 'reorder'])->middleware('verify.permissions:grades.reorder')->name('api.v1.admin.grades.reorder');
        Route::put('grades/{grade}', [GradesController::class, 'update'])->middleware('verify.permissions:grades.update')->name('api.v1.admin.grades.update');
        Route::delete('grades/{grade}', [GradesController::class, 'destroy'])->middleware('verify.permissions:grades.destroy')->name('api.v1.admin.grades.destroy');

        // Job Offers
        Route::get('job-offers', [JobOffersController::class, 'index'])->middleware('verify.permissions:job-offers.index')->name('api.v1.admin.job-offers.index');
        Route::get('job-offers/{job_offer}', [JobOffersController::class, 'show'])->middleware('verify.permissions:job-offers.index')->name('api.v1.admin.job-offers.show');
        Route::post('job-offers', [JobOffersController::class, 'store'])->middleware('verify.permissions:job-offers.store')->name('api.v1.admin.job-offers.store');
        Route::put('job-offers/{job_offer}', [JobOffersController::class, 'update'])->middleware('verify.permissions:job-offers.update')->name('api.v1.admin.job-offers.update');
        Route::delete('job-offers/{job_offer}', [JobOffersController::class, 'destroy'])->middleware('verify.permissions:job-offers.destroy')->name('api.v1.admin.job-offers.destroy');

        // Countries
        Route::get('countries', [CountriesController::class, 'index'])->middleware('verify.permissions:countries.index')->name('api.v1.admin.countries.index');
        Route::get('countries/{country}', [CountriesController::class, 'show'])->middleware('verify.permissions:countries.index')->name('api.v1.admin.countries.show');
        Route::post('countries', [CountriesController::class, 'store'])->middleware('verify.permissions:countries.store')->name('api.v1.admin.countries.store');
        Route::put('countries/{country}', [CountriesController::class, 'update'])->middleware('verify.permissions:countries.update')->name('api.v1.admin.countries.update');
    });
});
