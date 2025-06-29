<?php

namespace App\Models;

use App\Traits\HasFiles;
use App\Traits\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;

class Media extends Model
{
    use HasFactory, Translatable, HasFiles;

    //Properties
    protected $guarded = ['id'];

    protected $appends = ['mediaUrl'];

    protected $casts = [
        'is_main' => 'boolean',
    ];

    //Relationships
    public function file()
    {
        return $this->morphOne(File::class, 'model');
    }

    public function mediable()
    {
        return $this->morphTo();
    }
        
    //Accessors & Mutators   
    public function getMediaUrlAttribute()
    {
        return ($this->file) ? $this->file->url : URL::to('assets/admin/images/default-thumbnail.jpg');
    }

    public function getTranslatableFields(): array
    {
        return ['title', 'description'];
    }
}
