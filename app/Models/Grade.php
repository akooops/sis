<?php

namespace App\Models;

use App\Traits\Translations\HasEnabledTranslations;
use App\Traits\Uploads\HasMedia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

/**
 * A year group inside a program (Grade 1 … Grade 12) plus its downloadable
 * guidelines. `order` is the position within the program and is only written by
 * the reorder endpoint.
 */
class Grade extends Model
{
    use HasEnabledTranslations, HasFactory, HasMedia, HasTranslations, HasUlids;

    /* -----------------------------------------
     1. Attributes
    ------------------------------------------*/

    /** Multi-file: absent from singleFileCollections(), so attaching keeps the rest. */
    public const GUIDELINES_COLLECTION = 'guidelines';

    /**
     * Mirrors the keys in config('uploads.allowed_types'). Documents mostly; images
     * cover the scanned sheet nobody re-typed.
     *
     * @var array<int, string>
     */
    public const GUIDELINE_TYPES = ['documents', 'images'];

    /**
     * Stored as locale => value JSON. The trait casts these, so they must NOT
     * also appear in $casts.
     *
     * @var array<int, string>
     */
    public $translatable = ['title'];

    protected $guarded = ['id'];

    protected $casts = [
        'order' => 'integer',
    ];

    /* -----------------------------------------
     2. Relationships
    ------------------------------------------*/

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /* -----------------------------------------
     3. Accessors
    ------------------------------------------*/

    /* -----------------------------------------
     4. Methods
    ------------------------------------------*/

    /** Display order, created_at breaking ties so it is never arbitrary. */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order')->orderBy('created_at');
    }

    /**
     * A new grade goes on the end of ITS program. Zero-based to match the positions
     * reorder writes, or the first grade of a program would sit at 1 until someone
     * dragged.
     */
    public static function nextOrder(?string $programId): int
    {
        $last = static::query()->where('program_id', $programId)->max('order');

        return $last === null ? 0 : (int) $last + 1;
    }
}
