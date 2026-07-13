<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Models\Media;
use App\Services\Uploads\UploadService;
use Illuminate\Http\JsonResponse;

class MediaController extends ApiController
{
    /**
     * Detach a media from its owner, returning it to the free (reusable) pool.
     * Nothing is deleted — the file is pruned later if it stays unused.
     */
    public function detach(Media $media): JsonResponse
    {
        UploadService::detach($media);

        return $this->respond(null, 'Media detached successfully');
    }
}
