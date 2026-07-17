<?php

namespace App\Data\Integration;

use Spatie\LaravelData\Data;

/**
 * The outcome of a connection test. `message` carries the real driver error on
 * failure (never swallowed into a generic string) so the admin can act on it,
 * but must never echo a secret back.
 */
class TestResultData extends Data
{
    public function __construct(
        public bool $ok,
        public string $message,
        public ?int $latency_ms = null,
        /** @var array<string, mixed>|null */
        public ?array $details = null,
    ) {}

    public static function pass(string $message, ?int $latencyMs = null, ?array $details = null): self
    {
        return new self(ok: true, message: $message, latency_ms: $latencyMs, details: $details);
    }

    public static function fail(string $message, ?int $latencyMs = null, ?array $details = null): self
    {
        return new self(ok: false, message: $message, latency_ms: $latencyMs, details: $details);
    }
}
