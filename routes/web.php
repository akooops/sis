<?php

use App\Http\Controllers\Web\Admin\AuthController;
use App\Http\Controllers\Web\Admin\PagesController as AdminPagesController;
use App\Http\Controllers\Web\PagesController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| These routes only render Inertia page shells; all data is fetched by the
| Svelte pages from the JSON API (/api/v1/admin/...). The rendering lives in
| App\Http\Controllers\Web, never inline here.
|
*/

/*------------------------
| Auth (guest) pages
|------------------------*/
Route::middleware('guest')->prefix('admin/auth')->group(function () {
    Route::get('login', [AuthController::class, 'login'])->name('web.admin.auth.login');
});

/*------------------------
| Admin (authenticated) pages
|------------------------*/
Route::middleware('auth')->prefix('admin')->group(function () {
    Route::get('/', [AdminPagesController::class, 'index'])->middleware('verify.permissions:dashboards.index')->name('web.admin.dashboard');
    Route::get('users', [AdminPagesController::class, 'users'])->middleware('verify.permissions:users.index')->name('web.admin.users.index');
    Route::get('roles', [AdminPagesController::class, 'roles'])->middleware('verify.permissions:roles.index')->name('web.admin.roles.index');
    Route::get('permissions', [AdminPagesController::class, 'permissions'])->middleware('verify.permissions:permissions.index')->name('web.admin.permissions.index');
    Route::get('api-keys', [AdminPagesController::class, 'apiKeys'])->middleware('verify.permissions:api-keys.index')->name('web.admin.api-keys.index');
    Route::get('media', [AdminPagesController::class, 'media'])->middleware('verify.permissions:media.index')->name('web.admin.media.index');
    Route::get('activities', [AdminPagesController::class, 'activities'])->middleware('verify.permissions:activities.index')->name('web.admin.activities.index');
    Route::get('integrations', [AdminPagesController::class, 'integrations'])->middleware('verify.permissions:integrations.index')->name('web.admin.integrations.index');
    Route::get('notifications', [AdminPagesController::class, 'notifications'])->name('web.admin.notifications.index');
    Route::get('notification-groups', [AdminPagesController::class, 'notificationGroups'])->middleware('verify.permissions:notification-groups.index')->name('web.admin.notification-groups.index');
    Route::get('languages', [AdminPagesController::class, 'languages'])->middleware('verify.permissions:languages.index')->name('web.admin.languages.index');
    Route::get('translations', [AdminPagesController::class, 'translations'])->middleware('verify.permissions:translations.index')->name('web.admin.translations.index');
    Route::get('pages', [AdminPagesController::class, 'pages'])->middleware('verify.permissions:pages.index')->name('web.admin.pages.index');
    Route::get('articles', [AdminPagesController::class, 'articles'])->middleware('verify.permissions:articles.index')->name('web.admin.articles.index');
    Route::get('albums', [AdminPagesController::class, 'albums'])->middleware('verify.permissions:albums.index')->name('web.admin.albums.index');
    Route::get('events', [AdminPagesController::class, 'events'])->middleware('verify.permissions:events.index')->name('web.admin.events.index');
    Route::get('categories', [AdminPagesController::class, 'categories'])->middleware('verify.permissions:categories.index')->name('web.admin.categories.index');
    Route::get('achievements', [AdminPagesController::class, 'achievements'])->middleware('verify.permissions:achievements.index')->name('web.admin.achievements.index');
});

/*------------------------
| Root
|------------------------*/
Route::get('/', [PagesController::class, 'index'])->name('web.index');
