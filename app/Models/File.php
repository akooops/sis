<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class File extends Model
{
    use HasFactory;

    //Properties
    protected $guarded = ['id'];
    protected $appends = ['url'];

    //Relationships

    /**
     * Get the parent model that owns the file.
    */
    public function model()
    {
        return $this->morphTo();
    }

    //Accessors & Mutators
    public function getUrlAttribute()
    {
        return $this->name ? Storage::disk('public')->url($this->path) : URL::to($this->path);
    }

    public function attach(Model $model)
    {
        if($this->model) return;

        $this->update([
            'model_type' => $model->getMorphClass(),
            'model_id' => $model->id
        ]);
    }

    public function detach()
    {
        $this->update([
            'model_type' => null,
            'model_id' => null
        ]);
    }

    //Boot Method
    public static function boot()
    {
        parent::boot();

        static::deleting(function ($file) {
            Storage::disk('public')->delete($file->path);
        });
    }
}
