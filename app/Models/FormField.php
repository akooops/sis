<?php

namespace App\Models;

use App\Contracts\Forms\FieldType;
use App\Services\Forms\FieldTypeRegistry;
use App\Traits\Translations\HasEnabledTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

/**
 * One element on a form — an input, display copy, or a button.
 *
 * `key` is the machine name: the answer key in form_submissions.data, the CSV
 * column header and the webhook mapping source. Unique per form, and frozen
 * once submissions exist.
 *
 * Note the two kinds of JSON. settings/validation are ordinary array casts;
 * label/placeholder/value/content are translatable. A translatable key must
 * never also appear in $casts — the cast would fight the trait.
 */
class FormField extends Model
{
    use HasEnabledTranslations, HasFactory, HasTranslations, HasUlids;

    /* -----------------------------------------
     1. Attributes
    ------------------------------------------*/

    /**
     * @var array<int, string>
     */
    public $translatable = ['label', 'placeholder', 'value', 'content'];

    protected $guarded = ['id'];

    protected $casts = [
        'settings' => 'array',
        'validation' => 'array',
        'is_required' => 'bool',
        'is_unique' => 'bool',
        'order' => 'integer',
    ];

    /* -----------------------------------------
     2. Relationships
    ------------------------------------------*/

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(FormPage::class, 'form_page_id');
    }

    /** Where a button goes. Null for everything else. */
    public function targetPage(): BelongsTo
    {
        return $this->belongsTo(FormPage::class, 'target_form_page_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(FormFieldOption::class)->orderBy('order');
    }

    /* -----------------------------------------
     3. Accessors
    ------------------------------------------*/

    /* -----------------------------------------
     4. Methods
    ------------------------------------------*/

    /** Fields that hold an answer — headings and buttons do not. */
    public function scopeCapturing(Builder $query): Builder
    {
        return $query->whereIn('type', app(FieldTypeRegistry::class)->inputCodes());
    }

    /**
     * The registry entry backing this field, or null when its type has been
     * dropped from config. Null rather than a throw: an unknown code must
     * degrade, not 500 a public form — the same guard Captcha::driver() applies.
     */
    public function element(): ?FieldType
    {
        $registry = app(FieldTypeRegistry::class);

        return $registry->has($this->type) ? $registry->type($this->type) : null;
    }

    public function capturesValue(): bool
    {
        return (bool) $this->element()?->isInput();
    }

    /** Zero-based, scoped to the page — position inside a page IS the order. */
    public static function nextOrder(string $formPageId): int
    {
        return (int) static::query()->where('form_page_id', $formPageId)->max('order') + 1;
    }
}
