<?php

namespace App\Services\Integrations\Drivers;

use App\Contracts\Integrations\Driver;
use App\Contracts\Integrations\GeneratesEmbeddings;
use App\Contracts\Integrations\GeneratesText;
use App\Contracts\Integrations\ReadsDocuments;
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
class OpenAiDriver implements Driver, GeneratesEmbeddings, GeneratesText, ReadsDocuments
{
    protected const API_URL = 'https://api.openai.com/v1/chat/completions';

    protected const EMBEDDINGS_URL = 'https://api.openai.com/v1/embeddings';

    /**
     * The default embedding model.
     *
     * text-embedding-3-small supports the `dimensions` parameter, which is what
     * lets this app ask for 256 floats instead of 1536 — the difference between
     * clustering in PHP in seconds and not being able to.
     */
    protected const EMBEDDING_MODEL = 'text-embedding-3-small';

    /**
     * The endpoint a DOCUMENT call goes to, which is not the one chat uses.
     *
     * THIS IS THE WHOLE REASON A WORD CV CAN BE READ. Chat Completions takes a
     * file part, but only a PDF — a .docx is refused with "Expected file type to
     * be a supported format: .pdf but got .docx". The Responses API is where
     * OpenAI put the broad document support, and the form accepts .doc and .docx
     * as well as .pdf, so the document path posts here while generate()/chat()
     * stay on completions. Two endpoints in one driver is worth one more method.
     *
     * There WAS a period when /v1/responses listed those types and rejected them
     * anyway; that was a provider-side bug, fixed in March 2026. If Word CVs ever
     * start failing again, check the integrations log before assuming this file
     * is wrong — the fallback is silent by design and costs only the prefill.
     */
    protected const RESPONSES_URL = 'https://api.openai.com/v1/responses';

    /**
     * What OpenAI will read, mapped to the MIME type its data URI must declare.
     *
     * The three the application form accepts, plus the two neighbours that cost
     * nothing to allow. A type absent here is refused BEFORE the request rather
     * than after it: readableTypes() is what lets CvParser skip a file the
     * provider would only reject, so the applicant is not made to wait out a
     * round trip for a no.
     */
    protected const DOCUMENT_TYPES = [
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'rtf' => 'application/rtf',
        'odt' => 'application/vnd.oasis.opendocument.text',
    ];

    /**
     * The model a document call falls back to.
     *
     * Reading a PDF needs vision — OpenAI documents page images as requiring
     * "gpt-4o and later" — so an install left on gpt-4-turbo would have every CV
     * rejected by the API rather than by the field. Same shape as
     * EMBEDDING_MODEL: the capability picks the model, not the chat setting.
     */
    protected const DOCUMENT_MODEL = 'gpt-4o-mini';

    /** The models schema() offers that predate document input. */
    protected const NON_DOCUMENT_MODELS = ['gpt-4-turbo'];

    /**
     * OpenAI's documented ceiling: each file must be UNDER 50 MB, and the whole
     * request under 50 MB too.
     *
     * Measured against the BASE64, not the raw bytes, because base64 is what
     * crosses the wire and it is a third larger — checking the raw length would
     * wave through a 40 MB file that arrives as 53 MB and is refused. Unreachable
     * today (config/uploads.php caps every upload far below it), which is exactly
     * why it is written down: the day that cap is raised, this is what stops the
     * applicant waiting out a round trip for a no.
     */
    protected const MAX_DOCUMENT_BYTES = 50 * 1024 * 1024;

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
            // step: 'any' — the range is 0 to 1, so integer stepping would leave
            // exactly two legal settings. 0 must stay reachable: app/Ai/Runner
            // and every Prompt pass it explicitly for determinism.
            new FieldData(
                key: 'temperature',
                label: 'Temperature',
                type: 'number',
                default: 0.7,
                help: 'Between 0 and 1, decimals allowed: 0 answers the same prompt the same way every time, 1 varies the wording.',
                min: 0,
                max: 1,
                step: 'any',
            ),
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
        return $this->send(
            $this->payload($messages, $config, $options),
            $config,
            (int) config('integrations.timeout', 15),
        );
    }

    /**
     * @return array<int, string>
     */
    public function readableTypes(): array
    {
        return array_keys(self::DOCUMENT_TYPES);
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     * @param  array{filename: string, contents: string}  $document
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $options
     */
    public function read(array $messages, array $document, array $config, array $options = []): string
    {
        $filename = trim((string) ($document['filename'] ?? ''));
        $contents = (string) ($document['contents'] ?? '');
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $mime = self::DOCUMENT_TYPES[$extension] ?? null;

        if ($mime === null) {
            throw new RuntimeException('OpenAI does not read .'.($extension ?: '?').' files.');
        }

        if ($contents === '') {
            throw new RuntimeException('The document is empty.');
        }

        $encoded = base64_encode($contents);

        if (strlen($encoded) >= self::MAX_DOCUMENT_BYTES) {
            throw new RuntimeException('The document is larger than the '.(self::MAX_DOCUMENT_BYTES / 1024 / 1024).' MB the provider accepts.');
        }

        $payload = [
            'model' => $this->documentModel($config, $options),
            'input' => $this->input($messages, $filename, $mime, $encoded),
            'temperature' => (float) ($options['temperature'] ?? $config['temperature'] ?? 0.7),
        ];

        // Responses nests the format one level deeper than completions'
        // `response_format`. Same intent, different spelling.
        if (! empty($options['json'])) {
            $payload['text'] = ['format' => ['type' => 'json_object']];
        }

        // Longer than a chat call, like the embedding batch and for the same
        // kind of reason: the provider has to render and read every page of the
        // document before it answers, and 15 seconds regularly is not enough.
        return $this->sendDocument($payload, $config, (int) config('integrations.document_timeout', 60));
    }

    /**
     * @param  array<int, string>  $inputs
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $options
     * @return array<int, array<int, float>>
     */
    public function embed(array $inputs, array $config, array $options = []): array
    {
        $apiKey = $config['api_key'] ?? null;

        if (! $apiKey) {
            throw new RuntimeException('The AI provider is not configured.');
        }

        $inputs = array_values(array_filter(array_map('strval', $inputs), 'strlen'));

        if ($inputs === []) {
            return [];
        }

        $payload = [
            // Not the chat model: embeddings are their own family, and the
            // configured gpt-* would simply be rejected here.
            'model' => $options['embedding_model'] ?? $config['embedding_model'] ?? self::EMBEDDING_MODEL,
            'input' => $inputs,
        ];

        // Ask for a shorter vector. Supported by text-embedding-3-*; older models
        // ignore it and return full width, which still works — it is only slower.
        if (! empty($options['dimensions'])) {
            $payload['dimensions'] = (int) $options['dimensions'];
        }

        try {
            $response = Http::withToken($apiKey)
                // Longer than a chat call on purpose: this is a BATCH, and a
                // nightly rebuild sends hundreds of records at once.
                ->timeout((int) config('integrations.embedding_timeout', 60))
                ->post(self::EMBEDDINGS_URL, $payload);
        } catch (Throwable) {
            Log::channel('integrations')->error('OpenAI embedding failed: could not reach provider.');

            throw new RuntimeException('Could not reach the AI provider.');
        }

        if (! $response->successful()) {
            Log::channel('integrations')->warning('OpenAI embedding rejected', ['status' => $response->status()]);

            throw new RuntimeException("The AI provider returned an error (HTTP {$response->status()}).");
        }

        /*
         * SORTED BY `index`, not taken in response order. The API documents that
         * results may come back out of order, and a silently mismatched vector is
         * the worst possible failure here — every cluster would be wrong and
         * nothing would look broken.
         */
        $rows = collect($response->json('data') ?? [])
            ->sortBy('index')
            ->map(fn ($row) => array_map('floatval', $row['embedding'] ?? []))
            ->values()
            ->all();

        if (count($rows) !== count($inputs)) {
            throw new RuntimeException('The AI provider returned '.count($rows).' embeddings for '.count($inputs).' inputs.');
        }

        return $rows;
    }

    /**
     * The completion request body, minus the transport.
     *
     * @param  array<int, mixed>  $messages
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    protected function payload(array $messages, array $config, array $options): array
    {
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

        return $payload;
    }

    /**
     * Our `messages` list rewritten as the Responses API's `input`, with the
     * file hung off the last user turn.
     *
     * TRANSLATED, NOT REUSED: `input` takes an array of content PARTS per turn
     * where completions takes a string, the part types are `input_text` and
     * `input_file` rather than `text` and `file`, and `input_file` carries
     * `filename`/`file_data` at its own level instead of nested under a `file`
     * key. Passing a completions message list here is accepted-looking and wrong.
     *
     * `system` becomes `developer`, which is what Responses calls the same turn
     * and what it documents as taking precedence over a user instruction.
     *
     * INLINE BASE64, NOT A file_id FROM THE FILES API, and the round trip it
     * saves is the smaller half of the reason. Uploading leaves a SECOND COPY of
     * somebody's CV on the provider's storage, with its own lifetime and no
     * expiry — and nothing in this app would ever delete it, since there is no
     * job, no observer and no column that would remember it exists. An inline
     * part is read and gone. The upload path earns its keep when one file is
     * sent repeatedly; a CV is read once, while its owner waits.
     *
     * The file leads and the instruction follows, which is the order OpenAI's
     * own file-input examples use.
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     * @return array<int, mixed>
     */
    protected function input(array $messages, string $filename, string $mime, string $encoded): array
    {
        $file = [
            'type' => 'input_file',
            'filename' => $filename,
            'file_data' => 'data:'.$mime.';base64,'.$encoded,
        ];

        $input = [];
        $attached = false;

        // Backwards, so the file lands on the LAST user turn and every earlier
        // one is left alone.
        foreach (array_reverse($messages) as $message) {
            $role = ($message['role'] ?? 'user') === 'system' ? 'developer' : (string) $message['role'];
            $text = ['type' => 'input_text', 'text' => (string) ($message['content'] ?? '')];

            $parts = ! $attached && $role === 'user' ? [$file, $text] : [$text];
            $attached = $attached || $role === 'user';

            array_unshift($input, ['role' => $role, 'content' => $parts]);
        }

        // A message list with no user turn at all — nothing produces one today,
        // but the file must still travel or the call is a prompt about a
        // document that was never sent.
        if (! $attached) {
            $input[] = ['role' => 'user', 'content' => [$file]];
        }

        return $input;
    }

    /**
     * The chat model, unless it is one that cannot take a document.
     *
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $options
     */
    protected function documentModel(array $config, array $options): string
    {
        $model = (string) ($options['model'] ?? $config['model'] ?? self::DOCUMENT_MODEL);

        return in_array($model, self::NON_DOCUMENT_MODELS, true) ? self::DOCUMENT_MODEL : $model;
    }

    /**
     * POST a completion and return the message content.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $config
     */
    protected function send(array $payload, array $config, int $timeout): string
    {
        $apiKey = $config['api_key'] ?? null;

        if (! $apiKey) {
            throw new RuntimeException('The AI provider is not configured.');
        }

        try {
            $response = Http::withToken($apiKey)
                ->timeout($timeout)
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
     * POST a Responses request and return the assistant's text.
     *
     * SEPARATE FROM send() BECAUSE BOTH ENDS DIFFER — a different URL, and an
     * answer that is not at `choices.0.message.content`. Responses returns an
     * `output` LIST whose items are typed, and a reasoning model puts one or more
     * `reasoning` items in front of the message. Reading `output.0` works right
     * up until the day the configured model reasons, and then returns '' with
     * nothing logged — so this finds the message item by type and concatenates
     * its `output_text` parts.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $config
     */
    protected function sendDocument(array $payload, array $config, int $timeout): string
    {
        $apiKey = $config['api_key'] ?? null;

        if (! $apiKey) {
            throw new RuntimeException('The AI provider is not configured.');
        }

        try {
            $response = Http::withToken($apiKey)
                ->timeout($timeout)
                ->post(self::RESPONSES_URL, $payload);
        } catch (Throwable) {
            Log::channel('integrations')->error('OpenAI document request failed: could not reach provider.');

            throw new RuntimeException('Could not reach the AI provider.');
        }

        if (! $response->successful()) {
            Log::channel('integrations')->warning('OpenAI document request rejected', [
                'status' => $response->status(),
                // The body names the reason — an unsupported type, a model that
                // cannot read files, a size refusal — and without it every one of
                // them looks identical in the log.
                'error' => $response->json('error.message'),
            ]);

            throw new RuntimeException("The AI provider returned an error (HTTP {$response->status()}).");
        }

        $text = '';

        foreach ((array) $response->json('output', []) as $item) {
            if (($item['type'] ?? null) !== 'message') {
                continue;
            }

            foreach ((array) ($item['content'] ?? []) as $part) {
                if (($part['type'] ?? null) === 'output_text') {
                    $text .= (string) ($part['text'] ?? '');
                }
            }
        }

        return $text;
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
