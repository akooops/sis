<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\LanguagesController;
use App\Http\Controllers\Admin\LanguagesKeysController;
use App\Http\Controllers\Admin\PermissionsController;
use App\Http\Controllers\Admin\RolesController;
use App\Http\Controllers\Admin\UsersController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Admin Authentication Routes
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->group(function () {
    Route::get('auth/login', [AuthController::class, 'showLoginForm'])->name('admin.auth.login');
    Route::post('auth/login', [AuthController::class, 'login'])->name('admin.auth.login');
    
    // Azure AD authentication routes
    Route::get('auth/azure', [AuthController::class, 'redirectToAzure'])->name('admin.auth.azure');
    Route::get('auth/azure/callback', [AuthController::class, 'handleAzureCallback'])->name('admin.auth.azure.callback');
});

Route::middleware(['auth'])->prefix('admin')->group(function () {
    //Auth
    Route::post('auth/logout', [AuthController::class, 'logout'])->name('admin.auth.logout');

    // Permissions
    Route::get('permissions', [PermissionsController::class, 'index'])->middleware('check.permission:admin.permissions.index')->name('admin.permissions.index');
    Route::get('permissions/{permission}', [PermissionsController::class, 'show'])->middleware('check.permission:admin.permissions.show')->name('admin.permissions.show');

    // Roles
    Route::get('roles', [RolesController::class, 'index'])->middleware('check.permission:admin.roles.index')->name('admin.roles.index');
    Route::get('roles/create', [RolesController::class, 'create'])->middleware('check.permission:admin.roles.store')->name('admin.roles.create');
    Route::post('roles', [RolesController::class, 'store'])->middleware('check.permission:admin.roles.store')->name('admin.roles.store');
    Route::get('roles/{role}', [RolesController::class, 'show'])->middleware('check.permission:admin.roles.show')->name('admin.roles.show');
    Route::get('roles/{role}/edit', [RolesController::class, 'edit'])->middleware('check.permission:admin.roles.update')->name('admin.roles.edit');
    Route::patch('roles/{role}', [RolesController::class, 'update'])->middleware('check.permission:admin.roles.update')->name('admin.roles.update');
    Route::delete('roles/{role}', [RolesController::class, 'destroy'])->middleware('check.permission:admin.roles.destroy')->name('admin.roles.destroy');

    // Users
    Route::get('users', [UsersController::class, 'index'])->middleware('check.permission:admin.users.index')->name('admin.users.index');
    Route::get('users/create', [UsersController::class, 'create'])->middleware('check.permission:admin.users.store')->name('admin.users.create');
    Route::post('users', [UsersController::class, 'store'])->middleware('check.permission:admin.users.store')->name('admin.users.store');
    Route::get('users/{user}', [UsersController::class, 'show'])->middleware('check.permission:admin.users.show')->name('admin.users.show');
    Route::get('users/{user}/edit', [UsersController::class, 'edit'])->middleware('check.permission:admin.users.update')->name('admin.users.edit');
    Route::patch('users/{user}', [UsersController::class, 'update'])->middleware('check.permission:admin.users.update')->name('admin.users.update');
    Route::delete('users/{user}', [UsersController::class, 'destroy'])->middleware('check.permission:admin.users.destroy')->name('admin.users.destroy');

    // Languages
    Route::get('languages', [LanguagesController::class, 'index'])->middleware('check.permission:admin.languages.index')->name('admin.languages.index');
    Route::get('languages/create', [LanguagesController::class, 'create'])->middleware('check.permission:admin.languages.store')->name('admin.languages.create');
    Route::post('languages', [LanguagesController::class, 'store'])->middleware('check.permission:admin.languages.store')->name('admin.languages.store');
    Route::get('languages/{language}', [LanguagesController::class, 'show'])->middleware('check.permission:admin.languages.show')->name('admin.languages.show');
    Route::get('languages/{language}/edit', [LanguagesController::class, 'edit'])->middleware('check.permission:admin.languages.update')->name('admin.languages.edit');
    Route::patch('languages/{language}', [LanguagesController::class, 'update'])->middleware('check.permission:admin.languages.update')->name('admin.languages.update');
    Route::delete('languages/{language}', [LanguagesController::class, 'destroy'])->middleware('check.permission:admin.languages.destroy')->name('admin.languages.destroy');

    // Language keys
    Route::get('language-keys', [LanguagesKeysController::class, 'index'])->middleware('check.permission:admin.language-keys.index')->name('admin.language-keys.index');
    Route::get('language-keys/create', [LanguagesKeysController::class, 'create'])->middleware('check.permission:admin.language-keys.store')->name('admin.language-keys.create');
    Route::post('language-keys', [LanguagesKeysController::class, 'store'])->middleware('check.permission:admin.language-keys.store')->name('admin.language-keys.store');
    Route::get('language-keys/{languageKey}', [LanguagesKeysController::class, 'show'])->middleware('check.permission:admin.language-keys.show')->name('admin.language-keys.show');
    Route::get('language-keys/{languageKey}/edit', [LanguagesKeysController::class, 'edit'])->middleware('check.permission:admin.language-keys.update')->name('admin.language-keys.edit');
    Route::patch('language-keys/{languageKey}/update-translation', [LanguagesKeysController::class, 'updateTranslation'])->middleware('check.permission:admin.language-keys.update')->name('admin.language-keys.update');
    Route::patch('language-keys/{languageKey}', [LanguagesKeysController::class, 'update'])->middleware('check.permission:admin.language-keys.update')->name('admin.language-keys.update');
    Route::delete('language-keys/{languageKey}', [LanguagesKeysController::class, 'destroy'])->middleware('check.permission:admin.language-keys.destroy')->name('admin.language-keys.destroy');
});
