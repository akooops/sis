<?php

namespace App\Models;

use App\Traits\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Nationality extends Model
{
    use HasFactory, Translatable;

    protected $guarded = ['id'];

    public function getTranslatableFields(): array
    {
        return ['title'];
    }
}
