<?php

namespace App\Data\ContactDetail;

use App\Models\ContactDetail;
use App\Models\Language;
use App\Rules\PhoneNumber;
use App\Services\Phone\PhoneFormatter;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/** Every locale at once. Errors come back keyed `title.ar`. */
class UpdateContactDetailData extends Data
{
    public function __construct(
        public string $type,
        public string $name,
        /** @var array<string, string|null> */
        public array $title,
        public ?string $value,
        /** @var array<string, string|null>|null */
        public ?array $address,
        public ?string $platform,
        public ?string $map_url,
        public ?float $latitude,
        public ?float $longitude,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        $codes = Language::enabledCodes();
        $default = Language::defaultCode();

        $type = $context->payload['type'] ?? null;

        // Which extra columns this type owns. Same predicate the controller
        // blanks by and the form renders by, so the three cannot drift.
        $extras = ContactDetail::extrasFor($type);
        $isAddress = $extras === 'address';

        // The registry names the shape of `value`, so a new type needs a line in
        // config, not a branch here.
        $shape = ContactDetail::typeConfig($type)['value'] ?? null;

        $rules = [
            'type' => ['required', 'string', Rule::in(array_keys(ContactDetail::types()))],
            'name' => ['required', 'string', 'max:255'],

            // array:en,ar also rejects unknown keys.
            'title' => ['required', 'array:'.implode(',', $codes)],

            'value' => match ($shape) {
                'phone' => ['required', 'string', new PhoneNumber],
                'email' => ['required', 'string', 'email:filter', 'max:255'],
                'url' => ['required', 'string', 'url', 'max:2048'],
                // No scalar at all (address): the controller writes null.
                default => ['nullable', 'string', 'max:255'],
            },

            'address' => $isAddress
                ? ['required', 'array:'.implode(',', $codes)]
                : ['nullable', 'array:'.implode(',', $codes)],

            // A registry code, never free text, so two rows cannot spell the same
            // network differently and the icon is always derivable.
            'platform' => [
                $extras === 'social' ? 'required' : 'nullable',
                'string',
                Rule::in(array_keys(ContactDetail::platforms())),
            ],

            'map_url' => ['nullable', 'url', 'max:2048'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];

        // Per locale, not `title.*` plus an override: merged rule sets read badly.
        foreach ($codes as $code) {
            $rules["title.{$code}"] = $code === $default
                ? ['required', 'string', 'max:255']
                : ['nullable', 'string', 'max:255'];

            // Only the address type owes a default-locale address at all.
            $rules["address.{$code}"] = $isAddress && $code === $default
                ? ['required', 'string', 'max:1000']
                : ['nullable', 'string', 'max:1000'];
        }

        return $rules;
    }

    /**
     * E164 before validation, so Rule\PhoneNumber and any later uniqueness check
     * see the one canonical form.
     *
     * NOT App\Traits\Phone\NormalizesPhones: that trait normalises a FIXED field
     * list, and `value` is a phone only for the types whose registry entry says
     * so. Running e164() over a URL is not a number that failed to parse — it is
     * not a number at all.
     *
     * @param  array<string, mixed>  $properties
     * @return array<string, mixed>
     */
    public static function prepareForPipeline(array $properties): array
    {
        $shape = ContactDetail::typeConfig($properties['type'] ?? null)['value'] ?? null;

        if ($shape !== 'phone' || ! is_string($properties['value'] ?? null)) {
            return $properties;
        }

        // Keep what was typed: an unparseable number must reach the validator.
        $properties['value'] = PhoneFormatter::e164($properties['value']) ?? $properties['value'];

        return $properties;
    }
}
