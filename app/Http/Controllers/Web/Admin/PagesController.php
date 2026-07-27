<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
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

    public function documents(): Response
    {
        return inertia('Admin/Documents/Index');
    }

    public function jobOffers(): Response
    {
        return inertia('Admin/JobOffers/Index');
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
}
