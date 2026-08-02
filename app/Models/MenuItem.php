<?php

namespace App\Models;

use App\Enums\MorphType;
use App\Traits\Translations\HasEnabledTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Translatable\HasTranslations;

/**
 * One entry in a menu. It links nowhere, to an external `url`, or to any internal
 * record with a public slug (`linkable`) — the two are mutually exclusive, and
 * both are optional because a parent is often just a label.
 *
 * Items nest exactly one level: a top-level item may have children, a child may
 * not. `name` is the internal label, `title` the copy the site renders.
 */
class MenuItem extends Model
{
    use HasEnabledTranslations, HasFactory, HasTranslations, HasUlids;

    /* -----------------------------------------
     1. Attributes
    ------------------------------------------*/

    /**
     * MorphType aliases an item may link to: every model with a public slug.
     *
     * @var array<int, string>
     */
    public const LINKABLE_TYPES = [
        'page',
        'article',
        'achievement',
        'album',
        'event',
        'job_offer',
        'program',
        'stream',
        'form',
    ];

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

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('order');
    }

    /** Stores a fully qualified class name, like every other morph column here. */
    public function linkable(): MorphTo
    {
        return $this->morphTo();
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
     * A new item goes on the end of ITS sibling group — order is scoped to
     * (menu, parent), so a child and a root can both sit at 0. Zero-based to match
     * the positions reorder writes.
     */
    public static function nextOrder(string $menuId, ?string $parentId): int
    {
        $last = static::query()
            ->where('menu_id', $menuId)
            ->when(
                $parentId === null,
                fn (Builder $query) => $query->whereNull('parent_id'),
                fn (Builder $query) => $query->where('parent_id', $parentId),
            )
            ->max('order');

        return $last === null ? 0 : (int) $last + 1;
    }

    /** The table an alias points at, for the linkable_id exists rule. */
    public static function linkableTable(?string $alias): ?string
    {
        if (! in_array($alias, self::LINKABLE_TYPES, true)) {
            return null;
        }

        $class = MorphType::classFor($alias);

        return $class === null ? null : (new $class)->getTable();
    }
}
