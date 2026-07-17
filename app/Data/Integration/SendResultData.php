<?php

namespace App\Data\Integration;

use Spatie\LaravelData\Data;

/**
 * The outcome of a send through a communication driver (mail/SMS). `reference`
 * is the provider's own id for the message (e.g. 4jawaly's job id) when it
 * returns one.
 */
class SendResultData extends Data
{
    public function __construct(
        public bool $ok,
        public string $message,
        public ?string $reference = null,
    ) {}

    public static function ok(string $message = 'Sent', ?string $reference = null): self
    {
        return new self(ok: true, message: $message, reference: $reference);
    }

    public static function error(string $message): self
    {
        return new self(ok: false, message: $message);
    }
}
