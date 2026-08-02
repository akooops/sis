<?php

namespace App\Data\Form;

/**
 * `forms.custom_css` is the only admin string in this module that reaches a
 * public page as CODE — it is written straight into a <style> block, where
 * escaping it would break the very thing it is for. So it is cleaned on the way
 * IN instead, once, by both the create and the update payload.
 *
 * The one that actually matters is `</style`: a stylesheet ends at the first
 * closing tag the parser sees, so an admin who types one is no longer writing
 * CSS — everything after it is markup on the page, `<script>` included. The rest
 * (`<script`, `expression(`, `javascript:`, `@import`) close the older routes to
 * the same result: IE's expression(), a url(javascript:…) value, and an @import
 * pulling a third-party sheet into a page we vouch for.
 *
 * This runs in prepareForPipeline, so it happens BEFORE validation — the length
 * rule measures what will be stored, not what was typed, and nothing downstream
 * ever sees the raw string.
 */
trait SanitisesCustomCss
{
    /**
     * Case-insensitive, because `</STYLE` closes a block just as well.
     *
     * @var array<int, string>
     */
    protected static array $customCssBlocklist = [
        '</style',
        '<script',
        'expression(',
        'javascript:',
        '@import',
    ];

    /**
     * @param  array<string, mixed>  $properties
     * @return array<string, mixed>
     */
    public static function prepareForPipeline(array $properties): array
    {
        // Only touch what was sent: writing the key back would turn absent into
        // null, and on update that is the difference between "leave it" and
        // "clear it".
        if (! array_key_exists('custom_css', $properties)) {
            return $properties;
        }

        $value = $properties['custom_css'];

        if (! is_string($value)) {
            return $properties;
        }

        $properties['custom_css'] = static::stripCustomCss($value);

        return $properties;
    }

    /**
     * Strip until the string stops changing.
     *
     * One pass is not enough, and the reason is the whole point of this method:
     * str_ireplace scans once, so removing the inner match of `<scr<scriptipt>`
     * leaves the two halves touching and spells `<script>` in the OUTPUT. The
     * same trick reassembles `</style` out of `</st</styleyle`, which is the one
     * that actually escapes the block. Repeating until a pass changes nothing
     * closes it — and it always terminates, because a pass either shortens the
     * string or ends the loop.
     */
    protected static function stripCustomCss(string $value): string
    {
        do {
            $previous = $value;
            $value = str_ireplace(static::$customCssBlocklist, '', $value);
        } while ($value !== $previous);

        return $value;
    }
}
