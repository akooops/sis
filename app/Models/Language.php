<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    use HasFactory;

    //Properties
    protected $guarded = ['id'];

    protected $casts = [
        'is_default' => 'boolean',
        'is_rtl' => 'boolean'
    ];
}
