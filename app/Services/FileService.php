<?php

namespace App\Services;

use App\Models\File;
use App\Models\Media;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileService
{
    public function upload(UploadedFile $file, $model_type = null, $model_id = null, $is_main = 0)
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
            'is_main' => $is_main,
            'model_type' => $model_type,
            'model_id' => $model_id,
        ]);
    }

    public function duplicateMediaFile(Media $media, $model_type = null, $model_id = null, $is_main = false)
    {
        $originalFile = $media->file;
        if (!$originalFile) {
            return null;
        }

        $extension = pathinfo($originalFile->name, PATHINFO_EXTENSION);
        $newFileName = uniqid() . '.' . $extension;
        $newFilePath = dirname($originalFile->path) . '/' . $newFileName;

        // Copy the file in storage
        if (!Storage::disk('public')->copy($originalFile->path, $newFilePath)) {
            return null;
        }

        // Create a new File record
        $newFile = File::create([
            'original_name' => $originalFile->original_name,
            'name' => $newFileName,
            'path' => $newFilePath,
            'size' => $originalFile->size,
            'type' => $originalFile->type,
            'is_main' => $is_main,
            'model_type' => $model_type,
            'model_id' => $model_id,
        ]);

        return $newFile;
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