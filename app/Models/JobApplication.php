<?php

namespace App\Models;

use App\Traits\HasFiles;
use App\Traits\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobApplication extends Model
{
    use HasFactory, HasFiles;

    //Properties
    protected $guarded = ['id'];

    protected $casts = [
        'ai_scored_at' => 'datetime',
    ];

    //Relationships
    public function jobPosting()
    {
        return $this->belongsTo(JobPosting::class);
    }

    public function education()
    {
        return $this->hasMany(JobApplicationEducation::class);
    }

    public function experiences()
    {
        return $this->hasMany(JobApplicationExperience::class);
    }

    public function languages()
    {
        return $this->hasMany(JobApplicationLanguage::class);
    }

    public function cv()
    {
        return $this->morphOne(File::class, 'model')->where('is_main', 1);
    }

    //Accessors & Mutators
    public function getSkillsArrayAttribute()
    {
        return $this->skills ? explode(',', $this->skills) : [];
    }

    public function setSkillsAttribute($value)
    {
        $this->attributes['skills'] = is_array($value) ? implode(',', array_filter($value)) : $value;
    }
}
