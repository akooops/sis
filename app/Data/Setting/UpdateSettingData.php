<?php

namespace App\Data\Setting;

use App\Models\Setting;
use App\Rules\SettingReference;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/**
 * `value` is the only writable column, so it is the only property. Name, type,
 * options and the rest are seeded metadata; a payload carrying them must not be
 * able to rewrite the catalogue, and the surest way to guarantee that is to
 * leave them off the DTO entirely.
 *
 * The rules come from the BOUND setting rather than from the payload — the row
 * declares the shape of its value, so a client cannot loosen its own validation
 * by sending a type along with the value.
 */
class UpdateSettingData extends Data
{
    public function __construct(
        public mixed $value = null,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        $setting = request()->route('setting');

        // Nothing bound (only reachable off the route, so this is belt-and-braces):
        // demand the key and let the controller's own binding be the 404.
        if (! $setting instanceof Setting) {
            return ['value' => ['present']];
        }

        $scalar = match ($setting->type) {
            'number' => ['numeric'],
            'date' => ['date'],
            // Straight off the same `options` the form renders, so the rule can
            // never reject a choice the picker offered.
            'select' => ['string', Rule::in($setting->optionValues())],
            // Not a bare exists: the id must also satisfy the setting's own
            // filter, or a slot narrowed to one kind of record would accept any.
            'model' => ['string', new SettingReference($setting)],
            default => ['string', 'max:65535'],
        };

        // `present`, not `required`: emptying a setting is a legitimate edit. A
        // multi-valued one empties to [], which is why its members stay required.
        return $setting->is_multiple
            ? ['value' => ['present', 'array'], 'value.*' => array_merge(['required'], $scalar)]
            : ['value' => array_merge(['present', 'nullable'], $scalar)];
    }
}
