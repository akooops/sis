<?php

use App\Http\Controllers\Web\Admin\AuthController;
use App\Http\Controllers\Web\Admin\PagesController as AdminPagesController;
use App\Http\Controllers\Web\FormsController as PublicFormsController;
use App\Http\Controllers\Web\NewsletterController;
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
    Route::get('brands', [AdminPagesController::class, 'brands'])->middleware('verify.permissions:brands.index')->name('web.admin.brands.index');
    Route::get('brand-asset-groups', [AdminPagesController::class, 'brandAssetGroups'])->middleware('verify.permissions:brand-asset-groups.index')->name('web.admin.brand-asset-groups.index');
    Route::get('brand-assets', [AdminPagesController::class, 'brandAssets'])->middleware('verify.permissions:brand-assets.index')->name('web.admin.brand-assets.index');
    Route::get('events', [AdminPagesController::class, 'events'])->middleware('verify.permissions:events.index')->name('web.admin.events.index');
    Route::get('categories', [AdminPagesController::class, 'categories'])->middleware('verify.permissions:categories.index')->name('web.admin.categories.index');
    Route::get('achievements', [AdminPagesController::class, 'achievements'])->middleware('verify.permissions:achievements.index')->name('web.admin.achievements.index');
    Route::get('partners', [AdminPagesController::class, 'partners'])->middleware('verify.permissions:partners.index')->name('web.admin.partners.index');
    Route::get('newsletters', [AdminPagesController::class, 'newsletters'])->middleware('verify.permissions:newsletters.index')->name('web.admin.newsletters.index');
    Route::get('newsletter-groups', [AdminPagesController::class, 'newsletterGroups'])->middleware('verify.permissions:newsletter-groups.index')->name('web.admin.newsletter-groups.index');
    Route::get('newsletter-group-subscribers', [AdminPagesController::class, 'newsletterGroupSubscribers'])->middleware('verify.permissions:newsletter-group-subscribers.index')->name('web.admin.newsletter-group-subscribers.index');
    Route::get('calendars', [AdminPagesController::class, 'calendars'])->middleware('verify.permissions:calendars.index')->name('web.admin.calendars.index');
    Route::get('banners', [AdminPagesController::class, 'banners'])->middleware('verify.permissions:banners.index')->name('web.admin.banners.index');
    Route::get('documents', [AdminPagesController::class, 'documents'])->middleware('verify.permissions:documents.index')->name('web.admin.documents.index');
    Route::get('programs', [AdminPagesController::class, 'programs'])->middleware('verify.permissions:programs.index')->name('web.admin.programs.index');
    Route::get('streams', [AdminPagesController::class, 'streams'])->middleware('verify.permissions:streams.index')->name('web.admin.streams.index');
    Route::get('job-offers', [AdminPagesController::class, 'jobOffers'])->middleware('verify.permissions:job-offers.index')->name('web.admin.job-offers.index');
    Route::get('menus', [AdminPagesController::class, 'menus'])->middleware('verify.permissions:menus.index')->name('web.admin.menus.index');
    Route::get('menu-items', [AdminPagesController::class, 'menuItems'])->middleware('verify.permissions:menu-items.index')->name('web.admin.menu-items.index');
    Route::get('countries', [AdminPagesController::class, 'countries'])->middleware('verify.permissions:countries.index')->name('web.admin.countries.index');
    Route::get('grades', [AdminPagesController::class, 'grades'])->middleware('verify.permissions:grades.index')->name('web.admin.grades.index');
    Route::get('forms', [AdminPagesController::class, 'forms'])->middleware('verify.permissions:forms.index')->name('web.admin.forms.index');
    Route::get('form-submissions', [AdminPagesController::class, 'formSubmissions'])->middleware('verify.permissions:form-submissions.index')->name('web.admin.form-submissions.index');
    Route::get('forms/{form}/build', [AdminPagesController::class, 'formBuilder'])->middleware('verify.permissions:forms.index')->name('web.admin.forms.builder');
    Route::get('forms/{form}/analytics', [AdminPagesController::class, 'formAnalytics'])->middleware('verify.permissions:forms.show')->name('web.admin.forms.analytics');
});

/*------------------------
| Public (no auth — reached from an email)
|------------------------*/
Route::get('newsletter/unsubscribe/{signature}', [NewsletterController::class, 'unsubscribe'])->name('web.user.newsletter-groups.unsubscribe');

/*
 * Public forms.
 *
 * In the `web` group deliberately: a session-less group has no
 * ShareErrorsFromSession, so every validation failure on this non-JSON POST
 * would 500 with "Session store not set" rather than redirecting back.
 *
 * The locale is an explicit URL segment rather than content negotiation — a
 * public link has to render the same thing for everyone who opens it. The
 * `[a-z]{2}` constraint is compilable, so route:cache still works.
 */
Route::prefix('forms')->group(function () {
    Route::get('{locale}/{slug}', [PublicFormsController::class, 'show'])
        ->where('locale', '[a-z]{2}')->name('web.user.forms.show');

    Route::get('{locale}/{slug}/thanks', [PublicFormsController::class, 'thanks'])
        ->where('locale', '[a-z]{2}')->name('web.user.forms.thanks');

    Route::post('{locale}/{slug}', [PublicFormsController::class, 'submit'])
        ->where('locale', '[a-z]{2}')
        ->middleware('throttle:form-submits')
        ->name('web.user.forms.submit');

    // Files go up before the form is submitted: they have to survive a page
    // change and a virus scan, and the answer only carries the media id.
    Route::post('{locale}/{slug}/uploads', [PublicFormsController::class, 'upload'])
        ->where('locale', '[a-z]{2}')
        ->middleware('throttle:form-uploads')
        ->name('web.user.forms.upload');

    // The analytics beacon. EXCEPTED FROM CSRF in App\Http\Middleware\
    // VerifyCsrfToken, because navigator.sendBeacon cannot set a header — the
    // encrypted submission token authenticates it instead, and the throttle
    // keeps the cost of an unauthenticated POST bounded.
    Route::post('{locale}/{slug}/telemetry', [PublicFormsController::class, 'telemetry'])
        ->where('locale', '[a-z]{2}')
        ->middleware('throttle:form-telemetry')
        ->name('web.user.forms.telemetry');

    // Bare slug: pick a locale the visitor can read and redirect. Declared last
    // so it never swallows the two-letter locale segment above.
    Route::get('{slug}', [PublicFormsController::class, 'redirectToLocale'])->name('web.user.forms.entry');
});

/*------------------------
| Root
|------------------------*/
Route::get('/', [PagesController::class, 'index'])->name('web.index');
