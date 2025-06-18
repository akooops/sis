<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisitTimeSlot extends Model
{
    use HasFactory;

    //Properties
    protected $guarded = ['id'];

    protected $appends = ['reserved', 'remaining_capacity'];

    //Relationships
    public function visitService()
    {
        return $this->belongsTo(VisitService::class);
    }

    public function visitBookings()
    {
        return $this->hasMany(VisitBooking::class);
    }

    //Accessors
    public function getRemainingCapacityAttribute()
    {
        $currentBookings = $this->visitBookings()->sum('visitors_count');
        return  $this->capacity - $currentBookings;
    }
}
