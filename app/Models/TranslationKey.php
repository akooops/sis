<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * One translatable line, identified by file (`group`) and dotted path (`key`).
 * Mirrors TranslationKeysSeeder::catalogue(), one entry per group, whose
 * values are locale => string maps. Grown by code, never CRUD.
 *
 * Keys ONLY — values live in lang/{code}/{group}.php. This table is what makes
 * "every key survives a write" checkable, and what the Translations page pages.
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
     * Hang one locale's line off the row so a paginated registry can go straight to
     * TranslationLineData. locale/value/is_translated are transient — no columns,
     * never saved.
     */
    public function withLine(string $locale, ?string $value): static
    {
        $this->setAttribute('locale', $locale);
        $this->setAttribute('value', $value);
        $this->setAttribute('is_translated', is_string($value) && trim($value) !== '');

        return $this;
    }
}
