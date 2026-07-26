<?php

namespace App\Models;

use App\States\Page\PageStatus;
use App\States\Page\Published;
use App\Traits\Uploads\HasMedia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\ModelStates\HasStates;
use Spatie\Translatable\HasTranslations;

/**
 * A content page: an editorial record with a public slug and a publish workflow.
 *
 * `name` is the internal label an admin scans the list by; `title` is what the
 * public sees, and it — with `description` and `content` — is translated per
 * locale by spatie/laravel-translatable into a JSON column. That is content, not
 * UI chrome: the UI string catalogue still lives in lang/*.php and is untouched.
 *
 * One media collection: `thumbnail`. Images inserted into the content stay FREE
 * media in the library — nothing prunes them, so nothing needs to own them.
 */
class Page extends Model
{
    use HasFactory, HasMedia, HasStates, HasTranslations, HasUlids;

    /* -----------------------------------------
     1. Attributes
    ------------------------------------------*/

    public const THUMBNAIL_COLLECTION = 'thumbnail';

    /**
     * Columns HasTranslations stores as a locale => value JSON map. The trait
     * casts these itself, so they must NOT be repeated in $casts.
     *
     * @var array<int, string>
     */
    public $translatable = ['title', 'description', 'content'];

    protected $guarded = ['id'];

    protected $appends = ['thumbnail_url'];

    protected $casts = [
        'status' => PageStatus::class,
        'published_at' => 'datetime',
        'is_system' => 'bool',
    ];

    /* -----------------------------------------
     2. Relationships
    ------------------------------------------*/

    /* -----------------------------------------
     3. Accessors
    ------------------------------------------*/

    /**
     * Null when there is no thumbnail yet — unlike Language::flag_url there is no
     * bundled artwork to fall back to, and a page's thumbnail is required at
     * creation anyway.
     */
    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl(self::THUMBNAIL_COLLECTION);
    }

    /* -----------------------------------------
     4. Methods
    ------------------------------------------*/

    /** Pages the public site may serve. */
    public function scopeLive(Builder $query): Builder
    {
        return $query->whereState('status', Published::class);
    }

    /**
     * One thumbnail: attaching a new one frees the old back into the reusable
     * pool. `images` is deliberately absent, which is what makes it multi-file.
     *
     * @return array<int, string>
     */
    public function singleFileCollections(): array
    {
        return [self::THUMBNAIL_COLLECTION];
    }
}
