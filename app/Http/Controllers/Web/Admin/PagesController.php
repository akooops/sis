<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Form;
use Inertia\Response;

class PagesController extends Controller
{
    public function index(): Response
    {
        return inertia('Admin/Dashboard/Index');
    }

    public function users(): Response
    {
        return inertia('Admin/Users/Index');
    }

    public function roles(): Response
    {
        return inertia('Admin/Roles/Index');
    }

    public function permissions(): Response
    {
        return inertia('Admin/Permissions/Index');
    }

    public function apiKeys(): Response
    {
        return inertia('Admin/ApiKeys/Index');
    }

    public function media(): Response
    {
        return inertia('Admin/Media/Index');
    }

    public function activities(): Response
    {
        return inertia('Admin/Activities/Index');
    }

    public function integrations(): Response
    {
        return inertia('Admin/Integrations/Index');
    }

    public function notifications(): Response
    {
        return inertia('Admin/Notifications/Index');
    }

    public function notificationGroups(): Response
    {
        return inertia('Admin/NotificationGroups/Index');
    }

    public function languages(): Response
    {
        return inertia('Admin/Languages/Index');
    }

    public function translations(): Response
    {
        return inertia('Admin/Translations/Index');
    }

    public function pages(): Response
    {
        return inertia('Admin/Pages/Index');
    }

    public function articles(): Response
    {
        return inertia('Admin/Articles/Index');
    }

    public function albums(): Response
    {
        return inertia('Admin/Albums/Index');
    }

    public function brands(): Response
    {
        return inertia('Admin/Brands/Index');
    }

    public function brandAssetGroups(): Response
    {
        return inertia('Admin/BrandAssetGroups/Index');
    }

    public function brandAssets(): Response
    {
        return inertia('Admin/BrandAssets/Index');
    }

    public function events(): Response
    {
        return inertia('Admin/Events/Index');
    }

    public function categories(): Response
    {
        return inertia('Admin/Categories/Index');
    }

    public function achievements(): Response
    {
        return inertia('Admin/Achievements/Index');
    }

    public function partners(): Response
    {
        return inertia('Admin/Partners/Index');
    }

    public function newsletters(): Response
    {
        return inertia('Admin/Newsletters/Index');
    }

    public function newsletterGroups(): Response
    {
        return inertia('Admin/NewsletterGroups/Index');
    }

    public function newsletterGroupSubscribers(): Response
    {
        return inertia('Admin/NewsletterGroupSubscribers/Index');
    }

    public function calendars(): Response
    {
        return inertia('Admin/Calendars/Index');
    }

    public function banners(): Response
    {
        return inertia('Admin/Banners/Index');
    }

    public function documents(): Response
    {
        return inertia('Admin/Documents/Index');
    }

    public function contactDetails(): Response
    {
        return inertia('Admin/ContactDetails/Index');
    }

    public function jobOffers(): Response
    {
        return inertia('Admin/JobOffers/Index');
    }

    public function menus(): Response
    {
        return inertia('Admin/Menus/Index');
    }

    public function menuItems(): Response
    {
        return inertia('Admin/MenuItems/Index');
    }

    public function countries(): Response
    {
        return inertia('Admin/Countries/Index');
    }

    public function programs(): Response
    {
        return inertia('Admin/Programs/Index');
    }

    public function streams(): Response
    {
        return inertia('Admin/Streams/Index');
    }

    public function grades(): Response
    {
        return inertia('Admin/Grades/Index');
    }

    /**
     * `trustsProxies` is deployment state, not form data, so it comes down with
     * the shell rather than from the API.
     *
     * The blocked-addresses drawer needs it to tell the admin the truth: every
     * per-form IP block is enforced against request()->ip(), and with no trusted
     * proxy configured behind a load balancer that address is the balancer's.
     * Blocking it blocks everyone; blocking a visitor's real address blocks
     * no one. A block list that quietly does neither is worse than an empty one.
     */
    public function forms(): Response
    {
        return inertia('Admin/Forms/Index', [
            'trustsProxies' => filled(config('app.trusted_proxies')),
        ]);
    }

    /**
     * Its own page rather than a drawer off Forms: the Forms table drills in
     * with ?filter[form_id]=…, which the client reads out of the URL, so a
     * filtered list stays linkable and exportable.
     */
    public function formSubmissions(): Response
    {
        return inertia('Admin/FormSubmissions/Index');
    }

    /**
     * The one shell in this controller that takes an argument.
     *
     * The builder cannot fetch anything until it knows which form it is
     * building, and binding here means an unknown id 404s before the page
     * renders rather than after. Only the id is passed — everything else comes
     * from the API, like every other page.
     */
    public function formBuilder(Form $form): Response
    {
        return inertia('Admin/Forms/Builder', ['formId' => $form->id]);
    }

    /**
     * The analytics dashboard for one form. Parameterised for the same reason
     * the builder is: there is nothing to render until it is known which form's
     * numbers are being read, and an unknown id should 404 here rather than
     * after a page of empty charts has already drawn.
     */
    public function formAnalytics(Form $form): Response
    {
        return inertia('Admin/Forms/Analytics', ['formId' => $form->id]);
    }
}
