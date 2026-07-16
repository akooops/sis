<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

class PagesController extends Controller
{
    public function index(): Response|RedirectResponse
    {
        if (! auth()->check()) {
            return redirect()->route('web.auth.login-page');
        }

        return inertia('Home');
    }
}
