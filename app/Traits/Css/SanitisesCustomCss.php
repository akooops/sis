<?php

namespace App\Traits\Css;

/**
 * `custom_css` is admin copy that reaches a public page as CODE — it is written
 * straight into a <style> block, where escaping it would break the very thing it
 * is for. So it is cleaned on the way IN instead, once, by every create and
 * update payload that carries the column: pages, articles, albums, events,
 * achievements, job_offers, brands and forms.
 *
 * WHAT IS GUARANTEED, AND WHERE. The output provably contains no `</style` and
 * no `<script`. That is exactly enough to keep the value INSIDE a <style>
 * element and nowhere near enough anywhere else. A stylesheet ends at the first
 * closing tag the parser sees, so an admin who types one is no longer writing
 * CSS — everything after it is markup on the page; <style> is RAWTEXT, so with
 * those two gone nothing in there can start a tag at all.
 *
 * The guarantee is BOUND TO THAT CONTEXT and does not travel. `<img src=x
 * onerror=alert(1)>` passes this strip untouched — it is inert in a <style>
 * block and it is stored XSS in a text node, in a `style=""` attribute, inside
 * SVG/MathML foreign content, or in a document served as application/xhtml+xml.
 * The seven public pages that will render this column do not exist yet, so this
 * is a rule for whoever writes them: `<style>{!! $model->custom_css !!}</style>`
 * is the only supported render site. Anything else needs its own escaping and
 * cannot lean on this trait.
 *
 * WHAT IS NOT GUARANTEED. `expression(`, `javascript:` and `@import` are legacy
 * hardening — best-effort, not a boundary, and deliberately left as-is rather
 * than chased. They are cheap so they stay, but they close nothing on their own:
 * this strip is textual while CSS escapes are not, so `@imp\ort` reaches a
 * browser as `@import`; and third-party loading is not preventable here in any
 * case, because `url()` in any property does the same thing and the sibling
 * `css_url` field is a <link> to any https host BY DESIGN. Loading a
 * third-party resource from a stylesheet is something an admin is ALLOWED to do
 * here. Escaping the <style> block is the thing that is prevented. Do not widen
 * the blocklist to pretend otherwise — the entries above are already the far
 * side of the line this actually holds.
 *
 * This runs in prepareForPipeline, so it happens BEFORE validation — the length
 * rule counts what will be stored, not what was typed, and nothing downstream
 * ever sees the raw string.
 *
 * It stays a TRAIT on purpose. prepareForPipeline is spatie/laravel-data's own
 * lifecycle hook: the pipeline calls it on the Data class, so the only way to
 * take part is to be composed into that class. A service would have to be called
 * by hand from every payload, which is exactly the step someone forgets on the
 * ninth module — do not "consistently" convert this one the way ManagesWebhooks
 * was converted, because that was stateless statics and this is not.
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
     * The point past which the input cannot possibly be stored, so there is
     * nothing to gain from reading it: every DTO using this trait caps
     * custom_css at 65535 CHARACTERS and UTF-8 spends at most 4 bytes on a
     * character, so anything longer than this is a guaranteed 422.
     */
    protected static int $customCssMaxBytes = 262140;

    /**
     * @param  array<string, mixed>  $properties
     * @return array<string, mixed>
     */
    public static function prepareForPipeline(array $properties): array
    {
        // Only touch what was sent — a hook must not INVENT a key the payload
        // never carried. Note this is not an update-semantics guard: every DTO
        // declares custom_css as `?string`, so an omitted key still materialises
        // as null downstream, and these PUT endpoints are full-replacement by
        // design (css_url, category_id and the rest behave identically).
        if (! array_key_exists('custom_css', $properties)) {
            return $properties;
        }

        $value = $properties['custom_css'];

        if (! is_string($value)) {
            return $properties;
        }

        // Validation runs AFTER this hook, so the length rule cannot bound what
        // arrives here — only post_max_size can, and that is measured in
        // gigabytes. Hand an impossible string straight back and let `max` fail
        // the request; a rejected payload never reaches a model.
        if (strlen($value) > static::$customCssMaxBytes) {
            return $properties;
        }

        $properties['custom_css'] = static::stripCustomCss($value);

        return $properties;
    }

    /**
     * Remove every blocklisted token, including the ones that only APPEAR once
     * their neighbours become adjacent.
     *
     * One str_ireplace pass is not enough, and that is the whole problem: it
     * scans once, so deleting the inner match of `<scr<scriptipt>` leaves the
     * two halves touching and spells `<script>` in the OUTPUT. `</style` — the
     * one that actually escapes the block — reassembles the same way out of
     * `</st</styleyle`.
     *
     * Looping str_ireplace until a pass changes nothing does close that, but it
     * pays a full scan per nesting level, which is quadratic on a string this
     * hook sees before `max:65535` can reject it. Measured on this machine
     * before the rewrite: 112 KB of nested tokens took 2.8s and 448 KB took 72s
     * — one authenticated request pinning a worker for over a minute.
     *
     * So the input is copied a byte at a time and a token is deleted the moment
     * it ends the OUTPUT. Same fixed point in a single pass: a token surviving
     * to the end would have been matched when its last byte was appended,
     * because the output was byte-for-byte its own prefix at that moment, and
     * deletions only ever shorten it. Linear — and the bytes that cannot end a
     * token, which is nearly all of them, cost one isset().
     */
    protected static function stripCustomCss(string $value): string
    {
        // Tokens indexed by their last byte, in both cases so the hot loop does
        // not lowercase every byte it copies. Keyed by token so a token whose
        // last byte has no case (`(`, `:`) is not listed twice.
        $tokens = [];

        foreach (static::$customCssBlocklist as $token) {
            if ($token === '') {
                continue;
            }

            $last = substr($token, -1);

            $tokens[strtolower($last)][$token] = strlen($token);
            $tokens[strtoupper($last)][$token] = strlen($token);
        }

        $length = strlen($value);

        // Written in place: the kept length never overtakes the read cursor, so
        // the output can reuse the input's buffer rather than concatenating a
        // second string a byte at a time.
        $output = $value;
        $kept = 0;

        for ($i = 0; $i < $length; $i++) {
            $output[$kept++] = $value[$i];

            while ($kept > 0 && isset($tokens[$output[$kept - 1]])) {
                $removed = 0;

                foreach ($tokens[$output[$kept - 1]] as $token => $size) {
                    if ($kept >= $size && strcasecmp(substr($output, $kept - $size, $size), (string) $token) === 0) {
                        $removed = $size;
                        break;
                    }
                }

                if ($removed === 0) {
                    break;
                }

                $kept -= $removed;
            }
        }

        return substr($output, 0, $kept);
    }
}
