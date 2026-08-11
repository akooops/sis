<?php

namespace App\Services\Forms;

use App\Models\Form;
use App\Services\Integrations\Captcha;

/**
 * Everything a page needs to render a public form.
 *
 * A value object rather than a bag of view variables, because two very different
 * pages now render the same form: the standalone /forms/{locale}/{slug} page, and
 * the site's own /{locale}/contact and /{locale}/inquiries, which embed the
 * renderer inside the full site chrome. Both include one partial, and this is
 * what that partial reads.
 *
 * PHP 8.1: promoted readonly properties are fine; readonly classes are not.
 */
final class FormPresentation
{
    /**
     * @param  array<string, mixed>  $schema
     * @param  array<string, string>  $labels
     */
    public function __construct(
        public readonly Form $form,
        public readonly string $locale,
        public readonly array $schema,
        public readonly string $token,
        public readonly ?string $honeypot,
        public readonly Captcha $captcha,
        public readonly string $action,
        public readonly string $uploadAction,
        public readonly string $telemetryAction,
        public readonly array $labels,
    ) {}
}
