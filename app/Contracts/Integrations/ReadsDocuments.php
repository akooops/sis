<?php

namespace App\Contracts\Integrations;

/**
 * A driver that can be handed a FILE to read, not just a string. Resolved
 * through the Ai service.
 *
 * A THIRD capability beside GeneratesText and GeneratesEmbeddings, for the same
 * reason those two are apart: a provider can chat without accepting documents,
 * and one that cannot should fail when asked rather than declare a method it has
 * no answer for.
 *
 * WHAT IT REPLACES: extracting the text ourselves. A regex over a PDF's content
 * streams returns nothing usable for most real CVs — compressed object streams,
 * CID-encoded fonts and two-column layouts each defeat it — and the caller
 * cannot tell an empty extraction from a CV with nothing in it. Sending the file
 * makes reading it the provider's problem, which is one they have solved.
 */
interface ReadsDocuments
{
    /**
     * The file extensions this provider will read: lowercase, no dot.
     *
     * PART OF THE CONTRACT, not a convenience. Providers accept a narrow set of
     * formats while a form may collect more (the application form takes .doc and
     * .docx; OpenAI reads PDF), so a caller has to be able to ask before it
     * sends rather than spend a round trip being refused.
     *
     * @return array<int, string>
     */
    public function readableTypes(): array;

    /**
     * Answer a message list that carries one document.
     *
     * The file travels as its OWN content part rather than being flattened into
     * the prompt: the provider extracts it with the page layout intact, and the
     * message stays the instruction it was written as.
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     * @param  array{filename: string, contents: string}  $document  raw bytes, not base64
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $options
     */
    public function read(array $messages, array $document, array $config, array $options = []): string;
}
