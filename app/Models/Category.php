<?php

namespace App\Models;

use App\Enums\CategoryType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A classification for one kind of content. `type` scopes the whole thing: an
 * article can only ever be filed under an `articles` category, so each picker
 * shows its own list and the two never mix.
 *
 * `name` is plain, not translatable — a category is an editorial filing label
 * rather than published copy.
 */
class Category extends Model
{
    use HasFactory, HasUlids;

    /* -----------------------------------------
     1. Attributes
    ------------------------------------------*/

    protected $guarded = ['id'];

    protected $casts = [
        'type' => CategoryType::class,
    ];

    /* -----------------------------------------
     2. Relationships
    ------------------------------------------*/

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    public function achievements(): HasMany
    {
        return $this->hasMany(Achievement::class);
    }

    /* -----------------------------------------
     3. Accessors
    ------------------------------------------*/

    /* -----------------------------------------
     4. Methods
    ------------------------------------------*/

    public function scopeOfType(Builder $query, CategoryType|string $type): Builder
    {
        return $query->where('type', $type instanceof CategoryType ? $type->value : $type);
    }
}
