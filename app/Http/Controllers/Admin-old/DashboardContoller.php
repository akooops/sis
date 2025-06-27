<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\Auth\LoginRequest;
use App\Models\Role;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;

class DashboardContoller extends Controller
{
    public function index()
    {
        $stats = [
            'users' => User::count(),
            'articles' => \App\Models\Article::count(),
            'events' => \App\Models\Event::count(),
            'albums' => \App\Models\Album::count(),
        ];

        return Inertia::render('Admin/Dashboard', [
            'auth' => [
                'user' => auth()->user(),
            ],
            'stats' => $stats,
        ]);
    }
}
