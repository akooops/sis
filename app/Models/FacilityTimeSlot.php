<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FacilityTimeSlot extends Model
{
    use HasFactory;

    //Properties
    protected $guarded = ['id'];

    protected $appends = ['reserved', 'remaining_capacity'];

    //Relationships
    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }

    public function facilityReservations()
    {
        return $this->hasMany(FacilityReservation::class);
    }

    //Accessors
    public function getRemainingCapacityAttribute()
    {
        $currentReservations = $this->facilityReservations()->count();

        return $this->capacity - $currentReservations;
    }

    public function getReservedAttribute()
    {
        return $this->facilityReservations()->count() >= $this->capacity ? true : false;
    }
}
