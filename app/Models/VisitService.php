<?php

namespace App\Models;

use App\Traits\HasFiles;
use App\Traits\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;

class VisitService extends Model
{
    use HasFactory, Translatable, HasFiles;

    //Properties
    protected $guarded = ['id'];

    protected $appends = ['thumbnailUrl', 'formattedDuration'];

    //Relationships
    public function file()
    {
        return $this->morphOne(File::class, 'model')->where('is_main', 1);
    }

    public function visitTimeSlots()
    {
        return $this->hasMany(VisitTimeSlot::class);
    }

    public function visitBookings()
    {
        return $this->hasMany(VisitBooking::class);
    }

    //Accessors & Mutators
    public function getThumbnailUrlAttribute()
    {
        return ($this->file) ? $this->file->url : URL::to('assets/admin/images/default-thumbnail.jpg');
    }

    public function getFormattedDurationAttribute()
    {
        if ($this->duration > 60) {
            $hours = floor($this->duration / 60);
            $minutes = $this->duration % 60;
            return $minutes > 0 ? "$hours h $minutes min" : "$hours h";
        } else {
            return "$this->duration min";
        }
    }

    public function getTranslatableFields(): array
    {
        return ['title', 'description', 'content'];
    }
}
