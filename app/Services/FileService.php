<?php

namespace App\Services;

use App\Models\File;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileService
{
    public function upload(UploadedFile $file, $model_type = null, $model_id = null)
    {
        $originalName = $file->getClientOriginalName();
        $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $filePath = 'uploads/' . $fileName;

        Storage::disk('public')->putFileAs('uploads', $file, $fileName);

        return File::create([
            'original_name' => $originalName,
            'name' => $fileName,
            'path' => $filePath,
            'size' => $file->getSize(),
            'type' => $file->getMimeType(),
            'model_type' => $model_type,
            'model_id' => $model_id,
        ]);
    }

    public function cleanupUnusedFiles($olderThan = '-30 days')
    {
        $files = File::where('created_at', '<', now()->modify($olderThan))
                     ->get();

        $deletedCount = 0;

        foreach ($files as $file) {
            if (!$file->isUsed()) {
                $file->delete();
                $deletedCount++;
            }
        }

        return $deletedCount;
    }
}