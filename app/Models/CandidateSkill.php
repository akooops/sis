<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One skill.
 *
 * `name` is what the candidate typed; `fold` is the lowercased form the unique
 * index and every lookup use, so "IB" and "ib" are one skill for matching while
 * the record still shows their own wording.
 */
class CandidateSkill extends Model
{
    use HasFactory, HasUlids;

    /* -----------------------------------------
     1. Attributes
    ------------------------------------------*/

    protected $guarded = ['id'];

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

    /** The comparison form of a skill name — one definition, used on every write. */
    public static function fold(string $name): string
    {
        return mb_strtolower(trim($name));
    }
}
