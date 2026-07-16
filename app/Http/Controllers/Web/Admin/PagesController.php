<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use Inertia\Response;

/**
 * Renders the admin page shells. Nothing is fetched here on purpose: every page
 * is a Svelte component that pulls its own data from the JSON API, so these
 * actions exist only to name a route and hand Inertia a component.
 */
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
}
