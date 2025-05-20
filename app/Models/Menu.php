<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    use HasFactory;

    //Properties
    protected $guarded = ['id'];

    //Relationships
    public function items()
    {
        return $this->hasMany(MenuItem::class)->whereNull('menu_item_id')->orderBy('order');
    }

    public function allItems()
    {
        return $this->hasMany(MenuItem::class);
    }
}
