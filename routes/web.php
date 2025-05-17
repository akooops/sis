<?php

use App\Http\Controllers\Admin\AlbumsController;
use App\Http\Controllers\Admin\ArticlesController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\FilesController;
use App\Http\Controllers\Admin\GradesController;
use App\Http\Controllers\Admin\LanguagesController;
use App\Http\Controllers\Admin\LanguagesKeysController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\PagesController;
use App\Http\Controllers\Admin\PermissionsController;
use App\Http\Controllers\Admin\ProgramsController;
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
    Route::patch('language-keys/{languageKey}/update-translation', [LanguagesKeysController::class, 'updateTranslation'])->middleware('check.permission:admin.language-keys.update')->name('admin.language-keys.update-translation');
    Route::patch('language-keys/{languageKey}', [LanguagesKeysController::class, 'update'])->middleware('check.permission:admin.language-keys.update')->name('admin.language-keys.update');
    Route::delete('language-keys/{languageKey}', [LanguagesKeysController::class, 'destroy'])->middleware('check.permission:admin.language-keys.destroy')->name('admin.language-keys.destroy');

    // Media
    Route::get('media', [MediaController::class, 'index'])->middleware('check.permission:admin.media.index')->name('admin.media.index');
    Route::get('media/create', [MediaController::class, 'create'])->middleware('check.permission:admin.media.store')->name('admin.media.create');
    Route::post('media', [MediaController::class, 'store'])->middleware('check.permission:admin.media.store')->name('admin.media.store');
    Route::get('media/{media}', [MediaController::class, 'show'])->middleware('check.permission:admin.media.show')->name('admin.media.show');
    Route::get('media/{media}/edit', [MediaController::class, 'edit'])->middleware('check.permission:admin.media.update')->name('admin.media.edit');
    Route::patch('media/{media}/update-translation', [MediaController::class, 'updateTranslation'])->middleware('check.permission:admin.media.update')->name('admin.media.update-translation');
    Route::patch('media/{media}', [MediaController::class, 'update'])->middleware('check.permission:admin.media.update')->name('admin.media.update');
    Route::delete('media/{media}', [MediaController::class, 'destroy'])->middleware('check.permission:admin.media.destroy')->name('admin.media.destroy');

    // Page
    Route::get('pages', [PagesController::class, 'index'])->middleware('check.permission:admin.pages.index')->name('admin.pages.index');
    Route::get('pages/create', [PagesController::class, 'create'])->middleware('check.permission:admin.pages.store')->name('admin.pages.create');
    Route::post('pages', [PagesController::class, 'store'])->middleware('check.permission:admin.pages.store')->name('admin.pages.store');
    Route::get('pages/{page}', [PagesController::class, 'show'])->middleware('check.permission:admin.pages.show')->name('admin.pages.show');
    Route::get('pages/{page}/edit', [PagesController::class, 'edit'])->middleware('check.permission:admin.pages.update')->name('admin.pages.edit');
    Route::patch('pages/{page}/update-translation', [PagesController::class, 'updateTranslation'])->middleware('check.permission:admin.pages.update')->name('admin.pages.update-translation');
    Route::patch('pages/{page}', [PagesController::class, 'update'])->middleware('check.permission:admin.pages.update')->name('admin.pages.update');
    Route::delete('pages/{page}', [PagesController::class, 'destroy'])->middleware('check.permission:admin.pages.destroy')->name('admin.pages.destroy');

    // Files
    Route::post('files/upload', [FilesController::class, 'upload'])->name('admin.files.upload');

    // Article
    Route::get('articles', [ArticlesController::class, 'index'])->middleware('check.permission:admin.articles.index')->name('admin.articles.index');
    Route::get('articles/create', [ArticlesController::class, 'create'])->middleware('check.permission:admin.articles.store')->name('admin.articles.create');
    Route::post('articles', [ArticlesController::class, 'store'])->middleware('check.permission:admin.articles.store')->name('admin.articles.store');
    Route::get('articles/{article}', [ArticlesController::class, 'show'])->middleware('check.permission:admin.articles.show')->name('admin.articles.show');
    Route::get('articles/{article}/edit', [ArticlesController::class, 'edit'])->middleware('check.permission:admin.articles.update')->name('admin.articles.edit');
    Route::patch('articles/{article}/update-translation', [ArticlesController::class, 'updateTranslation'])->middleware('check.permission:admin.articles.update')->name('admin.articles.update-translation');
    Route::patch('articles/{article}', [ArticlesController::class, 'update'])->middleware('check.permission:admin.articles.update')->name('admin.articles.update');
    Route::delete('articles/{article}', [ArticlesController::class, 'destroy'])->middleware('check.permission:admin.articles.destroy')->name('admin.articles.destroy');

    // Program
    Route::get('programs', [ProgramsController::class, 'index'])->middleware('check.permission:admin.programs.index')->name('admin.programs.index');
    Route::get('programs/create', [ProgramsController::class, 'create'])->middleware('check.permission:admin.programs.store')->name('admin.programs.create');
    Route::post('programs', [ProgramsController::class, 'store'])->middleware('check.permission:admin.programs.store')->name('admin.programs.store');
    Route::get('programs/{program}', [ProgramsController::class, 'show'])->middleware('check.permission:admin.programs.show')->name('admin.programs.show');
    Route::get('programs/{program}/edit', [ProgramsController::class, 'edit'])->middleware('check.permission:admin.programs.update')->name('admin.programs.edit');
    Route::patch('programs/{program}/update-translation', [ProgramsController::class, 'updateTranslation'])->middleware('check.permission:admin.programs.update')->name('admin.programs.update-translation');
    Route::patch('programs/{program}', [ProgramsController::class, 'update'])->middleware('check.permission:admin.programs.update')->name('admin.programs.update');
    Route::delete('programs/{program}', [ProgramsController::class, 'destroy'])->middleware('check.permission:admin.programs.destroy')->name('admin.programs.destroy');

    // Grades
    Route::get('grades', [GradesController::class, 'index'])->middleware('check.permission:admin.grades.index')->name('admin.grades.index');
    Route::get('grades/create', [GradesController::class, 'create'])->middleware('check.permission:admin.grades.store')->name('admin.grades.create');
    Route::post('grades', [GradesController::class, 'store'])->middleware('check.permission:admin.grades.store')->name('admin.grades.store');
    Route::get('grades/{grade}', [GradesController::class, 'show'])->middleware('check.permission:admin.grades.show')->name('admin.grades.show');
    Route::get('grades/{grade}/edit', [GradesController::class, 'edit'])->middleware('check.permission:admin.grades.update')->name('admin.grades.edit');
    Route::patch('grades/{grade}/update-translation', [GradesController::class, 'updateTranslation'])->middleware('check.permission:admin.grades.update')->name('admin.grades.update-translation');
    Route::patch('grades/{grade}', [GradesController::class, 'update'])->middleware('check.permission:admin.grades.update')->name('admin.grades.update');
    Route::delete('grades/{grade}', [GradesController::class, 'destroy'])->middleware('check.permission:admin.grades.destroy')->name('admin.grades.destroy');

    // Albums
    Route::get('albums', [AlbumsController::class, 'index'])->middleware('check.permission:admin.albums.index')->name('admin.albums.index');
    Route::get('albums/create', [AlbumsController::class, 'create'])->middleware('check.permission:admin.albums.store')->name('admin.albums.create');
    Route::post('albums', [AlbumsController::class, 'store'])->middleware('check.permission:admin.albums.store')->name('admin.albums.store');
    Route::get('albums/{album}', [AlbumsController::class, 'show'])->middleware('check.permission:admin.albums.show')->name('admin.albums.show');
    Route::get('albums/{album}/edit', [AlbumsController::class, 'edit'])->middleware('check.permission:admin.albums.update')->name('admin.albums.edit');
    Route::patch('albums/{album}/update-translation', [AlbumsController::class, 'updateTranslation'])->middleware('check.permission:admin.albums.update')->name('admin.albums.update-translation');
    Route::patch('albums/{album}', [AlbumsController::class, 'update'])->middleware('check.permission:admin.albums.update')->name('admin.albums.update');
    Route::delete('albums/{album}', [AlbumsController::class, 'destroy'])->middleware('check.permission:admin.albums.destroy')->name('admin.albums.destroy');
});
