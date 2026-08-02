<?php

namespace App\Models;

use App\Traits\Translations\HasEnabledTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

/**
 * One contact detail — a phone, an email, an address, a social profile. `type`
 * is a code from the registry in config('contacts.types'), which also declares
 * what shape that type's `value` takes.
 *
 * `name` is the internal label, `title` the translated public one. `value` holds
 * the machine-readable half — E164 number, email address, URL — and is null for
 * the address type, whose text is translated in `address` instead.
 *
 * `order` is the display position and is only written by the reorder endpoint.
 */
class ContactDetail extends Model
{
    use HasEnabledTranslations, HasFactory, HasTranslations, HasUlids;

    /* -----------------------------------------
     1. Attributes
    ------------------------------------------*/

    /**
     * The two types with columns of their own; every other one carries just
     * `value`. Nothing compares against these directly — go through
     * extrasFor(), which is the one predicate the rules, the controller and the
     * form all read.
     */
    public const ADDRESS_TYPE = 'address';

    public const SOCIAL_TYPE = 'social';

    /**
     * Stored as locale => value JSON. The trait casts these, so they must NOT
     * also appear in $casts.
     *
     * @var array<int, string>
     */
    public $translatable = ['title', 'address'];

    protected $guarded = ['id'];

    protected $casts = [
        'order' => 'integer',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    /* -----------------------------------------
     2. Relationships
    ------------------------------------------*/

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
     * A new detail goes on the end. Zero-based to match the positions reorder
     * writes, or the first ever detail would sit at 1 until someone dragged.
     */
    public static function nextOrder(): int
    {
        $last = static::query()->max('order');

        return $last === null ? 0 : (int) $last + 1;
    }

    /**
     * The type registry in picker order. The DTOs and the controller read the
     * catalogue through here rather than repeating config() in five places.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function types(): array
    {
        $types = (array) config('contacts.types', []);

        uasort($types, fn (array $a, array $b) => ($a['sort'] ?? 0) <=> ($b['sort'] ?? 0));

        return $types;
    }

    /**
     * One entry, or null when the code is not in the registry — so a caller can
     * branch on the shape without first checking the type is real.
     *
     * @return array<string, mixed>|null
     */
    public static function typeConfig(?string $type): ?array
    {
        if ($type === null) {
            return null;
        }

        return static::types()[$type] ?? null;
    }

    /**
     * Which group of extra columns this type owns — 'social' (platform),
     * 'address' (address, map_url, latitude, longitude), or null for a type
     * that carries nothing but `value`.
     *
     * ONE predicate, deliberately: the validation rules decide what is required
     * from it, the controller blanks from it, and the form renders from it (it
     * rides down on the contact-types response). When the form inferred the
     * groups from the `value` SHAPE instead, a second url-shaped type would have
     * rendered a Platform field the server then silently nulled.
     */
    public static function extrasFor(?string $type): ?string
    {
        return match ($type) {
            self::SOCIAL_TYPE => 'social',
            self::ADDRESS_TYPE => 'address',
            default => null,
        };
    }

    /**
     * The networks a social row may name, keyed by the code stored in `platform`.
     * Read through here, never config() directly: the rule and the form's picker
     * must offer the same set, and the icon shown comes off the same entry.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function platforms(): array
    {
        return (array) config('contacts.platforms', []);
    }
}
