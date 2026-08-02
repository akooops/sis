<?php

namespace App\Services\Forms;

use App\Models\FormWebhook;
use Closure;
use Illuminate\Validation\Rule;

/**
 * The rules a webhook write must pass and the shapes it is stored in — used by
 * StoreFormWebhookData, UpdateFormWebhookData and FormWebhooksController.
 *
 * They live together on purpose: the validator's "this secret may be blank" and
 * the controller's "a blank secret keeps the stored one" are the SAME decision,
 * and stating it twice is how the two drift into a form that validates and then
 * wipes a working token. keptAuth() is where both sides read that decision, so
 * splitting the rules off from the folding would put a class boundary through
 * one invariant — Registry keeps rulesFor() and applyValues() together for the
 * same reason, and this mirrors it.
 *
 * The write-side counterpart to WebhookPayload: Payload is what one delivery
 * SENDS, this is what an admin may write and what is kept. There is nothing
 * here about the BODY: it is fixed (see WebhookPayload), so a write only ever
 * decides where to send it and how to authenticate.
 *
 * Static because it holds nothing — every input arrives as an argument, so
 * there is no resolved map or file read to memoise the way the three bound
 * singletons have. spatie/laravel-data resolves rules()/attributes() through
 * app()->call(), so an instance WOULD be injectable; it would just be a
 * container round trip for a class with no state to fetch. This is also not a
 * trait: a trait earns its place when the consuming class must answer to a name
 * someone else declared (prepareForPipeline, see NormalizesPhones) — nothing
 * here is declared or called by spatie, so it was only ever a namespace.
 */
class WebhookConfig
{
    /**
     * Every rule a write must pass: the columns, plus the auth fields the
     * submitted type needs.
     *
     * The two halves are merged here rather than by each caller because neither
     * is ever wanted alone, and their keys are disjoint so the order cannot
     * matter.
     *
     * @param  FormWebhook|null  $webhook  the row being updated; null on create
     * @param  array<string, mixed>  $payload
     * @return array<string, array<int, mixed>>
     */
    public static function rules(?FormWebhook $webhook, array $payload): array
    {
        return array_merge(self::baseRules(), self::authRules($webhook, $payload));
    }

    /**
     * Readable names for the nested keys, for the Data classes' attributes()
     * hook — this is not one itself.
     *
     * The per-row header ones are emitted from the payload rather than declared
     * with a wildcard, because the rules themselves are per index (whether a
     * value may be blank depends on what is stored under that header's name).
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, string>
     */
    public static function attributes(array $payload): array
    {
        $attributes = [
            'auth.token' => 'token',
            'auth.headers' => 'headers',
        ];

        $headers = is_array($payload['auth']['headers'] ?? null) ? $payload['auth']['headers'] : [];

        foreach (array_keys($headers) as $index) {
            $row = is_numeric($index) ? ((int) $index) + 1 : $index;

            $attributes["auth.headers.{$index}.key"] = "header {$row} name";
            $attributes["auth.headers.{$index}.value"] = "header {$row} value";
        }

        return $attributes;
    }

    /**
     * Fold the submitted auth values into what gets stored, mirroring
     * Registry::applyValues(): a non-empty value overwrites, a blank one leaves
     * the stored value alone, and there is nothing to leave alone on create.
     *
     * @param  array<string, mixed>  $submitted
     * @return array<string, mixed>|null null for `none`, so the column is cleared
     */
    public static function foldAuth(?FormWebhook $webhook, string $authType, array $submitted): ?array
    {
        if ($authType === 'none') {
            return null;
        }

        $kept = self::keptAuth($webhook, $authType);

        if ($authType === 'bearer') {
            $value = $submitted['token'] ?? null;

            return array_filter(
                ['token' => filled($value) ? (string) $value : ($kept['token'] ?? null)],
                fn ($v) => $v !== null,
            );
        }

        $config = [];

        foreach ($submitted['headers'] ?? [] as $row) {
            $key = is_array($row) && is_string($row['key'] ?? null) ? trim($row['key']) : '';

            if ($key === '') {
                continue;
            }

            $value = $row['value'] ?? null;
            $value = filled($value) ? (string) $value : ($kept[$key] ?? null);

            // A header dropped from the list is dropped from the config: the
            // submitted list IS the set, not a patch over it.
            if ($value !== null) {
                $config[$key] = $value;
            }
        }

        return $config;
    }

    /**
     * The columns every write sets.
     *
     * `url:http,https` and not a bare `url`: Laravel's plain rule accepts any
     * scheme, and a `file://` or `ftp://` endpoint is a request the delivery job
     * would actually try to make.
     *
     * @return array<string, array<int, mixed>>
     */
    private static function baseRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'url' => ['required', 'url:http,https', 'max:2048'],
            'method' => ['required', Rule::in(FormWebhook::METHODS)],
            'is_enabled' => ['required', 'boolean'],
            'auth_type' => ['required', Rule::in(FormWebhook::AUTH_TYPES)],
        ];
    }

    /**
     * Auth fields for the submitted type, with the secret-on-update rule from
     * Registry::applyValues(): a blank secret KEEPS the stored one, so the form
     * never has to re-ask for a token it is not allowed to show.
     *
     * The rule is per key and per type — see keptAuth() for why changing the
     * type keeps nothing.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, array<int, mixed>>
     */
    private static function authRules(?FormWebhook $webhook, array $payload): array
    {
        $authType = is_string($payload['auth_type'] ?? null) ? $payload['auth_type'] : 'none';
        $kept = self::keptAuth($webhook, $authType);

        $rules = ['auth' => ['sometimes', 'array']];

        if ($authType === 'bearer') {
            $rules['auth.token'] = self::secretRules(filled($kept['token'] ?? null));
        }

        if ($authType === 'headers') {
            // The type says "send these headers"; none of them is not a state
            // worth storing — it is auth_type none with extra steps.
            $rules['auth.headers'] = ['required', 'array', 'min:1', 'max:20', self::headerIndexRule()];
            $rules['auth.headers.*.key'] = ['required', 'string', 'max:128', 'regex:/^[A-Za-z0-9_-]+$/', 'distinct'];

            // Guarded rather than left to `??`, which only defends against a
            // missing key: a scalar posted here would reach foreach and raise a
            // PHP error before the `array` rule above ever got to report it.
            $headers = is_array($payload['auth']['headers'] ?? null) ? $payload['auth']['headers'] : [];

            foreach ($headers as $index => $row) {
                $key = is_array($row) && is_string($row['key'] ?? null) ? $row['key'] : null;

                $rules["auth.headers.{$index}.value"] = self::secretRules(
                    $key !== null && filled($kept[$key] ?? null),
                );
            }
        }

        return $rules;
    }

    /**
     * The header rows must arrive under integer indices — the shape the form
     * posts, and the only one the per-row rules above can address.
     *
     * A row's value rule is registered under a dotted path built from that row's
     * index (`auth.headers.0.value`), and neither a dotted path nor a `*`
     * wildcard can reach a key holding a dot of its own: both walk one segment
     * at a time, so `auth[headers][0.x][value]` registers a rule the validator
     * then silently skips. On update the skipped rule is the LENIENT one — blank
     * is allowed precisely because a secret IS stored under that header name —
     * so the row would fold an unvalidated value straight over a live secret and
     * answer 200. Refused here rather than patched rule by rule, because "every
     * row is addressable" is what the whole per-row branch rests on.
     */
    private static function headerIndexRule(): Closure
    {
        return function (string $attribute, $value, Closure $fail): void {
            if (! is_array($value)) {
                return; // `array` above already reports this
            }

            foreach (array_keys($value) as $index) {
                if (! is_int($index)) {
                    $fail('The :attribute must be sent as a list.');

                    return;
                }
            }
        };
    }

    /**
     * The stored auth config a write is allowed to keep.
     *
     * NOTHING is kept when the auth type changes. The config is one flat array
     * and `headers` sends every key in it as a header — so carrying a bearer
     * `token` across a switch to `headers` would put the token on the wire as a
     * header literally named "token".
     *
     * @return array<string, mixed>
     */
    private static function keptAuth(?FormWebhook $webhook, string $authType): array
    {
        return $webhook && $webhook->auth_type === $authType
            ? ($webhook->auth_config ?? [])
            : [];
    }

    /**
     * Required when there is nothing stored to fall back on, optional when there
     * is. `sometimes` + `nullable` is what lets the form post an empty box.
     *
     * @return array<int, string>
     */
    private static function secretRules(bool $hasStored): array
    {
        return $hasStored
            ? ['sometimes', 'nullable', 'string', 'max:2048']
            : ['required', 'string', 'max:2048'];
    }
}
