<?php

namespace App\Models;

use App\Traits\Translations\HasEnabledTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

/**
 * A page of a form. Every form has at least one; a "page break" in the builder
 * is the next row here.
 *
 * A page is a real row rather than a break-type field because its title is
 * translatable and a button targets it by id.
 */
class FormPage extends Model
{
    use HasEnabledTranslations, HasFactory, HasTranslations, HasUlids;

    /* -----------------------------------------
     1. Attributes
    ------------------------------------------*/

    /**
     * @var array<int, string>
     */
    public $translatable = ['title'];

    protected $guarded = ['id'];

    protected $casts = [
        'is_interstitial' => 'bool',
        'order' => 'integer',
    ];

    /* -----------------------------------------
     2. Relationships
    ------------------------------------------*/

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    /** EVERY field on the page, group children included. */
    public function fields(): HasMany
    {
        return $this->hasMany(FormField::class)->orderBy('order');
    }

    /**
     * The fields laid out on the page itself — what anything that RENDERS or
     * COLUMNISES the page wants, since a group draws its own children and a child
     * has no position of its own on the page.
     */
    public function topLevelFields(): HasMany
    {
        return $this->hasMany(FormField::class)->topLevel()->orderBy('order');
    }

    /* -----------------------------------------
     3. Accessors
    ------------------------------------------*/

    /* -----------------------------------------
     4. Methods
    ------------------------------------------*/

    /** Pages in the step sequence — an instruction page is not one. */
    public function scopeLinear(Builder $query): Builder
    {
        return $query->where('is_interstitial', false);
    }

    /** Zero-based, scoped to the form. */
    public static function nextOrder(string $formId): int
    {
        return (int) static::query()->where('form_id', $formId)->max('order') + 1;
    }
}
