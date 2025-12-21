<?php

namespace App\Models;

use App\Traits\HasFiles;
use App\Traits\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Achievement extends Model
{
    use HasFactory, Translatable, HasFiles;

    protected $guarded = ['id'];

    protected $appends = ['url', 'thumbnailUrl'];

    // Relationships
    public function category()
    {
        return $this->belongsTo(AchievementCategory::class, 'achievement_category_id');
    }

    public function linkable()
    {
        return $this->morphTo();
    }

    public function file()
    {
        return $this->morphOne(File::class, 'model');
    }

    // Accessors
    public function getUrlAttribute()
    {
        if ($this->linkable) {
            $type = class_basename($this->linkable_type);
            
            switch (strtolower($type)) {
                case 'program':
                    return route('program', ['slug' => $this->linkable->slug]);
                case 'page':
                    return route('page', ['slug' => $this->linkable->slug]);
                case 'article':
                    return route('article', ['slug' => $this->linkable->slug]);
                case 'album':
                    return route('album', ['slug' => $this->linkable->slug]);
                case 'event':
                    return route('event', ['slug' => $this->linkable->slug]);
                case 'grade':
                    return route('grade', ['slug' => $this->linkable->slug]);
                case 'jobposting':
                    return route('job', ['slug' => $this->linkable->slug]);
                default:
                    return $this->attributes['url'] ?? '#';
            }
        }
        
        return $this->attributes['url'] ?? '#';
    }

    public function getThumbnailUrlAttribute()
    {
        return ($this->file) ? $this->file->url : asset('assets/admin/images/default-thumbnail.jpg');
    }

    public function getTranslatableFields(): array
    {
        return ['title', 'description', 'done_by'];
    }
}
