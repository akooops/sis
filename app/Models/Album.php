<?php

namespace App\Models;

use App\Traits\HasFiles;
use App\Traits\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;

class Album extends Model
{
    use HasFactory, Translatable, HasFiles;

    //Properties
    protected $guarded = ['id'];

    protected $appends = ['thumbnailUrl'];

    //Relationships
    public function file()
    {
        return $this->morphOne(File::class, 'model')->where('is_main', 1);
    }

    public function files()
    {
        return $this->morphMany(File::class, 'model')->where('is_main', 0);
    }

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }

    //Scopes
    public function scopeMain($query)
    {
        return $query->whereNull('facility_id');
    }

    //Accessors & Mutators
    public function getThumbnailUrlAttribute()
    {
        return ($this->file) ? $this->file->url : URL::to('assets/admin/images/default-thumbnail.jpg');
    }

    public function getTranslatableFields(): array
    {
        return ['title', 'description'];
    }
}
