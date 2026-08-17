<?php

namespace App\Contracts\Integrations;

/**
 * A driver that turns text into a vector. Resolved through the Ai service.
 *
 * SEPARATE FROM GeneratesText, not folded into it, because the two capabilities
 * genuinely come apart: a provider can chat without embedding and vice versa,
 * and a driver that cannot embed should fail when asked to rather than be forced
 * to declare a method it has no answer for. Same reasoning that keeps SendsMail
 * and SendsSms apart.
 *
 * WHAT THE VECTORS ARE FOR: candidates and job offers are embedded into ONE
 * shared space, so "which pool does this posting draw from?" is answerable by
 * cosine against the same centroids the candidates were clustered into. That is
 * what bounds the match matrix — without it, every candidate is scored against
 * every open posting and each cell is an LLM call.
 */
interface GeneratesEmbeddings
{
    /**
     * Embed one or more strings, in order.
     *
     * BATCHED BY DESIGN: a nightly rebuild embeds hundreds of records, and one
     * request per record is the difference between seconds and minutes.
     *
     * `dimensions` in $options asks the provider for a SHORTER vector. This app
     * wants that: MySQL has no vector type, so these live in a JSON column and
     * every comparison runs in PHP — 256 floats keep a few thousand records
     * clusterable in seconds where the full-width vector would not.
     *
     * @param  array<int, string>  $inputs
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $options
     * @return array<int, array<int, float>> one vector per input, same order
     */
    public function embed(array $inputs, array $config, array $options = []): array;
}
