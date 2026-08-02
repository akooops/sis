<?php

namespace App\Contracts\Integrations;

/**
 * A driver that hands the public page what it needs to load an analytics tag.
 *
 * DATA, NEVER MARKUP. The one method here returns a small allow-listed array —
 * a driver that returned an HTML snippet would put an admin-typed string into a
 * Blade `{!! !!}`, which is a stored-XSS hole one typo wide. The view owns the
 * markup and escapes every value it interpolates.
 *
 * It is also an ALLOW-LIST, not a config dump: `Integration::config` is
 * `encrypted:array` and `$hidden` precisely so it never reaches the wire, and
 * that guarantee would be worth nothing if this method handed the whole blob to
 * a template. Return the keys the tag needs and nothing else.
 *
 * Implementations must not throw: this runs while rendering a public form.
 */
interface ProvidesAnalytics
{
    /**
     * The public tag parameters, or [] when the integration cannot be used.
     *
     * [] is the "half-configured" answer — a missing or malformed id turns
     * tracking OFF rather than rendering a broken tag or failing the page.
     *
     * @param  array<string, mixed>  $config  the integration's decrypted config
     * @return array<string, mixed> at minimum a `provider` key the view branches on
     */
    public function client(array $config): array;
}
