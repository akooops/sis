<?php

namespace App\Models;

use App\Traits\HasFiles;
use App\Traits\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;

class Program extends Model
{
    use HasFactory, Translatable, HasFiles;

    //Properties
    protected $guarded = ['id'];

    protected $appends = ['thumbnailUrl'];

    //Relationships
    public function file()
    {
        return $this->morphOne(File::class, 'model');
    }

    public function grades(){
        return $this->hasMany(Grade::class, 'program_id', 'id')->orderBy('order');;
    }

    //Accessors & Mutators
    public function getThumbnailUrlAttribute()
    {
        return ($this->file) ? $this->file->url : URL::to('assets/admin/images/default-thumbnail.jpg');
    }

    public function getTranslatableFields(): array
    {
        return ['title', 'subtitle', 'description', 'content'];
    }
}
