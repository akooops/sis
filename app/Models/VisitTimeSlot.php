<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisitTimeSlot extends Model
{
    use HasFactory;

    //Properties
    protected $guarded = ['id'];

    //Relationships
    public function visitService()
    {
        return $this->belongsTo(VisitService::class);
    }
}
