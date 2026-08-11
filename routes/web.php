<?php

use App\Http\Controllers\Web\Admin\AuthController;
use App\Http\Controllers\Web\Admin\PagesController as AdminPagesController;
use App\Http\Controllers\Web\FormsController as PublicFormsController;
use App\Http\Controllers\Web\NewsletterController;
use App\Http\Controllers\Web\Site\AchievementsController;
use App\Http\Controllers\Web\Site\AlbumsController;
use App\Http\Controllers\Web\Site\ArticlesController;
use App\Http\Controllers\Web\Site\BrandsController;
use App\Http\Controllers\Web\Site\ContactController;
use App\Http\Controllers\Web\Site\EventsController;
use App\Http\Controllers\Web\Site\HomeController;
use App\Http\Controllers\Web\Site\JobsController;
use App\Http\Controllers\Web\Site\PagesController;
use App\Http\Controllers\Web\Site\ProgramsController;
use App\Http\Controllers\Web\Site\ResourcesController;
use App\Http\Controllers\Web\Site\SitemapController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Two audiences in one file.
|
| The ADMIN routes only render Inertia page shells; all data is fetched by the
| Svelte pages from the JSON API (/api/v1/admin/...). The rendering lives in
| App\Http\Controllers\Web\Admin, never inline here.
|
| The SITE routes are server-rendered Blade under App\Http\Controllers\Web\Site,
| every one of them behind a `{locale}` segment. See the site group at the
| bottom for why the ordering inside it is load-bearing.
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
    Route::get('settings', [AdminPagesController::class, 'settings'])->middleware('verify.permissions:settings.index')->name('web.admin.settings.index');
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
    Route::get('contact-details', [AdminPagesController::class, 'contactDetails'])->middleware('verify.permissions:contact-details.index')->name('web.admin.contact-details.index');
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
    /*
     * `set.locale` was added when the public site landed. The URIs are
     * deliberately unchanged — a form link already in the wild must keep
     * working — but these views now extend the real site layout, whose header
     * calls route('web.site.home'), and that throws "Missing required
     * parameter" without the URL::defaults the middleware sets.
     *
     * The controller keeps its own abort_unless guards. They are the contract;
     * this is presentation plumbing.
     */
    Route::middleware('set.locale')->group(function () {
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
    });

    // Bare slug: pick a locale the visitor can read and redirect. Declared last
    // so it never swallows the two-letter locale segment above, and OUTSIDE the
    // set.locale group because it has no {locale} to resolve.
    Route::get('{slug}', [PublicFormsController::class, 'redirectToLocale'])->name('web.user.forms.entry');
});

/*------------------------
| SEO endpoints
|------------------------*/

/*
 * BEFORE the site group. Its `{slug}` route matches any single segment, so
 * registered after these it would swallow `sitemap.xml` and `robots.txt` and
 * answer them with a Page lookup that 404s.
 *
 * Outside the locale prefix too: one sitemap for the whole site, with every
 * locale expressed as an <xhtml:link> alternate INSIDE it. robots.txt is a route
 * rather than the static public/robots.txt it replaces, because the Sitemap:
 * directive needs an absolute URL and only a route can interpolate
 * config('app.url') per environment — which is also why that static file had to
 * be deleted, the web server serving public/ first would have made this dead.
 */
Route::get('sitemap.xml', [SitemapController::class, 'index'])->name('web.site.sitemap');
Route::get('robots.txt', [SitemapController::class, 'robots'])->name('web.site.robots');

Route::get('newsletter/unsubscribe/{signature}', [NewsletterController::class, 'unsubscribe'])
    ->name('web.site.newsletter-groups.unsubscribe');

/*------------------------
| Public site
|------------------------*/
$site = function () {
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::get('/', [HomeController::class, 'index'])->name('home');

    Route::get('articles', [ArticlesController::class, 'index'])->name('articles.index');
    Route::get('articles/{slug}', [ArticlesController::class, 'show'])->name('articles.show');

    Route::get('albums', [AlbumsController::class, 'index'])->name('albums.index');
    Route::get('albums/{slug}', [AlbumsController::class, 'show'])->name('albums.show');

    Route::get('events', [EventsController::class, 'index'])->name('events.index');
    Route::get('events/{slug}', [EventsController::class, 'show'])->name('events.show');

    Route::get('achievements', [AchievementsController::class, 'index'])->name('achievements.index');
    Route::get('achievements/{slug}', [AchievementsController::class, 'show'])->name('achievements.show');

    Route::get('programs/{slug}', [ProgramsController::class, 'show'])->name('programs.show');

    Route::get('brands', [BrandsController::class, 'index'])->name('brands.index');
    Route::get('brands/{slug}', [BrandsController::class, 'show'])->name('brands.show');

    Route::get('jobs', [JobsController::class, 'index'])->name('jobs.index');
    Route::get('jobs/apply', [JobsController::class, 'apply'])->name('jobs.apply');
    Route::get('jobs/{slug}', [JobsController::class, 'show'])->name('jobs.show');

    Route::get('calendars', [ResourcesController::class, 'calendars'])->name('calendars');
    Route::get('newsletters', [ResourcesController::class, 'newsletters'])->name('newsletters');
    Route::get('guidelines', [ResourcesController::class, 'guidelines'])->name('guidelines');
    Route::get('documents', [ResourcesController::class, 'documents'])->name('documents');

    Route::get('contact', [ContactController::class, 'contact'])->name('contact');
    Route::get('inquiries', [ContactController::class, 'inquiries'])->name('inquiries');

    Route::get('{slug}', [PagesController::class, 'show'])->name('pages.show');
};

Route::middleware('set.locale')->prefix('{locale}')->name('web.site.')->group($site);
Route::middleware('set.locale')->name('web.site.root.')->group($site);