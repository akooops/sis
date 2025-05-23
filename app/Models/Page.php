<?php

namespace App\Models;

use App\Traits\HasFiles;
use App\Traits\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;

class Page extends Model
{
    use HasFactory, Translatable, HasFiles;

    //Properties
    protected $guarded = ['id'];

    protected $casts = [
        'is_system_page' => 'boolean',
    ];

    protected $appends = ['thumbnailUrl'];

    //Relationships
    public function file()
    {
        return $this->morphOne(File::class, 'model');
    }

    public function menu()
    {
        return $this->belongsTo(Menu::class, 'menu_id');
    }
    
    //Accessors & Mutators
    public function getThumbnailUrlAttribute()
    {
        return ($this->file) ? $this->file->url : URL::to('assets/admin/images/default-thumbnail.jpg');
    }

    public function getTranslatableFields(): array
    {
        return ['title', 'description', 'content'];
    }
}
