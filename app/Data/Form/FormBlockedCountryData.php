<?php

namespace App\Data\Form;

use App\Data\Country\CountryData;
use App\Models\FormBlockedCountry;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;

/** One form → one refused country. `country` carries the name and flag_url. */
class FormBlockedCountryData extends Data
{
    public function __construct(
        public string $id,
        public string $form_id,
        public string $country_id,
        public ?string $created_at,
        public ?string $updated_at,
        public Lazy|CountryData $country,
        public Lazy|FormData $form,
    ) {}

    public static function fromModel(FormBlockedCountry $link): self
    {
        return new self(
            id: $link->id,
            form_id: $link->form_id,
            country_id: $link->country_id,
            created_at: $link->created_at?->toIso8601String(),
            updated_at: $link->updated_at?->toIso8601String(),
            country: Lazy::whenLoaded('country', $link, fn () => CountryData::from($link->country)),
            form: Lazy::whenLoaded('form', $link, fn () => FormData::from($link->form)),
        );
    }
}
