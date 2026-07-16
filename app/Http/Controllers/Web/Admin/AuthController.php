<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use Inertia\Response;

class AuthController extends Controller
{
    public function login(): Response
    {
        return inertia('Admin/Auth/Login');
    }
}
