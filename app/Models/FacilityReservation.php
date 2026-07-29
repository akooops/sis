<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FacilityReservation extends Model
{
    use HasFactory;

    //Properties
    protected $guarded = ['id'];

    //Relationships
    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }

    public function facilityTimeSlot()
    {
        return $this->belongsTo(FacilityTimeSlot::class);
    }
}
