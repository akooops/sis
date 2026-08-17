<?php

use App\Http\Controllers\Api\Admin\AchievementsController;
use App\Http\Controllers\Api\Admin\ActivitiesController;
use App\Http\Controllers\Api\Admin\AlbumsController;
use App\Http\Controllers\Api\Admin\ApiKeyPermissionsController;
use App\Http\Controllers\Api\Admin\ApiKeysController;
use App\Http\Controllers\Api\Admin\ArticlesController;
use App\Http\Controllers\Api\Admin\AuthController;
use App\Http\Controllers\Api\Admin\BannersController;
use App\Http\Controllers\Api\Admin\BrandAssetGroupsController;
use App\Http\Controllers\Api\Admin\BrandAssetsController;
use App\Http\Controllers\Api\Admin\BrandsController;
use App\Http\Controllers\Api\Admin\CalendarsController;
use App\Http\Controllers\Api\Admin\CandidateClustersController;
use App\Http\Controllers\Api\Admin\CandidateMatchesController;
use App\Http\Controllers\Api\Admin\CandidatesController;
use App\Http\Controllers\Api\Admin\CategoriesController;
use App\Http\Controllers\Api\Admin\ClustersController;
use App\Http\Controllers\Api\Admin\ContactDetailsController;
use App\Http\Controllers\Api\Admin\ContactTypesController;
use App\Http\Controllers\Api\Admin\CountriesController;
use App\Http\Controllers\Api\Admin\DocumentsController;
use App\Http\Controllers\Api\Admin\EventsController;
use App\Http\Controllers\Api\Admin\FormAnalyticsController;
use App\Http\Controllers\Api\Admin\FormBlockedCountriesController;
use App\Http\Controllers\Api\Admin\FormBlockedIpsController;
use App\Http\Controllers\Api\Admin\FormBuilderController;
use App\Http\Controllers\Api\Admin\FormFieldTypesController;
use App\Http\Controllers\Api\Admin\FormNotificationGroupsController;
use App\Http\Controllers\Api\Admin\FormsController;
use App\Http\Controllers\Api\Admin\FormSubmissionsController;
use App\Http\Controllers\Api\Admin\FormWebhooksController;
use App\Http\Controllers\Api\Admin\GradesController;
use App\Http\Controllers\Api\Admin\IntegrationsController;
use App\Http\Controllers\Api\Admin\IntegrationTypesController;
use App\Http\Controllers\Api\Admin\JobApplicationsController;
use App\Http\Controllers\Api\Admin\JobOfferClustersController;
use App\Http\Controllers\Api\Admin\JobOffersController;
use App\Http\Controllers\Api\Admin\LanguagesController;
use App\Http\Controllers\Api\Admin\MediaController;
use App\Http\Controllers\Api\Admin\MenuItemsController;
use App\Http\Controllers\Api\Admin\MenusController;
use App\Http\Controllers\Api\Admin\NewsletterGroupsController;
use App\Http\Controllers\Api\Admin\NewsletterGroupSubscribersController;
use App\Http\Controllers\Api\Admin\NewslettersController;
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
use App\Http\Controllers\Api\Admin\SettingsController;
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

        // Settings. Seeded from config/settings.php, so there is no store and no
        // destroy — an admin edits a value and nothing else.
        Route::get('settings', [SettingsController::class, 'index'])->middleware('verify.permissions:settings.index')->name('api.v1.admin.settings.index');
        // Declared before settings/{setting} so 'groups' is not bound as an id.
        Route::get('settings/groups', [SettingsController::class, 'groups'])->middleware('verify.permissions:settings.index')->name('api.v1.admin.settings.groups');
        Route::get('settings/{setting}', [SettingsController::class, 'show'])->middleware('verify.permissions:settings.index')->name('api.v1.admin.settings.show');
        Route::put('settings/{setting}', [SettingsController::class, 'update'])->middleware('verify.permissions:settings.update')->name('api.v1.admin.settings.update');

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

        // Brands
        Route::get('brands', [BrandsController::class, 'index'])->middleware('verify.permissions:brands.index')->name('api.v1.admin.brands.index');
        Route::get('brands/{brand}', [BrandsController::class, 'show'])->middleware('verify.permissions:brands.index')->name('api.v1.admin.brands.show');
        Route::post('brands', [BrandsController::class, 'store'])->middleware('verify.permissions:brands.store')->name('api.v1.admin.brands.store');
        Route::put('brands/{brand}', [BrandsController::class, 'update'])->middleware('verify.permissions:brands.update')->name('api.v1.admin.brands.update');
        Route::delete('brands/{brand}', [BrandsController::class, 'destroy'])->middleware('verify.permissions:brands.destroy')->name('api.v1.admin.brands.destroy');
        // Nested read: the same index, with the brand pinned server-side.
        Route::get('brands/{brand}/asset-groups', [BrandAssetGroupsController::class, 'forBrand'])->middleware('verify.permissions:brand-asset-groups.index')->name('api.v1.admin.brands.asset-groups.index');

        // Brand asset groups
        Route::get('brand-asset-groups', [BrandAssetGroupsController::class, 'index'])->middleware('verify.permissions:brand-asset-groups.index')->name('api.v1.admin.brand-asset-groups.index');
        Route::get('brand-asset-groups/{brandAssetGroup}', [BrandAssetGroupsController::class, 'show'])->middleware('verify.permissions:brand-asset-groups.index')->name('api.v1.admin.brand-asset-groups.show');
        Route::post('brand-asset-groups', [BrandAssetGroupsController::class, 'store'])->middleware('verify.permissions:brand-asset-groups.store')->name('api.v1.admin.brand-asset-groups.store');
        // Declared before brand-asset-groups/{brandAssetGroup} so 'reorder' is not bound as an id.
        Route::post('brand-asset-groups/reorder', [BrandAssetGroupsController::class, 'reorder'])->middleware('verify.permissions:brand-asset-groups.reorder')->name('api.v1.admin.brand-asset-groups.reorder');
        Route::put('brand-asset-groups/{brandAssetGroup}', [BrandAssetGroupsController::class, 'update'])->middleware('verify.permissions:brand-asset-groups.update')->name('api.v1.admin.brand-asset-groups.update');
        Route::delete('brand-asset-groups/{brandAssetGroup}', [BrandAssetGroupsController::class, 'destroy'])->middleware('verify.permissions:brand-asset-groups.destroy')->name('api.v1.admin.brand-asset-groups.destroy');
        // Nested read: the same index, with the group pinned server-side.
        Route::get('brand-asset-groups/{brandAssetGroup}/assets', [BrandAssetsController::class, 'forGroup'])->middleware('verify.permissions:brand-assets.index')->name('api.v1.admin.brand-asset-groups.assets.index');

        // Brand assets
        Route::get('brand-assets', [BrandAssetsController::class, 'index'])->middleware('verify.permissions:brand-assets.index')->name('api.v1.admin.brand-assets.index');
        Route::get('brand-assets/{brandAsset}', [BrandAssetsController::class, 'show'])->middleware('verify.permissions:brand-assets.index')->name('api.v1.admin.brand-assets.show');
        Route::post('brand-assets', [BrandAssetsController::class, 'store'])->middleware('verify.permissions:brand-assets.store')->name('api.v1.admin.brand-assets.store');
        // Declared before brand-assets/{brandAsset} so 'reorder' is not bound as an id.
        Route::post('brand-assets/reorder', [BrandAssetsController::class, 'reorder'])->middleware('verify.permissions:brand-assets.reorder')->name('api.v1.admin.brand-assets.reorder');
        Route::put('brand-assets/{brandAsset}', [BrandAssetsController::class, 'update'])->middleware('verify.permissions:brand-assets.update')->name('api.v1.admin.brand-assets.update');
        Route::delete('brand-assets/{brandAsset}', [BrandAssetsController::class, 'destroy'])->middleware('verify.permissions:brand-assets.destroy')->name('api.v1.admin.brand-assets.destroy');

        // Contact details
        Route::get('contact-types', [ContactTypesController::class, 'index'])->middleware('verify.permissions:contact-details.index')->name('api.v1.admin.contact-types.index');

        Route::get('contact-details', [ContactDetailsController::class, 'index'])->middleware('verify.permissions:contact-details.index')->name('api.v1.admin.contact-details.index');
        Route::get('contact-details/{contactDetail}', [ContactDetailsController::class, 'show'])->middleware('verify.permissions:contact-details.index')->name('api.v1.admin.contact-details.show');
        Route::post('contact-details', [ContactDetailsController::class, 'store'])->middleware('verify.permissions:contact-details.store')->name('api.v1.admin.contact-details.store');
        // Declared before contact-details/{contactDetail} so 'reorder' is not bound as an id.
        Route::post('contact-details/reorder', [ContactDetailsController::class, 'reorder'])->middleware('verify.permissions:contact-details.reorder')->name('api.v1.admin.contact-details.reorder');
        Route::put('contact-details/{contactDetail}', [ContactDetailsController::class, 'update'])->middleware('verify.permissions:contact-details.update')->name('api.v1.admin.contact-details.update');
        Route::delete('contact-details/{contactDetail}', [ContactDetailsController::class, 'destroy'])->middleware('verify.permissions:contact-details.destroy')->name('api.v1.admin.contact-details.destroy');

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

        // Calendars
        Route::get('calendars', [CalendarsController::class, 'index'])->middleware('verify.permissions:calendars.index')->name('api.v1.admin.calendars.index');
        Route::get('calendars/{calendar}', [CalendarsController::class, 'show'])->middleware('verify.permissions:calendars.index')->name('api.v1.admin.calendars.show');
        Route::post('calendars', [CalendarsController::class, 'store'])->middleware('verify.permissions:calendars.store')->name('api.v1.admin.calendars.store');
        Route::put('calendars/{calendar}', [CalendarsController::class, 'update'])->middleware('verify.permissions:calendars.update')->name('api.v1.admin.calendars.update');
        Route::delete('calendars/{calendar}', [CalendarsController::class, 'destroy'])->middleware('verify.permissions:calendars.destroy')->name('api.v1.admin.calendars.destroy');

        // Newsletter Groups
        Route::get('newsletter-groups', [NewsletterGroupsController::class, 'index'])->middleware('verify.permissions:newsletter-groups.index')->name('api.v1.admin.newsletter-groups.index');
        Route::get('newsletter-groups/{newsletterGroup}', [NewsletterGroupsController::class, 'show'])->middleware('verify.permissions:newsletter-groups.index')->name('api.v1.admin.newsletter-groups.show');
        Route::post('newsletter-groups', [NewsletterGroupsController::class, 'store'])->middleware('verify.permissions:newsletter-groups.store')->name('api.v1.admin.newsletter-groups.store');
        Route::put('newsletter-groups/{newsletterGroup}', [NewsletterGroupsController::class, 'update'])->middleware('verify.permissions:newsletter-groups.update')->name('api.v1.admin.newsletter-groups.update');
        Route::delete('newsletter-groups/{newsletterGroup}', [NewsletterGroupsController::class, 'destroy'])->middleware('verify.permissions:newsletter-groups.destroy')->name('api.v1.admin.newsletter-groups.destroy');
        // Nested read: the same index, with the group pinned server-side.
        Route::get('newsletter-groups/{newsletterGroup}/subscribers', [NewsletterGroupSubscribersController::class, 'forGroup'])->middleware('verify.permissions:newsletter-group-subscribers.index')->name('api.v1.admin.newsletter-groups.subscribers.index');

        // Newsletter Group Subscribers
        Route::get('newsletter-group-subscribers', [NewsletterGroupSubscribersController::class, 'index'])->middleware('verify.permissions:newsletter-group-subscribers.index')->name('api.v1.admin.newsletter-group-subscribers.index');
        Route::get('newsletter-group-subscribers/{newsletterGroupSubscriber}', [NewsletterGroupSubscribersController::class, 'show'])->middleware('verify.permissions:newsletter-group-subscribers.index')->name('api.v1.admin.newsletter-group-subscribers.show');
        Route::post('newsletter-group-subscribers', [NewsletterGroupSubscribersController::class, 'store'])->middleware('verify.permissions:newsletter-group-subscribers.store')->name('api.v1.admin.newsletter-group-subscribers.store');
        Route::put('newsletter-group-subscribers/{newsletterGroupSubscriber}', [NewsletterGroupSubscribersController::class, 'update'])->middleware('verify.permissions:newsletter-group-subscribers.update')->name('api.v1.admin.newsletter-group-subscribers.update');
        Route::delete('newsletter-group-subscribers/{newsletterGroupSubscriber}', [NewsletterGroupSubscribersController::class, 'destroy'])->middleware('verify.permissions:newsletter-group-subscribers.destroy')->name('api.v1.admin.newsletter-group-subscribers.destroy');

        // Newsletters
        Route::get('newsletters', [NewslettersController::class, 'index'])->middleware('verify.permissions:newsletters.index')->name('api.v1.admin.newsletters.index');
        Route::get('newsletters/{newsletter}', [NewslettersController::class, 'show'])->middleware('verify.permissions:newsletters.index')->name('api.v1.admin.newsletters.show');
        Route::post('newsletters', [NewslettersController::class, 'store'])->middleware('verify.permissions:newsletters.store')->name('api.v1.admin.newsletters.store');
        Route::put('newsletters/{newsletter}', [NewslettersController::class, 'update'])->middleware('verify.permissions:newsletters.update')->name('api.v1.admin.newsletters.update');
        Route::delete('newsletters/{newsletter}', [NewslettersController::class, 'destroy'])->middleware('verify.permissions:newsletters.destroy')->name('api.v1.admin.newsletters.destroy');

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
        Route::get('menu-items/{menuItem}', [MenuItemsController::class, 'show'])->middleware('verify.permissions:menu-items.index')->name('api.v1.admin.menu-items.show');
        Route::post('menu-items', [MenuItemsController::class, 'store'])->middleware('verify.permissions:menu-items.store')->name('api.v1.admin.menu-items.store');
        // Declared before menu-items/{menuItem} so 'reorder' is not bound as an id.
        Route::post('menu-items/reorder', [MenuItemsController::class, 'reorder'])->middleware('verify.permissions:menu-items.reorder')->name('api.v1.admin.menu-items.reorder');
        Route::put('menu-items/{menuItem}', [MenuItemsController::class, 'update'])->middleware('verify.permissions:menu-items.update')->name('api.v1.admin.menu-items.update');
        Route::delete('menu-items/{menuItem}', [MenuItemsController::class, 'destroy'])->middleware('verify.permissions:menu-items.destroy')->name('api.v1.admin.menu-items.destroy');

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
        Route::get('job-offers/{jobOffer}', [JobOffersController::class, 'show'])->middleware('verify.permissions:job-offers.index')->name('api.v1.admin.job-offers.show');
        Route::post('job-offers', [JobOffersController::class, 'store'])->middleware('verify.permissions:job-offers.store')->name('api.v1.admin.job-offers.store');
        Route::put('job-offers/{jobOffer}', [JobOffersController::class, 'update'])->middleware('verify.permissions:job-offers.update')->name('api.v1.admin.job-offers.update');
        Route::delete('job-offers/{jobOffer}', [JobOffersController::class, 'destroy'])->middleware('verify.permissions:job-offers.destroy')->name('api.v1.admin.job-offers.destroy');

        /*
         * Job applications — READ-ONLY plus five explicit transitions. No store
         * and no update: the submit pipeline is the only writer, and the only
         * thing an admin changes is where an application has reached.
         *
         * `export` MUST stay above `{jobApplication}`, or it binds as a ULID and
         * every export 404s. Same discipline as form-submissions/{form}/export.
         */
        Route::get('job-applications/export', [JobApplicationsController::class, 'export'])->middleware('verify.permissions:job-applications.export')->name('api.v1.admin.job-applications.export');
        Route::get('job-applications', [JobApplicationsController::class, 'index'])->middleware('verify.permissions:job-applications.index')->name('api.v1.admin.job-applications.index');
        Route::get('job-applications/{jobApplication}', [JobApplicationsController::class, 'show'])->middleware('verify.permissions:job-applications.show')->name('api.v1.admin.job-applications.show');
        Route::post('job-applications/{jobApplication}/shortlist', [JobApplicationsController::class, 'shortlist'])->middleware('verify.permissions:job-applications.shortlist')->name('api.v1.admin.job-applications.shortlist');
        Route::post('job-applications/{jobApplication}/contact', [JobApplicationsController::class, 'contact'])->middleware('verify.permissions:job-applications.contact')->name('api.v1.admin.job-applications.contact');
        Route::post('job-applications/{jobApplication}/call', [JobApplicationsController::class, 'call'])->middleware('verify.permissions:job-applications.call')->name('api.v1.admin.job-applications.call');
        Route::post('job-applications/{jobApplication}/hire', [JobApplicationsController::class, 'hire'])->middleware('verify.permissions:job-applications.hire')->name('api.v1.admin.job-applications.hire');
        Route::post('job-applications/{jobApplication}/reject', [JobApplicationsController::class, 'reject'])->middleware('verify.permissions:job-applications.reject')->name('api.v1.admin.job-applications.reject');
        Route::delete('job-applications/{jobApplication}', [JobApplicationsController::class, 'destroy'])->middleware('verify.permissions:job-applications.destroy')->name('api.v1.admin.job-applications.destroy');

        /*
         * Candidates — the PEOPLE. No store: ApplicationProjector is the only
         * writer, and there is no candidates.store permission.
         *
         * The CV route MUST stay above candidates/{candidate}, or `{candidate}`
         * would swallow nothing here but the ordering habit is what keeps the
         * export line below correct. It is signed AND permission-gated: an
         * expired-signature check is not authorisation, and candidates.cv is
         * separate from candidates.show because the CV is personal data and
         * triaging a list should not require pulling files.
         */
        Route::get('candidates', [CandidatesController::class, 'index'])->middleware('verify.permissions:candidates.index')->name('api.v1.admin.candidates.index');
        Route::get('candidates/{candidate}/cv/{media}', [CandidatesController::class, 'cv'])->middleware(['signed', 'verify.permissions:candidates.cv'])->name('api.v1.admin.candidates.cv');
        Route::get('candidates/{candidate}', [CandidatesController::class, 'show'])->middleware('verify.permissions:candidates.show')->name('api.v1.admin.candidates.show');
        Route::put('candidates/{candidate}', [CandidatesController::class, 'update'])->middleware('verify.permissions:candidates.update')->name('api.v1.admin.candidates.update');
        Route::delete('candidates/{candidate}', [CandidatesController::class, 'destroy'])->middleware('verify.permissions:candidates.destroy')->name('api.v1.admin.candidates.destroy');

        // Candidate matches — ONE scoring table, read from both ends. Index only:
        // a match has no page, it fills the candidate and job offer drawers.
        Route::get('candidate-matches', [CandidateMatchesController::class, 'index'])->middleware('verify.permissions:candidate-matches.index')->name('api.v1.admin.candidate-matches.index');

        /*
         * Talent pools. No store: they are discovered by the nightly rebuild, and
         * a hand-made pool would have no centroid to gather anyone with.
         * `show` is gated on clusters.index, as job-offers.show is on its index.
         */
        Route::get('clusters', [ClustersController::class, 'index'])->middleware('verify.permissions:clusters.index')->name('api.v1.admin.clusters.index');
        Route::get('clusters/{cluster}', [ClustersController::class, 'show'])->middleware('verify.permissions:clusters.index')->name('api.v1.admin.clusters.show');
        Route::put('clusters/{cluster}', [ClustersController::class, 'update'])->middleware('verify.permissions:clusters.update')->name('api.v1.admin.clusters.update');
        Route::delete('clusters/{cluster}', [ClustersController::class, 'destroy'])->middleware('verify.permissions:clusters.destroy')->name('api.v1.admin.clusters.destroy');

        /*
         * Pool membership: READ AND REMOVE, no store.
         *
         * RebuildClusters deletes every membership row before reassigning, so an
         * add endpoint would be a feature that undoes itself at 03:00. The two
         * `*.store` permissions stay seeded and inert until that job learns to
         * preserve hand-made rows.
         */
        Route::get('candidate-clusters/{cluster}', [CandidateClustersController::class, 'index'])->middleware('verify.permissions:candidate-clusters.index')->name('api.v1.admin.candidate-clusters.index');
        Route::delete('candidate-clusters/{candidateCluster}', [CandidateClustersController::class, 'destroy'])->middleware('verify.permissions:candidate-clusters.destroy')->name('api.v1.admin.candidate-clusters.destroy');
        Route::get('job-offer-clusters/{cluster}', [JobOfferClustersController::class, 'index'])->middleware('verify.permissions:job-offer-clusters.index')->name('api.v1.admin.job-offer-clusters.index');
        Route::delete('job-offer-clusters/{jobOfferCluster}', [JobOfferClustersController::class, 'destroy'])->middleware('verify.permissions:job-offer-clusters.destroy')->name('api.v1.admin.job-offer-clusters.destroy');

        // Countries
        Route::get('countries', [CountriesController::class, 'index'])->middleware('verify.permissions:countries.index')->name('api.v1.admin.countries.index');
        Route::get('countries/{country}', [CountriesController::class, 'show'])->middleware('verify.permissions:countries.index')->name('api.v1.admin.countries.show');
        Route::post('countries', [CountriesController::class, 'store'])->middleware('verify.permissions:countries.store')->name('api.v1.admin.countries.store');
        Route::put('countries/{country}', [CountriesController::class, 'update'])->middleware('verify.permissions:countries.update')->name('api.v1.admin.countries.update');

        // Forms. 
        Route::get('form-field-types', [FormFieldTypesController::class, 'index'])->middleware('verify.permissions:form-fields.index')->name('api.v1.admin.form-field-types.index');
        Route::get('forms', [FormsController::class, 'index'])->middleware('verify.permissions:forms.index')->name('api.v1.admin.forms.index');
        Route::get('forms/{form}', [FormsController::class, 'show'])->middleware('verify.permissions:forms.show')->name('api.v1.admin.forms.show');
        Route::post('forms', [FormsController::class, 'store'])->middleware('verify.permissions:forms.store')->name('api.v1.admin.forms.store');
        Route::put('forms/{form}', [FormsController::class, 'update'])->middleware('verify.permissions:forms.update')->name('api.v1.admin.forms.update');
        Route::delete('forms/{form}', [FormsController::class, 'destroy'])->middleware('verify.permissions:forms.destroy')->name('api.v1.admin.forms.destroy');
        Route::get('forms/{form}/builder', [FormBuilderController::class, 'show'])->middleware('verify.permissions:forms.index')->name('api.v1.admin.forms.builder.show');
        Route::put('forms/{form}/builder', [FormBuilderController::class, 'update'])->middleware('verify.permissions:forms.update')->name('api.v1.admin.forms.builder.update');

        // Analytic
        Route::get('forms/{form}/analytics', [FormAnalyticsController::class, 'show'])->middleware('verify.permissions:forms.show')->name('api.v1.admin.forms.analytics');

        // Webhooks.
        Route::get('form-webhooks', [FormWebhooksController::class, 'index'])->middleware('verify.permissions:form-webhooks.index')->name('api.v1.admin.form-webhooks.index');
        Route::post('form-webhooks/{form}', [FormWebhooksController::class, 'store'])->middleware('verify.permissions:form-webhooks.store')->name('api.v1.admin.form-webhooks.store');
        Route::put('form-webhooks/{formWebhook}', [FormWebhooksController::class, 'update'])->middleware('verify.permissions:form-webhooks.update')->name('api.v1.admin.form-webhooks.update');
        Route::delete('form-webhooks/{formWebhook}', [FormWebhooksController::class, 'destroy'])->middleware('verify.permissions:form-webhooks.destroy')->name('api.v1.admin.form-webhooks.destroy');

        // Blocked addresses.
        Route::get('form-blocked-ips', [FormBlockedIpsController::class, 'index'])->middleware('verify.permissions:form-blocked-ips.index')->name('api.v1.admin.form-blocked-ips.index');
        Route::post('form-blocked-ips', [FormBlockedIpsController::class, 'store'])->middleware('verify.permissions:form-blocked-ips.store')->name('api.v1.admin.form-blocked-ips.store');
        Route::delete('form-blocked-ips/{formBlockedIp}', [FormBlockedIpsController::class, 'destroy'])->middleware('verify.permissions:form-blocked-ips.destroy')->name('api.v1.admin.form-blocked-ips.destroy');

        // Blocked countries.
        Route::get('form-blocked-countries/geo-status', [FormBlockedCountriesController::class, 'geoStatus'])->middleware('verify.permissions:form-blocked-countries.index')->name('api.v1.admin.form-blocked-countries.geo-status');
        Route::get('form-blocked-countries', [FormBlockedCountriesController::class, 'index'])->middleware('verify.permissions:form-blocked-countries.index')->name('api.v1.admin.form-blocked-countries.index');
        Route::post('form-blocked-countries', [FormBlockedCountriesController::class, 'store'])->middleware('verify.permissions:form-blocked-countries.store')->name('api.v1.admin.form-blocked-countries.store');
        Route::delete('form-blocked-countries/{formBlockedCountry}', [FormBlockedCountriesController::class, 'destroy'])->middleware('verify.permissions:form-blocked-countries.destroy')->name('api.v1.admin.form-blocked-countries.destroy');

        // Notification routing.
        Route::get('form-notification-groups', [FormNotificationGroupsController::class, 'index'])->middleware('verify.permissions:form-notification-groups.index')->name('api.v1.admin.form-notification-groups.index');
        Route::post('form-notification-groups', [FormNotificationGroupsController::class, 'store'])->middleware('verify.permissions:form-notification-groups.store')->name('api.v1.admin.form-notification-groups.store');
        Route::delete('form-notification-groups/{formNotificationGroup}', [FormNotificationGroupsController::class, 'destroy'])->middleware('verify.permissions:form-notification-groups.destroy')->name('api.v1.admin.form-notification-groups.destroy');

        // Submissions 
        Route::get('form-submissions', [FormSubmissionsController::class, 'index'])->middleware('verify.permissions:form-submissions.index')->name('api.v1.admin.form-submissions.index');
        Route::get('form-submissions/{form}/export', [FormSubmissionsController::class, 'export'])->middleware('verify.permissions:form-submissions.export')->name('api.v1.admin.form-submissions.export');
        Route::get('form-submissions/{formSubmission}/files/{media}', [FormSubmissionsController::class, 'file'])->middleware(['signed', 'verify.permissions:form-submissions.show'])->name('api.v1.admin.form-submissions.file');
        Route::get('form-submissions/{formSubmission}', [FormSubmissionsController::class, 'show'])->middleware('verify.permissions:form-submissions.show')->name('api.v1.admin.form-submissions.show');
    });
});
