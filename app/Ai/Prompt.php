<?php

namespace App\Ai;

/**
 * One thing we ask a model to do.
 *
 * A CLASS PER PROMPT, not a string in a service. A prompt is the part of an AI
 * feature most likely to be rewritten — by someone who is tuning wording, not
 * changing behaviour — and it belongs somewhere that can be read and edited
 * without touching the job that dispatches it or the model that stores the
 * answer.
 *
 * EVERY PROMPT RETURNS STRUCTURED JSON, described by schema(). The previous
 * app's scorer asked for prose and then scraped a number out of it with a regex
 * the prompt never asked for, so it usually stored null and nobody noticed. A
 * declared shape is what makes the answer usable without guessing.
 *
 * Transport is App\Services\Integrations\Ai — the provider an admin chose in
 * settings. Nothing here knows which provider that is.
 */
abstract class Prompt
{
    /** Who the model is being asked to be. */
    abstract public function system(): string;

    /** The request itself, built from whatever this prompt takes. */
    abstract public function user(): string;

    /**
     * The JSON shape the answer must take.
     *
     * Read by Runner both to instruct the model and to check what came back, so
     * a provider that ignores the instruction still cannot write nonsense into a
     * column.
     *
     * @return array<string, string> field => 'int'|'string'|'string[]'
     */
    abstract public function schema(): array;

    /**
     * Per-prompt overrides merged over the integration's stored config.
     *
     * Temperature 0 by default: everything here is an extraction or a judgement
     * that should give the same answer twice for the same input, and a scoring
     * model that drifts between runs cannot be compared across candidates.
     *
     * @return array<string, mixed>
     */
    public function options(): array
    {
        return ['temperature' => 0, 'json' => true];
    }

    /**
     * The standing context every prompt about this school carries.
     *
     * In config rather than inlined so it can be corrected without a deploy, and
     * so there is one statement of it rather than one per prompt.
     */
    protected function school(): string
    {
        return (string) config('jobs.context');
    }

    /** A labelled block, omitted entirely when there is nothing to say. */
    protected function section(string $label, ?string $body): string
    {
        $body = trim((string) $body);

        return $body === '' ? '' : "\n\n## {$label}\n{$body}";
    }
}
