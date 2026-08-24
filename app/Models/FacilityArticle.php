<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One news item shown on one venue's page.
 *
 * A first-class pivot with its own ULID key, so it can be observed and audited.
 * THIS is the write path — Facility::articles() is read-only, because attach()
 * bypasses the model and never generates that key.
 */
class FacilityArticle extends Model
{
    use HasFactory, HasUlids;

    /* -----------------------------------------
     1. Attributes
    ------------------------------------------*/

    protected $guarded = ['id'];

    protected $casts = [];

    /* -----------------------------------------
     2. Relationships
    ------------------------------------------*/

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    /* -----------------------------------------
     3. Accessors
    ------------------------------------------*/

    /* -----------------------------------------
     4. Methods
    ------------------------------------------*/
}
