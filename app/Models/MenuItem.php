<?php

namespace App\Models;

use App\Traits\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;

class MenuItem extends Model
{
    use HasFactory, Translatable;

    //Properties
    protected $guarded = ['id'];

    //Relationships
    public function page()
    {
        return $this->belongsTo(Page::class, 'page_id');
    }

    public function menu()
    {
        return $this->belongsTo(Menu::class, 'menu_id');
    }

    public function parent()
    {
        return $this->belongsTo(MenuItem::class, 'menu_item_id');
    }

    public function children()
    {
        return $this->hasMany(MenuItem::class, 'menu_item_id')->orderBy('order');
    }

    //Accessors & Mutators
    public function getTranslatableFields(): array
    {
        return ['title'];
    }
}
