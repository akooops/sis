<?php

namespace App\Models;

use App\Traits\HasFiles;
use App\Traits\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use HasFactory, Translatable;

    //Properties
    protected $guarded = ['id'];

    protected $casts = [
        'is_system_page' => 'boolean',
    ];

    //Accessors & Mutators
    public function getTranslatableFields(): array
    {
        return ['title', 'description', 'content'];
    }
}
