<?php

namespace App\Models;

use App\Traits\Translations\HasEnabledTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

/**
 * A variant of a program — High School runs a British stream and an American one.
 *
 * `name` is the internal label; title/description/content/cta are the public copy.
 * `color` is the chip background on the public site, `order` its position within
 * the program and is only written by the reorder endpoint.
 */
class Stream extends Model
{
    use HasEnabledTranslations, HasFactory, HasTranslations, HasUlids;

    /* -----------------------------------------
     1. Attributes
    ------------------------------------------*/

    public const DEFAULT_COLOR = '#1B84FF';

    /**
     * Stored as locale => value JSON. The trait casts these, so they must NOT
     * also appear in $casts.
     *
     * @var array<int, string>
     */
    public $translatable = ['title', 'description', 'content', 'cta'];

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
     * Order is per program — each program numbers its streams from 0 — so the max
     * is taken within the program. Zero-based to match the positions reorder writes.
     */
    public static function nextOrder(?string $programId): int
    {
        $last = static::query()->where('program_id', $programId)->max('order');

        return $last === null ? 0 : (int) $last + 1;
    }
}
