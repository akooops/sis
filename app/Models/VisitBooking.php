<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisitBooking extends Model
{
    use HasFactory;

    //Properties
    protected $guarded = ['id'];

    protected $casts = [
        'students' => 'array',
    ];

    //Relationships
    public function visitService()
    {
        return $this->belongsTo(VisitService::class);
    }

    public function visitTimeSlot()
    {
        return $this->belongsTo(VisitTimeSlot::class);
    }
}
