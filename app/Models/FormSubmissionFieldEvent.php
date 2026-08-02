<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * What happened at one field during one submission.
 *
 * The module's only per-field table, and it earns that: the headline analytic —
 * which field loses people — groups ACROSS submissions by field, which as JSON
 * on the submission would be a full scan on every dashboard load.
 *
 * Counts only. Keystrokes and pasted content are never stored, only how many
 * there were. That is a privacy line, not an optimisation.
 *
 * Not audited: these are written by the telemetry beacon, several times per
 * visit, and an activity row each would bury the log.
 */
class FormSubmissionFieldEvent extends Model
{
    use HasFactory, HasUlids;

    /* -----------------------------------------
     1. Attributes
    ------------------------------------------*/

    protected $guarded = ['id'];

    protected $casts = [
        'focus_order' => 'integer',
        'focus_ms' => 'integer',
        'revisits' => 'integer',
        'keystrokes' => 'integer',
        'deletions' => 'integer',
        'final_length' => 'integer',
        'paste_count' => 'integer',
        'error_count' => 'integer',
        'is_abandoned' => 'bool',
    ];

    /* -----------------------------------------
     2. Relationships
    ------------------------------------------*/

    public function submission(): BelongsTo
    {
        return $this->belongsTo(FormSubmission::class, 'form_submission_id');
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(FormField::class, 'form_field_id');
    }

    /* -----------------------------------------
     3. Accessors
    ------------------------------------------*/

    /* -----------------------------------------
     4. Methods
    ------------------------------------------*/
}
