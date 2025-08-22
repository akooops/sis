<?php

namespace App\Models;

use App\Traits\HasFiles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;

class Language extends Model
{
    use HasFactory, HasFiles;

    //Properties
    protected $guarded = ['id'];

    protected $appends = ['flagUrl'];

    protected $casts = [
        'is_default' => 'boolean',
        'is_rtl' => 'boolean'
    ];

    //Relationships
    public function file()
    {
        return $this->morphOne(File::class, 'model');
    }

    //Accessors & Mutators
    public function getFlagUrlAttribute()
    {
        return ($this->file) ? $this->file->url : URL::to('assets/admin/images/default-thumbnail.jpg');
    }
}
