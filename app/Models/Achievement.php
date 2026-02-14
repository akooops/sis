<?php

namespace App\Models;

use App\Traits\HasFiles;
use App\Traits\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Achievement extends Model
{
    use HasFactory, HasFiles, Translatable;

    protected $guarded = ['id'];

    protected $appends = ['thumbnailUrl'];

    // Relationships
    public function category()
    {
        return $this->belongsTo(AchievementCategory::class, 'achievement_category_id');
    }

    public function file()
    {
        return $this->morphOne(File::class, 'model');
    }

    // Accessors
    public function getThumbnailUrlAttribute()
    {
        return ($this->file) ? $this->file->url : asset('assets/admin/images/default-thumbnail.jpg');
    }

    public function getTranslatableFields(): array
    {
        return ['title', 'description', 'content', 'done_by'];
    }
}
