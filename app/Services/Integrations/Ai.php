<?php

namespace App\Services\Integrations;

use App\Contracts\Integrations\GeneratesText;
use App\Models\Integration;
use RuntimeException;

/**
 * AI channel. Consistent with Email and Sms, built for prompt engineering:
 *   Ai::default()->generate('Summarise this', ['variables' => ['topic' => $t]]);
 *   Ai::for('Analysis')->chat($messages, ['json' => true, 'temperature' => 0]);
 *
 * $options (system, model, temperature, max_tokens, variables, json) are merged
 * over the integration's stored config by the driver.
 */
class Ai
{
    public function __construct(protected ?Integration $integration) {}

    public static function default(): self
    {
        return new self(Integration::activeFor('ai'));
    }

    public static function for(string $idOrName): self
    {
        return new self(Integration::query()
            ->ofType('ai')
            ->where(fn ($q) => $q->whereKey($idOrName)->orWhere('name', $idOrName))
            ->firstOrFail());
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function generate(string $input, array $options = []): string
    {
        [$driver, $config] = $this->resolve();

        return $driver->generate($input, $config, $options);
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     * @param  array<string, mixed>  $options
     */
    public function chat(array $messages, array $options = []): string
    {
        [$driver, $config] = $this->resolve();

        return $driver->chat($messages, $config, $options);
    }

    /**
     * @return array{0: GeneratesText, 1: array<string, mixed>}
     */
    protected function resolve(): array
    {
        if (! $this->integration) {
            throw new RuntimeException('No AI integration is configured.');
        }

        $driver = $this->integration->resolveDriver();

        if (! $driver instanceof GeneratesText) {
            throw new RuntimeException('The integration cannot generate text.');
        }

        return [$driver, $this->integration->config ?? []];
    }
}
