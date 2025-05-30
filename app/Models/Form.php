<?php

namespace App\Models;

use App\Traits\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Form extends Model
{
    use HasFactory, Translatable;

    //Properties
    protected $guarded = ['id'];

    protected $casts = [
        'has_captcha' => 'boolean'
    ];

    //Relationships
    public function items()
    {
        return $this->hasMany(FormItem::class)->orderBy('order');
    }
    
    //Accessors & Mutators
    public function getTranslatableFields(): array
    {
        return ['title', 'description'];
    }
}
