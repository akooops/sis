<?php

namespace App\Data\Integration;

use Spatie\LaravelData\Data;

/** Outcome of a mail/SMS send. `reference` is the provider's own message id. */
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
