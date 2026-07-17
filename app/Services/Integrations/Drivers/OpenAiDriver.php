<?php

namespace App\Services\Integrations\Drivers;

use App\Contracts\Integrations\Driver;
use App\Contracts\Integrations\GeneratesText;
use App\Data\Integration\FieldData;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * OpenAI text-generation driver, built for prompt engineering: the stored
 * `system_prompt`/`model`/`temperature` are defaults, and per-call `$options`
 * (system, model, temperature, max_tokens, variables, json) override them. Thin
 * by design — a plain chat/completions call; optimise later.
 */
class OpenAiDriver implements Driver, GeneratesText
{
    protected const API_URL = 'https://api.openai.com/v1/chat/completions';

    public function code(): string
    {
        return 'openai';
    }

    public function type(): string
    {
        return 'ai';
    }

    public function label(): string
    {
        return 'OpenAI';
    }

    public function icon(): string
    {
        return 'ki-abstract-26';
    }

    /**
     * @return array<int, FieldData>
     */
    public function schema(): array
    {
        return [
            new FieldData(key: 'api_key', label: 'API key', type: 'password', required: true, secret: true),
            new FieldData(key: 'model', label: 'Model', type: 'select', default: 'gpt-4o-mini', options: [
                ['value' => 'gpt-4o-mini', 'label' => 'GPT-4o mini'],
                ['value' => 'gpt-4o', 'label' => 'GPT-4o'],
                ['value' => 'gpt-4-turbo', 'label' => 'GPT-4 Turbo'],
            ]),
            new FieldData(key: 'system_prompt', label: 'System prompt', type: 'textarea', help: 'General instructions sent as the system message. Supports :variables.'),
            new FieldData(key: 'temperature', label: 'Temperature', type: 'number', default: 0.7),
        ];
    }

    public function generate(string $input, array $config, array $options = []): string
    {
        $variables = $options['variables'] ?? [];
        $system = $this->interpolate($options['system'] ?? $config['system_prompt'] ?? '', $variables);

        $messages = [];
        if ($system !== '') {
            $messages[] = ['role' => 'system', 'content' => $system];
        }
        $messages[] = ['role' => 'user', 'content' => $this->interpolate($input, $variables)];

        return $this->chat($messages, $config, $options);
    }

    public function chat(array $messages, array $config, array $options = []): string
    {
        $apiKey = $config['api_key'] ?? null;

        if (! $apiKey) {
            throw new RuntimeException('The AI provider is not configured.');
        }

        $payload = [
            'model' => $options['model'] ?? $config['model'] ?? 'gpt-4o-mini',
            'messages' => $messages,
            'temperature' => (float) ($options['temperature'] ?? $config['temperature'] ?? 0.7),
        ];

        if (isset($options['max_tokens'])) {
            $payload['max_tokens'] = (int) $options['max_tokens'];
        }
        if (! empty($options['json'])) {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        try {
            $response = Http::withToken($apiKey)
                ->timeout((int) config('integrations.timeout', 15))
                ->post(self::API_URL, $payload);
        } catch (Throwable) {
            Log::channel('integrations')->error('OpenAI request failed: could not reach provider.');

            throw new RuntimeException('Could not reach the AI provider.');
        }

        if (! $response->successful()) {
            Log::channel('integrations')->warning('OpenAI request rejected', ['status' => $response->status()]);

            throw new RuntimeException("The AI provider returned an error (HTTP {$response->status()}).");
        }

        return (string) ($response->json('choices.0.message.content') ?? '');
    }

    /**
     * Replace :key placeholders from the variables map.
     *
     * @param  array<string, mixed>  $variables
     */
    protected function interpolate(string $text, array $variables): string
    {
        if ($variables === [] || $text === '') {
            return $text;
        }

        return preg_replace_callback('/:(\w+)/', function ($m) use ($variables) {
            return array_key_exists($m[1], $variables) ? (string) $variables[$m[1]] : $m[0];
        }, $text);
    }
}
