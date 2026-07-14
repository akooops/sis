<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| These routes only render Inertia page shells; all data is fetched by the
| Svelte pages from the JSON API (/api/v1/...).
|
*/

/*------------------------
| Auth (guest) pages
|------------------------*/
Route::middleware('guest')->prefix('auth')->group(function () {
    Route::get('login', fn () => Inertia::render('Auth/Login'))
        ->name('web.auth.login-page');
});

/*------------------------
| Locale switch (available to guests + authed users)
|------------------------*/
Route::post('locale', function (\Illuminate\Http\Request $request) {
    $supported = (array) config('app.supported_locales', ['en']);
    $locale = $request->input('locale');
    if (in_array($locale, $supported, true)) {
        $request->session()->put('locale', $locale);
    }

    return back();
})->name('web.locale.set');

/*------------------------
| Admin (authenticated) pages — shells only; data comes from the JSON API
|------------------------*/
Route::middleware('auth')->prefix('admin')->group(function () {
    Route::get('users', fn () => Inertia::render('Admin/Users/Index'))->name('web.admin.users.index');
    Route::get('roles', fn () => Inertia::render('Admin/Roles/Index'))->name('web.admin.roles.index');
    Route::get('permissions', fn () => Inertia::render('Admin/Permissions/Index'))->name('web.admin.permissions.index');
    Route::get('api-keys', fn () => Inertia::render('Admin/ApiKeys/Index'))->name('web.admin.api-keys.index');
    Route::get('media', fn () => Inertia::render('Admin/Media/Index'))->name('web.admin.media.index');
});

/*------------------------
| Root
|------------------------*/
Route::get('/', fn () => auth()->check()
    ? Inertia::render('Home')
    : redirect()->route('web.auth.login-page'))->name('web.home');
