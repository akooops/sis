<?php

namespace App\Data\Integration;

use Spatie\LaravelData\Data;

/** Outcome of a captcha verification. `score` is v3 only (0 = bot, 1 = human). */
class CaptchaResultData extends Data
{
    public function __construct(
        public bool $ok,
        /**
         * True when the provider could not be reached / answered malformed — a
         * transient condition the visitor should retry, NOT a failed challenge.
         */
        public bool $unavailable = false,
        public ?float $score = null,
    ) {}

    public static function passed(?float $score = null): self
    {
        return new self(ok: true, score: $score);
    }

    /** $score is passed when v3 answered but scored below the threshold. */
    public static function failed(?float $score = null): self
    {
        return new self(ok: false, score: $score);
    }

    public static function unavailable(): self
    {
        return new self(ok: false, unavailable: true);
    }
}
