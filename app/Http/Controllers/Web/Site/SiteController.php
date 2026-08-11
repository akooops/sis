<?php

namespace App\Http\Controllers\Web\Site;

use App\Http\Controllers\Controller;
use App\Services\Site\SiteContext;

abstract class SiteController extends Controller
{
    protected function site(): SiteContext
    {
        return app(SiteContext::class);
    }
}
