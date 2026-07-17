<?php

namespace App\Services\Integrations\Drivers;

use App\Contracts\Integrations\Driver;
use App\Contracts\Integrations\SendsSms;
use App\Data\Integration\FieldData;
use App\Data\Integration\SendResultData;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * 4jawaly (Saudi) SMS driver. Never logs response bodies, request headers, or
 * recipient numbers — those can echo the secret or be PII — and rethrows nothing,
 * so a provider error can't carry a credential into laravel.log.
 */
class FourJawalyDriver implements Driver, SendsSms
{
    protected const API_URL = 'https://api-sms.4jawaly.com/api/v1/account/area/sms/send';

    public function code(): string
    {
        return '4jawaly';
    }

    public function type(): string
    {
        return 'sms';
    }

    public function label(): string
    {
        return '4jawaly';
    }

    public function icon(): string
    {
        return 'ki-message-text';
    }

    /**
     * @return array<int, FieldData>
     */
    public function schema(): array
    {
        return [
            new FieldData(key: 'app_id', label: 'App ID', type: 'password', required: true, secret: true),
            new FieldData(key: 'app_secret', label: 'App secret', type: 'password', required: true, secret: true),
            new FieldData(key: 'sender_id', label: 'Sender ID', type: 'text', required: true, help: 'The approved sender name.'),
        ];
    }

    public function send(string $text, array $numbers, array $config): SendResultData
    {
        $appId = $config['app_id'] ?? null;
        $appSecret = $config['app_secret'] ?? null;
        $senderId = $config['sender_id'] ?? null;

        if (! $appId || ! $appSecret || ! $senderId) {
            return SendResultData::error('The SMS provider is not fully configured.');
        }

        $formatted = $this->formatNumbers($numbers);

        if ($formatted === []) {
            return SendResultData::error('No valid phone numbers provided.');
        }

        $appHash = base64_encode("{$appId}:{$appSecret}");
        $payload = [
            'messages' => [[
                'text' => $text,
                'numbers' => $formatted,
                'sender' => $senderId,
            ]],
        ];

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => "Basic {$appHash}",
            ])
                ->timeout((int) config('integrations.timeout', 15))
                ->post(self::API_URL, $payload);
        } catch (Throwable) {
            Log::channel('integrations')->error('4jawaly send failed: could not reach provider.');

            return SendResultData::error('Could not reach the SMS provider.');
        }

        $status = $response->status();
        $json = $response->json();
        $errText = $json['messages'][0]['err_text'] ?? null;

        if ($status === 200 && $errText === null) {
            Log::channel('integrations')->info('4jawaly send ok', ['recipients' => count($formatted)]);

            $jobId = $json['job_id'] ?? null;

            return SendResultData::ok('Message sent.', $jobId !== null ? (string) $jobId : null);
        }

        Log::channel('integrations')->warning('4jawaly send rejected', ['status' => $status]);

        return SendResultData::error($errText ?? ($json['message'] ?? "SMS sending failed (HTTP {$status})."));
    }

    /**
     * Normalize to 9665XXXXXXXX (Saudi), dropping anything unrecognizable.
     *
     * @param  array<int, string>  $numbers
     * @return array<int, string>
     */
    protected function formatNumbers(array $numbers): array
    {
        $formatted = [];

        foreach ($numbers as $number) {
            $clean = preg_replace('/[^0-9]/', '', (string) $number);

            if (strlen($clean) === 9) {
                $formatted[] = '966'.$clean;
            } elseif (strlen($clean) === 10 && str_starts_with($clean, '0')) {
                $formatted[] = '966'.substr($clean, 1);
            } elseif (strlen($clean) === 12 && str_starts_with($clean, '966')) {
                $formatted[] = $clean;
            }
        }

        return $formatted;
    }
}
