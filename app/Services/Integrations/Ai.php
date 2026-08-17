<?php

namespace App\Services\Integrations;

use App\Contracts\Integrations\GeneratesEmbeddings;
use App\Contracts\Integrations\GeneratesText;
use App\Contracts\Integrations\ReadsDocuments;
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
     * Embed one or more strings, in order.
     *
     * @param  array<int, string>  $inputs
     * @param  array<string, mixed>  $options
     * @return array<int, array<int, float>>
     */
    public function embed(array $inputs, array $options = []): array
    {
        [$driver, $config] = $this->resolve(GeneratesEmbeddings::class);

        return $driver->embed($inputs, $config, $options);
    }

    /**
     * Answer a message list that carries one document — the provider reads the
     * file itself rather than being handed text scraped out of it here.
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     * @param  array{filename: string, contents: string}  $document
     * @param  array<string, mixed>  $options
     */
    public function read(array $messages, array $document, array $options = []): string
    {
        [$driver, $config] = $this->resolve(ReadsDocuments::class);

        return $driver->read($messages, $document, $config, $options);
    }

    /**
     * The file extensions the configured provider will read.
     *
     * Asked BEFORE a file is sent, because a caller may collect more types than
     * the provider takes — the application form accepts .doc and .docx, OpenAI
     * reads PDF — and an applicant waiting on a parse should not spend a round
     * trip to be refused.
     *
     * @return array<int, string>
     */
    public function readableDocumentTypes(): array
    {
        [$driver] = $this->resolve(ReadsDocuments::class);

        return $driver->readableTypes();
    }

    /**
     * @param  class-string  $capability
     * @return array{0: mixed, 1: array<string, mixed>}
     */
    protected function resolve(string $capability = GeneratesText::class): array
    {
        if (! $this->integration) {
            throw new RuntimeException('No AI integration is configured.');
        }

        $driver = $this->integration->resolveDriver();

        if (! $driver instanceof $capability) {
            throw new RuntimeException('The configured AI integration does not support '.match ($capability) {
                GeneratesEmbeddings::class => 'embeddings',
                ReadsDocuments::class => 'reading documents',
                default => 'text generation',
            }.'.');
        }

        return [$driver, $this->integration->config ?? []];
    }
}
