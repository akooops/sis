<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BrandAsset extends Model
{
    use HasFactory;

    //Properties
    protected $guarded = ['id'];

    protected $appends = ['url', 'fileType', 'fileSize'];

    protected $with = ['file'];

    //Relationships
    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function file()
    {
        return $this->morphOne(File::class, 'model')->where('is_main', 1);
    }

    //Accessors & Mutators
    public function getUrlAttribute()
    {
        return $this->file?->url;
    }

    public function getFileTypeAttribute()
    {
        $mime = $this->file->type ?? '';

        return match (true) {
            str_starts_with($mime, 'image/') => 'image',
            str_starts_with($mime, 'video/') => 'video',
            str_starts_with($mime, 'audio/') => 'audio',
            default => 'document',
        };
    }

    public function getFileSizeAttribute()
    {
        $bytes = $this->file->size ?? 0;

        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        }

        if ($bytes >= 1024) {
            return round($bytes / 1024) . ' KB';
        }

        return $bytes . ' B';
    }

    //Boot
    protected static function boot()
    {
        parent::boot();

        // The asset owns its file copy: remove the file row (and the physical
        // file via File::boot) when the asset is deleted.
        static::deleting(function ($asset) {
            if ($asset->file) {
                $asset->file->delete();
            }
        });
    }
}
