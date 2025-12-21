<?php

namespace App\Models;

use App\Traits\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AchievementCategory extends Model
{
    use HasFactory, Translatable;

    protected $guarded = ['id'];

    public function achievements()
    {
        return $this->hasMany(Achievement::class);
    }

    public function getTranslatableFields(): array
    {
        return ['title'];
    }
}
