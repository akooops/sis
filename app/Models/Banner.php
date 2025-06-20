<?php

namespace App\Models;

use App\Traits\HasFiles;
use App\Traits\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;

class Banner extends Model
{
    use HasFactory, Translatable, HasFiles;

    //Properties
    protected $guarded = ['id'];

    protected $appends = ['thumbnailUrl', 'videoUrl'];

    //Relationships
    public function file()
    {
        return $this->morphOne(File::class, 'model')->where('is_main', 1);
    }

    public function video()
    {
        return $this->morphOne(File::class, 'model')->where('is_main', 0);
    }

    public function page()
    {
        return $this->belongsTo(Page::class, 'page_id');
    }

    //Accessors & Mutators
    public function getThumbnailUrlAttribute()
    {
        return ($this->file) ? $this->file->url : URL::to('assets/admin/images/default-banner.jpg');
    }

    public function getVideoUrlAttribute()
    {
        return ($this->video) ? $this->video->url : '';
    }

    public function getTranslatableFields(): array
    {
        return ['title', 'cta'];
    } 
}
