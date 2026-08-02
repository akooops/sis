<?php

namespace App\Data\Form;

use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/**
 * There is no update: a block is a form and a country, so the pair is
 * add/remove. Several at once, because blocking a region is one decision.
 *
 * The duplicate check mirrors fbc_form_country_unique — re-blocking a country
 * the form already refuses is a 422 naming it, not a 500 from the database.
 */
class StoreFormBlockedCountryData extends Data
{
    public function __construct(
        public string $form_id,
        /** @var array<int, string> */
        public array $countries,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public static function rules(ValidationContext $context): array
    {
        return [
            'form_id' => ['required', 'string', Rule::exists('forms', 'id')],

            'countries' => ['required', 'array', 'min:1'],
            'countries.*' => [
                'string',
                // The unique rule below reads the DATABASE, so it cannot see a
                // country listed twice in this same payload — that pair would
                // pass validation and then hit fbc_form_country_unique.
                'distinct',
                Rule::exists('countries', 'id'),
                Rule::unique('form_blocked_countries', 'country_id')
                    ->where('form_id', $context->payload['form_id'] ?? null),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(...$args): array
    {
        return [
            'countries.*.unique' => 'That country is already blocked on this form.',
            'countries.*.exists' => 'That country no longer exists.',
        ];
    }
}
