<?php

namespace App\Models;

use App\Traits\HasFiles;
use App\Traits\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Newsletter extends Model
{
    use HasFactory, HasFiles, Translatable;

    // Properties
    protected $guarded = ['id'];

    protected $appends = ['newsletterUrl'];

    // Relationships
    public function file()
    {
        return $this->morphOne(File::class, 'model')->where('is_main', 1);
    }

    // Accessors & Mutators
    public function getNewsletterUrlAttribute()
    {
        return ($this->file) ? $this->file->url : '#';
    }

    public function getTranslatableFields(): array
    {
        return ['title'];
    }
}
