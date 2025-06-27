<?php

namespace App\Http\Controllers\Admin;

use App\Services\FileService;
use App\Services\IndexService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected $indexService;
    protected $fileService;

    public function __construct(IndexService $indexService,FileService $fileService)
    {
        $this->indexService = $indexService;
        $this->fileService = $fileService;
    }
}
