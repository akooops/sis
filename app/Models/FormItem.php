<?php

namespace App\Models;

use App\Traits\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormItem extends Model
{
    use HasFactory, Translatable;

    //Properties
    protected $guarded = ['id'];

    protected $casts = [
        'options' => 'json',
        'is_required' => 'boolean'
    ];

    //Relationships
    public function form()
    {
        return $this->belongsTo(Form::class, 'form_id');
    }

    //Accessors & Mutators
    public function getTranslatableFields(): array
    {
        return ['label'];
    }
}
