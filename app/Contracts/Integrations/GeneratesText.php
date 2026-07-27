<?php

namespace App\Contracts\Integrations;

/**
 * A driver that generates text from a prompt. Resolved through the Ai service.
 *
 * `$options` are the prompt-engineering knobs, merged over the integration's
 * config: system, model, temperature, max_tokens, variables (interpolated :key
 * placeholders) and json.
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
