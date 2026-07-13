<?php

namespace App\Http\Controllers\Api;

use App\Data\Upload\StoreUploadData;
use App\Services\Uploads\UploadService;
use Illuminate\Http\JsonResponse;

class UploadController extends ApiController
{
    /**
     * Validate + quarantine a file and return a reference to submit with a form.
     */
    public function store(StoreUploadData $data): JsonResponse
    {
        return $this->respond(UploadService::store($data), 'File uploaded successfully', 201);
    }
}
