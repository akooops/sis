<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /** { status, message, data }. `status` follows the HTTP code. */
    protected function respond(mixed $data = null, string $message = '', int $status = 200): JsonResponse
    {
        return response()->json([
            'status' => $status >= 200 && $status < 300 ? 'success' : 'error',
            'message' => $message,
            'data' => $data,
        ], $status);
    }
}
