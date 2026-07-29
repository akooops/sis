<?php

namespace App\Models;

use App\Traits\HasFiles;
use App\Traits\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;

class Facility extends Model
{
    use HasFactory, Translatable, HasFiles;

    //Properties
    protected $guarded = ['id'];

    protected $casts = [
        'theme' => 'array',
        'socials' => 'array',
    ];

    protected $appends = ['thumbnailUrl', 'logoUrl'];

    //Relationships
    public function file()
    {
        return $this->morphOne(File::class, 'model')->where('is_main', 1);
    }

    public function files()
    {
        return $this->morphMany(File::class, 'model')->where('is_main', 0);
    }

    public function logoFile()
    {
        return $this->belongsTo(File::class, 'logo_file_id');
    }

    public function pages()
    {
        return $this->hasMany(Page::class);
    }

    public function articles()
    {
        return $this->hasMany(Article::class);
    }

    public function albums()
    {
        return $this->hasMany(Album::class);
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function menus()
    {
        return $this->hasMany(Menu::class);
    }

    public function contactSubmissions()
    {
        return $this->hasMany(ContactSubmission::class);
    }

    public function facilityTimeSlots()
    {
        return $this->hasMany(FacilityTimeSlot::class);
    }

    public function upcomingTimeSlots()
    {
        return $this->hasMany(FacilityTimeSlot::class)->where('starts_at', '>=', now());
    }

    public function facilityReservations()
    {
        return $this->hasMany(FacilityReservation::class);
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

    public function getLogoUrlAttribute()
    {
        return ($this->logoFile) ? $this->logoFile->url : null;
    }

    public function getThemeColorAttribute()
    {
        return $this->theme['primary_color'] ?? '#21262c';
    }

    public function getThemeSecondaryColorAttribute()
    {
        return $this->theme['secondary_color'] ?? $this->themeColor;
    }

    public function getWhatsappLinkAttribute()
    {
        if (! $this->whatsapp) {
            return null;
        }

        return 'https://wa.me/' . preg_replace('/[^0-9]/', '', $this->whatsapp);
    }

    public function getTranslatableFields(): array
    {
        return ['title', 'tagline', 'description', 'content', 'address'];
    }

    //Methods
    public function headerMenu()
    {
        return $this->menus()->where('name', 'like', '%header%')->first() ?? $this->menus()->first();
    }
}
