<?php

use App\Http\Controllers\Web\Admin\AuthController;
use App\Http\Controllers\Web\Admin\PagesController as AdminPagesController;
use App\Http\Controllers\Web\NewsletterController;
use App\Http\Controllers\Web\Site\AchievementsController;
use App\Http\Controllers\Web\Site\AlbumsController;
use App\Http\Controllers\Web\Site\ArticlesController;
use App\Http\Controllers\Web\Site\BrandsController;
use App\Http\Controllers\Web\Site\ContactController;
use App\Http\Controllers\Web\Site\EventsController;
use App\Http\Controllers\Web\Site\FormsController as SiteFormsController;
use App\Http\Controllers\Web\Site\HomeController;
use App\Http\Controllers\Web\Site\JobsController;
use App\Http\Controllers\Web\Site\PagesController;
use App\Http\Controllers\Web\Site\ProgramsController;
use App\Http\Controllers\Web\Site\ResourcesController;
use App\Http\Controllers\Web\Site\SitemapController;
use App\Http\Controllers\Web\Site\SubmitController;
use App\Http\Controllers\Web\Site\VisitsController;
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
    /*
     * `web.admin.job-applications.index` must keep exactly this name:
     * JobApplicationObserver::announce() already links every job notification to
     * it, and it swallows its own failures — so a rename does not error, it makes
     * every job notification vanish into the integrations log.
     */
    Route::get('job-applications', [AdminPagesController::class, 'jobApplications'])->middleware('verify.permissions:job-applications.index')->name('web.admin.job-applications.index');
    Route::get('candidates', [AdminPagesController::class, 'candidates'])->middleware('verify.permissions:candidates.index')->name('web.admin.candidates.index');
    Route::get('clusters', [AdminPagesController::class, 'clusters'])->middleware('verify.permissions:clusters.index')->name('web.admin.clusters.index');
    Route::get('visit-services', [AdminPagesController::class, 'visitServices'])->middleware('verify.permissions:visit-services.index')->name('web.admin.visit-services.index');
    Route::get('visit-slots', [AdminPagesController::class, 'visitSlots'])->middleware('verify.permissions:visit-slots.index')->name('web.admin.visit-slots.index');
    /*
     * `web.admin.visit-reservations.index` must keep exactly this name, for the
     * same reason its job-applications twin above must:
     * VisitReservationObserver::announce() links every visit notification to it and
     * swallows its own failures, so a rename does not error — it makes every visit
     * notification vanish into the integrations log.
     */
    Route::get('visit-reservations', [AdminPagesController::class, 'visitReservations'])->middleware('verify.permissions:visit-reservations.index')->name('web.admin.visit-reservations.index');
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
| SEO endpoints
|------------------------*/
Route::get('sitemap.xml', [SitemapController::class, 'index'])->name('web.site.sitemap');
Route::get('robots.txt', [SitemapController::class, 'robots'])->name('web.site.robots');

/*------------------------
| Public site - Newsletters
|------------------------*/
Route::get('newsletter/unsubscribe/{signature}', [NewsletterController::class, 'unsubscribe'])
    ->name('web.site.newsletter-groups.unsubscribe');

/*------------------------
| Public site - Pages
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
    Route::get('jobs/{slug}', [JobsController::class, 'show'])->name('jobs.show');

    /*
     * Visits: ONE page, plus the calendar's month feed.
     *
     * The feed is throttled because it is public and unauthenticated. It is
     * declared here rather than beside the form endpoints below so that it
     * inherits the same optional locale prefix as the page that calls it — the
     * browser builds its URL from the same route() that rendered the page.
     */
    Route::get('visits', [VisitsController::class, 'index'])->name('visits.index');
    Route::get('visits/{service}/slots', [VisitsController::class, 'slots'])
        ->middleware('throttle:60,1')
        ->name('visits.slots');

    Route::get('calendars', [ResourcesController::class, 'calendars'])->name('calendars');
    Route::get('newsletters', [ResourcesController::class, 'newsletters'])->name('newsletters');
    Route::get('guidelines', [ResourcesController::class, 'guidelines'])->name('guidelines');
    Route::get('documents', [ResourcesController::class, 'documents'])->name('documents');

    Route::get('contact', [ContactController::class, 'contact'])->name('contact');
    Route::get('inquiries', [ContactController::class, 'inquiries'])->name('inquiries');

    Route::get('forms/{slug}', [SiteFormsController::class, 'show'])->name('forms.show');

    Route::get('{slug}', [PagesController::class, 'show'])->name('pages.show');
};

Route::middleware('set.locale')->prefix('{locale}')->name('web.site.')->group($site);
Route::middleware('set.locale')->name('web.site.root.')->group($site);

/*------------------------
Forms
|------------------------*/
Route::post('forms/{slug}/uploads', [SubmitController::class, 'upload'])->middleware('throttle:form-uploads')->name('web.user.forms.upload');
Route::post('forms/{slug}/parse-cv', [SubmitController::class, 'parseCv'])->middleware('throttle:form-uploads')->name('web.user.forms.parse-cv');
Route::post('forms/{slug}/telemetry', [SubmitController::class, 'telemetry'])->middleware('throttle:form-telemetry')->name('web.user.forms.telemetry');

Route::middleware('set.locale')->group(function () {
    Route::post('{locale}/forms/{slug}', [SubmitController::class, 'submit'])->middleware('throttle:form-submits')->name('web.user.forms.submit');
    Route::post('forms/{slug}', [SubmitController::class, 'submit'])->middleware('throttle:form-submits')->name('web.user.forms.root.submit');
});
