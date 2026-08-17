<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One qualification, projected out of the application form's education group. */
class CandidateEducation extends Model
{
    use HasFactory, HasUlids;

    /* -----------------------------------------
     1. Attributes
    ------------------------------------------*/

    /**
     * PINNED, because Laravel's inflector treats "education" as UNCOUNTABLE and
     * infers `candidate_education` — the one table in this module whose name it
     * gets wrong, and it fails at runtime with "table doesn't exist" rather than
     * anywhere a reader would look.
     *
     * The table stays plural like every sibling; the previous app gave in and
     * named its equivalent singular, which left one odd table out of five.
     */
    protected $table = 'candidate_educations';

    protected $guarded = ['id'];

    protected $casts = [
        'start_year' => 'integer',
        'end_year' => 'integer',
        'order' => 'integer',
    ];

    /* -----------------------------------------
     2. Relationships
    ------------------------------------------*/

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    /* -----------------------------------------
     3. Accessors
    ------------------------------------------*/

    /* -----------------------------------------
     4. Methods
    ------------------------------------------*/
}
