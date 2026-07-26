<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * One translatable line, identified by its file (`group`) and dotted path
 * (`key`). A seeded mirror of config('translations.keys'); grown by code, never
 * CRUD.
 *
 * The registry holds keys ONLY — a key's value per locale lives in
 * lang/{code}/{group}.php. This table is what makes "every key must survive a
 * write" checkable, and what the Translations page paginates.
 */
class TranslationKey extends Model
{
    use HasUlids;

    /* -----------------------------------------
     1. Attributes
    ------------------------------------------*/

    protected $guarded = ['id'];

    /* -----------------------------------------
     2. Relationships
    ------------------------------------------*/

    /* -----------------------------------------
     3. Accessors
    ------------------------------------------*/

    /* -----------------------------------------
     4. Methods
    ------------------------------------------*/

    /**
     * Hang one locale's line off the row so the paginated registry can be handed
     * straight to TranslationLineData. `locale`/`value`/`is_translated` are
     * transient: they have no columns and must never be saved.
     */
    public function withLine(string $locale, ?string $value): static
    {
        $this->setAttribute('locale', $locale);
        $this->setAttribute('value', $value);
        $this->setAttribute('is_translated', is_string($value) && trim($value) !== '');

        return $this;
    }
}
