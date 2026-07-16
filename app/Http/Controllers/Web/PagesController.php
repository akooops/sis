<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

/**
 * The user end. It has no pages yet — every screen built so far is admin — so
 * `/` just points at where you can actually go. Give it its own Inertia page
 * when the user-facing side exists; the admin landing is web.admin.dashboard.
 */
class PagesController extends Controller
{
    public function index(): RedirectResponse
    {
        return auth()->check()
            ? redirect()->route('web.admin.dashboard')
            : redirect()->route('web.admin.auth.login');
    }
}
