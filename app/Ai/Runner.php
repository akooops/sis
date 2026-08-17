<?php

namespace App\Ai;

use App\Services\Integrations\Ai;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Runs a Prompt through the configured provider and hands back a checked array.
 *
 * THE ONLY PLACE A MODEL'S OUTPUT BECOMES DATA. Everything downstream — the
 * scoring job, the summariser, the CV parser — receives a shape it can trust or
 * an exception, never a half-parsed string it has to interpret itself.
 *
 * Three things it does that a bare Ai::generate() call does not:
 *  - states the required JSON shape in the message, so the model is told rather
 *    than hoped at;
 *  - tolerates the fenced ```json a model wraps its answer in even when asked
 *    not to, because they all still sometimes do;
 *  - CHECKS the decoded result against schema(), so a missing or wrong-typed
 *    field is a failure here rather than a null in a column nobody notices. The
 *    previous app scraped its score out of prose with a regex and silently
 *    stored null for most applications; this is the fix for that class of bug.
 *
 * A prompt may also be run with a DOCUMENT, which the provider reads itself.
 * All three guarantees hold identically there — a file is a different way of
 * asking, not a way around the checks.
 */
class Runner
{
    /**
     * The channel, resolved on first use.
     *
     * NOT A CONSTRUCTOR ARGUMENT, and that is the same trap EmbedCandidate fell
     * into: the container fills a typed constructor parameter whether or not it
     * is nullable with a default, `Ai` is constructible, and its `?Integration`
     * auto-wires to a BLANK model. Every container-built Runner — all three Ai
     * jobs and CvParser — therefore carried an Ai pointing at no integration,
     * which SHADOWED Ai::default() and threw "Argument #1 ($code) must be of
     * type string, null given" out of Integration::resolveDriver() instead of
     * the honest "No AI integration is configured." Nothing can inject past a
     * plain property.
     */
    protected ?Ai $ai = null;

    /** Run against a named integration rather than the admin's chosen one. */
    public function using(Ai $ai): static
    {
        $this->ai = $ai;

        return $this;
    }

    /**
     * @param  array{filename: string, contents: string}|null  $document  a file for the model to READ, not text pasted into the prompt
     * @return array<string, mixed>
     */
    public function run(Prompt $prompt, ?array $document = null): array
    {
        $ai = $this->ai ?? Ai::default();

        $messages = [
            ['role' => 'system', 'content' => $prompt->system()],
            ['role' => 'user', 'content' => $prompt->user()."\n\n".$this->instruction($prompt)],
        ];

        // A document changes how the provider is ASKED and nothing else: same
        // system message, same declared shape, same checks on the way back.
        // Every prompt that carries no file takes the path it always did.
        $raw = $document === null
            ? $ai->chat($messages, $prompt->options())
            : $ai->read($messages, $document, $prompt->options());

        $decoded = $this->decode($raw);

        if ($decoded === null) {
            Log::channel('integrations')->error('ai.unparsable-response', [
                'prompt' => $prompt::class,
                // Truncated: a full response can be pages, and the first line is
                // what says whether it refused, rambled or returned prose.
                'raw' => mb_substr($raw, 0, 300),
            ]);

            throw new RuntimeException($prompt::class.' did not return JSON.');
        }

        return $this->check($prompt, $decoded);
    }

    /** What the model is told the answer must look like. */
    protected function instruction(Prompt $prompt): string
    {
        $fields = [];

        foreach ($prompt->schema() as $field => $type) {
            $fields[] = "  \"{$field}\": {$this->describe($type)}";
        }

        return "Reply with JSON only, no prose and no code fence, exactly:\n{\n".implode(",\n", $fields)."\n}";
    }

    protected function describe(string $type): string
    {
        return match ($type) {
            'int' => '<a whole number>',
            'string[]' => '["a string", …]',
            default => '"a string"',
        };
    }

    /**
     * The first JSON object in the response.
     *
     * Models still fence their output when told not to, and some prepend a
     * sentence. Rather than fail on either, this takes the outermost braces —
     * which is the answer in every case worth recovering, and null in the ones
     * that are genuinely not JSON at all.
     *
     * @return array<string, mixed>|null
     */
    protected function decode(string $raw): ?array
    {
        $trimmed = trim($raw);

        $start = strpos($trimmed, '{');
        $end = strrpos($trimmed, '}');

        if ($start === false || $end === false || $end < $start) {
            return null;
        }

        $decoded = json_decode(substr($trimmed, $start, $end - $start + 1), true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Cast and check every declared field. A missing one is fatal; an extra one
     * is dropped, because a model volunteering more than it was asked for is not
     * a reason to lose the fields that are right.
     *
     * @param  array<string, mixed>  $decoded
     * @return array<string, mixed>
     */
    protected function check(Prompt $prompt, array $decoded): array
    {
        $out = [];

        foreach ($prompt->schema() as $field => $type) {
            if (! array_key_exists($field, $decoded)) {
                throw new RuntimeException($prompt::class." omitted \"{$field}\".");
            }

            $value = $decoded[$field];

            $out[$field] = match ($type) {
                'int' => is_numeric($value) ? (int) $value : throw new RuntimeException("\"{$field}\" is not a number."),
                'string[]' => is_array($value) ? array_values(array_filter(array_map('strval', $value), 'strlen')) : [],
                default => $this->string($field, $value),
            };
        }

        return $out;
    }

    /**
     * A declared `string`, including the case where the model sent structure.
     *
     * THIS USED TO RETURN '' FOR ANYTHING NON-SCALAR, which quietly undid the
     * guarantee the class docblock makes. ParseCv declares its three repeatable
     * sections — education, experience, languages — as `string`, meaning a JSON
     * array ENCODED AS a string, and a model asked for that will sometimes send
     * the array itself instead. It is being more structured than it was asked to
     * be, not wrong. Coercing that to '' meant CvParser json_decoded '', got
     * null, dropped the key, and the applicant saw every repeatable section
     * empty — indistinguishable from a CV that listed no jobs, with no exception
     * and no log line.
     *
     * So an array or object is re-encoded into the string the schema asked for,
     * and only a value that is genuinely neither — null, a resource — is an
     * error. Failing loudly there is the point: this class exists because the
     * previous app scraped its score out of prose and silently stored null.
     */
    protected function string(string $field, mixed $value): string
    {
        if (is_scalar($value)) {
            return trim((string) $value);
        }

        if (is_array($value)) {
            return (string) json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        throw new RuntimeException("\"{$field}\" is not text.");
    }
}
