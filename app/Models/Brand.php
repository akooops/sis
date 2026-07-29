<?php

namespace App\Models;

use App\Traits\HasFiles;
use App\Traits\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;

class Brand extends Model
{
    use HasFactory, Translatable, HasFiles;

    //Properties
    protected $guarded = ['id'];

    protected $appends = ['thumbnailUrl'];

    //Relationships
    public function file()
    {
        return $this->morphOne(File::class, 'model')->where('is_main', 1);
    }

    public function assets()
    {
        return $this->hasMany(BrandAsset::class)->orderBy('order')->orderBy('id');
    }

    //Scopes
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    //Accessors & Mutators
    public function getThumbnailUrlAttribute()
    {
        return ($this->file) ? $this->file->url : URL::to('assets/admin/images/default-thumbnail.jpg');
    }

    public function getTranslatableFields(): array
    {
        return ['title', 'tagline', 'description', 'content'];
    }

    //Methods

    /**
     * Assets keyed by group in the fixed display order used on the public page.
     */
    public function groupedAssets()
    {
        $order = ['logos', 'colors', 'fonts', 'guidelines', 'audio', 'images', 'documents'];

        return $this->assets
            ->groupBy('group')
            ->sortBy(function ($assets, $group) use ($order) {
                $index = array_search($group, $order);

                return $index === false ? count($order) : $index;
            });
    }
}
