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
    
    protected $appends = ['url'];

    //Relationships
    public function linkable()
    {
        return $this->morphTo();
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

    /**
     * Generate URL based on linkable model or fallback to url field
     */
    public function getUrlAttribute()
    {
        if ($this->linkable) {
            $type = class_basename($this->linkable_type);
            
            switch (strtolower($type)) {
                case 'program':
                    return route('program', ['slug' => $this->linkable->slug]);
                    
                case 'page':
                    return route('page', ['slug' => $this->linkable->slug]);
                    
                case 'article':
                    return route('article', ['slug' => $this->linkable->slug]);
                    
                case 'album':
                    return route('album', ['slug' => $this->linkable->slug]);
                    
                case 'event':
                    return route('event', ['slug' => $this->linkable->slug]);
                    
                case 'grade':
                    return route('grade', ['slug' => $this->linkable->slug]);
                    
                case 'jobposting':
                    return route('job', ['slug' => $this->linkable->slug]);
                    
                default:
                    return $this->attributes['url'] ?? '#';
            }
        }
        
        return $this->attributes['url'] ?? '#';
    }

    /**
     * Check if menu item has a linked model
     */
    public function hasLink(): bool
    {
        return !is_null($this->linkable_id) && !is_null($this->linkable_type);
    }

    /**
     * Get the display name for the linked model type
     */
    public function getLinkTypeAttribute(): ?string
    {
        if (!$this->linkable_type) {
            return null;
        }
        
        return class_basename($this->linkable_type);
    }
}
