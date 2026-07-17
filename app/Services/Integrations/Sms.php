<?php

namespace App\Services\Integrations;

use App\Contracts\Integrations\SendsSms;
use App\Data\Integration\SendResultData;
use App\Models\Integration;

/**
 * SMS channel. Consistent with Email and Ai:
 *   Sms::default()->send('Your code is 1234', $phone);
 *   Sms::for('Marketing')->send('Sale today!', $phones);
 */
class Sms
{
    public function __construct(protected ?Integration $integration) {}

    public static function default(): self
    {
        return new self(Integration::activeFor('sms'));
    }

    public static function for(string $idOrName): self
    {
        return new self(Integration::query()
            ->ofType('sms')
            ->where(fn ($q) => $q->whereKey($idOrName)->orWhere('name', $idOrName))
            ->firstOrFail());
    }

    public function send(string $text, string|array $numbers): SendResultData
    {
        if (! $this->integration) {
            return SendResultData::error('No SMS integration is configured.');
        }

        $driver = $this->integration->resolveDriver();

        if (! $driver instanceof SendsSms) {
            return SendResultData::error('The integration cannot send SMS.');
        }

        return $driver->send($text, is_array($numbers) ? $numbers : [$numbers], $this->integration->config ?? []);
    }
}
