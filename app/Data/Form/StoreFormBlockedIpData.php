<?php

namespace App\Data\Form;

use App\Rules\IpOrCidr;
use App\Services\Forms\IpValue;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/**
 * There is no update: an entry is a literal address and a reason. Editing the
 * address would be adding a different block, so the pair is add/remove.
 *
 * `is_cidr` is absent on purpose — the controller derives it from the value.
 */
class StoreFormBlockedIpData extends Data
{
    public function __construct(
        public string $form_id,
        public string $value,
        public ?string $note = null,
    ) {}

    /**
     * Canonicalise BEFORE validation, the same way NormalizesPhones does.
     *
     * prepareForPipeline runs ahead of every pipe, so Rule::unique below compares
     * the canonical spelling: " 203.0.113.5 " collides with an existing
     * 203.0.113.5 instead of becoming a second row that means the same thing.
     *
     * An unparseable value is left exactly as typed so IpOrCidr reports it —
     * writing null back here would surface as "required", which tells the admin
     * nothing about the typo.
     *
     * @param  array<string, mixed>  $properties
     * @return array<string, mixed>
     */
    public static function prepareForPipeline(array $properties): array
    {
        if (! array_key_exists('value', $properties) || ! is_string($properties['value'])) {
            return $properties;
        }

        $properties['value'] = IpValue::normalise($properties['value']) ?? trim($properties['value']);

        return $properties;
    }

    public static function rules(ValidationContext $context): array
    {
        return [
            'form_id' => ['required', 'string', Rule::exists('forms', 'id')],

            // 64 is the column: an IPv6 address with a /128 suffix is 49.
            // Unique per form — the same office range may be blocked on one form
            // and welcome on another. Mirrors the fbi_form_value_unique index, so
            // a duplicate is a 422 rather than a QueryException.
            'value' => [
                'required', 'string', 'max:64', new IpOrCidr,
                Rule::unique('form_blocked_ips', 'value')
                    ->where('form_id', $context->payload['form_id'] ?? null),
            ],

            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(...$args): array
    {
        return [
            'value.unique' => 'That address or range is already blocked on this form.',
        ];
    }
}
