<?php

namespace App\Models;

use App\Traits\HasFiles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory, HasFiles;

    //Properties
    protected $guarded = ['id'];

    protected $casts = [
        'options' => 'json',
    ];

    //Relationships
    public function page()
    {
        return $this->belongsTo(Page::class, 'value', 'id');
    }

    //Boot
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($setting) {    
            $setting->value = json_encode($setting->value);

            if ($setting->is_encrypted) {
                $setting->value = encrypt($setting->value);
            }
        });

        static::retrieved(function ($setting) {
            if ($setting->is_encrypted) {
                $setting->value = decrypt($setting->value);
            }

            try {
                $setting->value = json_decode($setting->value, true);
            } catch (\Exception $e) {
                $setting->value = [];
            }
        });
    }
}
