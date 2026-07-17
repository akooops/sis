<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class PagesController extends Controller
{
    public function index(): RedirectResponse
    {
        return auth()->check()
            ? redirect()->route('web.admin.dashboard')
            : redirect()->route('web.admin.auth.login');
    }
}
