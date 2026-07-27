<?php

namespace App\Models;

use App\Traits\Translations\HasEnabledTranslations;
use App\Traits\Uploads\HasMedia;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

/**
 * A downloadable file with a public label. A row of its own rather than a bare
 * media record: the file is swappable, the title is translated, and neither
 * belongs on the media library's own row.
 */
class Document extends Model
{
    use HasEnabledTranslations, HasFactory, HasMedia, HasTranslations, HasUlids;

    /* -----------------------------------------
     1. Attributes
    ------------------------------------------*/

    public const FILE_COLLECTION = 'file';

    /**
     * Stored as locale => value JSON. The trait casts these, so they must NOT
     * also appear in $casts.
     *
     * @var array<int, string>
     */
    public $translatable = ['title'];

    protected $guarded = ['id'];

    protected $appends = ['file_url'];

    /* -----------------------------------------
     2. Relationships
    ------------------------------------------*/

    /* -----------------------------------------
     3. Accessors
    ------------------------------------------*/

    public function getFileUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl(self::FILE_COLLECTION);
    }

    /* -----------------------------------------
     4. Methods
    ------------------------------------------*/

    /**
     * One file: attaching a new one frees the old back to the pool.
     *
     * @return array<int, string>
     */
    public function singleFileCollections(): array
    {
        return [self::FILE_COLLECTION];
    }
}
