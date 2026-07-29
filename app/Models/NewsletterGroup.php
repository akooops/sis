<?php

namespace App\Models;

use App\Traits\Translations\HasEnabledTranslations;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

/**
 * A mailing list. Two labels again: `name` is internal and never translated,
 * `title`/`description` are the public copy the signup form shows and are.
 *
 * `is_default` is the list a signup lands in when none is named — exactly one
 * row carries it, and that row cannot be deleted.
 */
class NewsletterGroup extends Model
{
    use HasEnabledTranslations, HasFactory, HasTranslations, HasUlids;

    /* -----------------------------------------
     1. Attributes
    ------------------------------------------*/

    /**
     * Stored as locale => value JSON. The trait casts these, so they must NOT
     * also appear in $casts.
     *
     * @var array<int, string>
     */
    public $translatable = ['title', 'description'];

    protected $guarded = ['id'];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    /* -----------------------------------------
     2. Relationships
    ------------------------------------------*/

    public function subscribers(): HasMany
    {
        return $this->hasMany(NewsletterGroupSubscriber::class);
    }

    public function newsletters(): BelongsToMany
    {
        return $this->belongsToMany(Newsletter::class, 'newsletter_newsletter_groups');
    }

    /* -----------------------------------------
     3. Accessors
    ------------------------------------------*/

    /* -----------------------------------------
     4. Methods
    ------------------------------------------*/

    /**
     * Where a signup goes when the form names no list. Seeded and undeletable, so
     * null means unseeded.
     */
    public static function default(): ?self
    {
        return static::query()->where('is_default', true)->first();
    }
}
