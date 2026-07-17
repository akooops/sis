<?php

namespace App\Contracts\Integrations;

/**
 * A driver that generates text from a prompt — built for prompt engineering, not
 * a one-liner. Resolved through App\Services\Integrations\Ai.
 *
 * `$options` carries the prompt-engineering knobs, merged over the integration's
 * stored config: `system` (override the system prompt), `model`, `temperature`,
 * `max_tokens`, `variables` (interpolated `:key` placeholders in the prompts),
 * `json` (force JSON output).
 */
interface GeneratesText
{
    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $options
     */
    public function generate(string $input, array $config, array $options = []): string;

    /**
     * Lower-level: full control over the message list.
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $options
     */
    public function chat(array $messages, array $config, array $options = []): string;
}
