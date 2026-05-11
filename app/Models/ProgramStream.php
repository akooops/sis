<?php

namespace App\Models;

use App\Traits\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgramStream extends Model
{
    use HasFactory, Translatable;

    //Properties
    protected $guarded = ['id'];

    //Relationships
    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    //Accessors & Mutators
    public function getTranslatableFields(): array
    {
        return ['title', 'description', 'content'];
    }
}
