<?php

namespace App\Models;

use App\Traits\HasFiles;
use App\Traits\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobApplicationEducation extends Model
{
    use HasFactory;

    //Properties
    protected $guarded = ['id'];

    //Relationships
    public function jobApplication()
    {
        return $this->belongsTo(JobApplication::class);
    }
}
