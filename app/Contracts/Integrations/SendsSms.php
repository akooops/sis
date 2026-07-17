<?php

namespace App\Contracts\Integrations;

use App\Data\Integration\SendResultData;

/**
 * A driver that can send SMS. Resolved through App\Services\Integrations\Sms,
 * which binds the active (or explicitly chosen) integration's config.
 */
interface SendsSms
{
    /**
     * @param  array<int, string>  $numbers
     * @param  array<string, mixed>  $config
     */
    public function send(string $text, array $numbers, array $config): SendResultData;
}
