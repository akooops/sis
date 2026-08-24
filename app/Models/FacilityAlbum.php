<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One photo album shown on one venue's page. The twin of FacilityArticle. */
class FacilityAlbum extends Model
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

    public function album(): BelongsTo
    {
        return $this->belongsTo(Album::class);
    }

    /* -----------------------------------------
     3. Accessors
    ------------------------------------------*/

    /* -----------------------------------------
     4. Methods
    ------------------------------------------*/
}
