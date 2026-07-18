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
});

/*------------------------
| Root
|------------------------*/
Route::get('/', [PagesController::class, 'index'])->name('web.index');
