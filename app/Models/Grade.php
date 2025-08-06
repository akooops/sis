<?php

namespace App\Models;

use App\Traits\HasFiles;
use App\Traits\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    use HasFactory, HasFiles, Translatable;

    //Properties
    protected $guarded = ['id'];

    //Relationships
    public function files()
    {
        return $this->morphMany(File::class, 'model')->where('is_main', 0);
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    //Accessors & Mutators
    public function getTranslatableFields(): array
    {
        return ['title'];
    }
}
