<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisitTimeSlot extends Model
{
    use HasFactory;

    //Properties
    protected $guarded = ['id'];

    protected $appends = ['reserved'];

    //Relationships
    public function visitService()
    {
        return $this->belongsTo(VisitService::class);
    }

    public function visitBooking()
    {
        return $this->hasOne(VisitBooking::class);
    }

    //Accessors
    public function getReservedAttribute(): bool
    {
        return $this->visitBooking()->exists();
    }
}
