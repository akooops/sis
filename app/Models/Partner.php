<?php

namespace App\Models;

use App\Traits\Uploads\HasMedia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;

/**
 * An organisation shown in the partners strip. Flat and ordered: `order` is the
 * display position and is only ever written by the reorder endpoint.
 */
class Partner extends Model
{
    use HasFactory, HasMedia, HasUlids;

    /* -----------------------------------------
     1. Attributes
    ------------------------------------------*/

    public const LOGO_COLLECTION = 'logo';

    protected $guarded = ['id'];

    protected $appends = ['logo_url'];

    protected $casts = [
        'order' => 'integer',
    ];

    /* -----------------------------------------
     2. Relationships
    ------------------------------------------*/

    /* -----------------------------------------
     3. Accessors
    ------------------------------------------*/

    public function getLogoUrlAttribute(): string
    {
        return $this->getFirstMediaUrl(self::LOGO_COLLECTION) ?: URL::to('assets/media/app/mini-logo.png');
    }

    /* -----------------------------------------
     4. Methods
    ------------------------------------------*/

    /** Display order, with created_at breaking ties so it is never arbitrary. */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order')->orderBy('created_at');
    }

    /**
     * Where a newly created partner goes: on the end. Zero-based, matching the
     * positions the reorder endpoint writes — otherwise the very first partner
     * would sit at 1 until someone dragged the list.
     */
    public static function nextOrder(): int
    {
        $last = static::query()->max('order');

        return $last === null ? 0 : (int) $last + 1;
    }

    /**
     * @return array<int, string>
     */
    public function singleFileCollections(): array
    {
        return [self::LOGO_COLLECTION];
    }
}
