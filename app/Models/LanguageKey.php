<?php

namespace App\Models;

use App\Traits\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LanguageKey extends Model
{
    use HasFactory, Translatable;

    //Properties
    protected $guarded = ['id'];

    public function getTranslatableFields(): array
    {
        return ['content'];
    }
}
