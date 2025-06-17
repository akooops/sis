<?php

namespace App\Models;

use App\Traits\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobPosting extends Model
{
    use HasFactory, Translatable;

    //Properties
    protected $guarded = ['id'];

    //Relationships
    public function jobApplications()
    {
        return $this->hasMany(JobApplication::class);
    }

    //Accessors & Mutators
    public function getTranslatableFields(): array
    {
        return ['title', 'description', 'content', 'required_skills'];
    } 
}
