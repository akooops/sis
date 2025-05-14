<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\Files\FileUploadRequest;
use App\Models\File;

class FilesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function upload(FileUploadRequest $request)
    {
        if($request->has('file')){
            $file = $this->fileService->upload($request->file('file'));

            return response()->json([
                'status' => 'success',
                'data' => [
                    'file' => $file
                ]
            ]);
        }
    }
}
