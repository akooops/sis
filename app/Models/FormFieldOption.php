<?php

namespace App\Models;

use App\Traits\Translations\HasEnabledTranslations;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

/**
 * A choice on a select, radio or checkbox group.
 *
 * `value` is what gets stored and exported and is deliberately NOT translatable:
 * translating it would make one answer read as several different values across
 * locales and break every downstream filter. `label` is the half a visitor sees.
 */
class FormFieldOption extends Model
{
    use HasEnabledTranslations, HasFactory, HasTranslations, HasUlids;

    /* -----------------------------------------
     1. Attributes
    ------------------------------------------*/

    /**
     * @var array<int, string>
     */
    public $translatable = ['label'];

    protected $guarded = ['id'];

    protected $casts = [
        'is_default' => 'bool',
        'order' => 'integer',
    ];

    /* -----------------------------------------
     2. Relationships
    ------------------------------------------*/

    public function field(): BelongsTo
    {
        return $this->belongsTo(FormField::class, 'form_field_id');
    }

    /* -----------------------------------------
     3. Accessors
    ------------------------------------------*/

    /* -----------------------------------------
     4. Methods
    ------------------------------------------*/

    /** Zero-based, scoped to the field. */
    public static function nextOrder(string $formFieldId): int
    {
        return (int) static::query()->where('form_field_id', $formFieldId)->max('order') + 1;
    }
}
