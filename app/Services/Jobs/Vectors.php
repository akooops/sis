<?php

namespace App\Services\Jobs;

/**
 * Vector arithmetic, in PHP, because MySQL has no vector type.
 *
 * That constraint is the whole reason this file is small and the vectors are
 * short: embeddings live in a JSON column, so every comparison happens here in
 * userland. At 256 dimensions a few thousand records cluster in seconds; at the
 * full 1536 the same work is minutes and the nightly rebuild stops being nightly.
 *
 * Static and dependency-free on purpose — it is arithmetic, and making it a
 * service would only add a container lookup to a loop that runs a million times.
 */
class Vectors
{
    /**
     * Cosine SIMILARITY, 1.0 for identical direction and 0.0 for unrelated.
     *
     * Not distance: every caller here wants "how alike", and flipping the sense
     * once at the source beats every call site remembering to subtract from one.
     *
     * Returns 0.0 rather than dividing by zero on an empty or all-zero vector —
     * a record with no signal is unlike everything, which is the answer that
     * keeps a rebuild running rather than throwing halfway through.
     *
     * @param  array<int, float>  $a
     * @param  array<int, float>  $b
     */
    public static function similarity(array $a, array $b): float
    {
        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        // One pass, indexed: array_map + array_sum over three closures is
        // several times slower, and this is the innermost loop of the rebuild.
        $length = min(count($a), count($b));

        for ($i = 0; $i < $length; $i++) {
            $dot += $a[$i] * $b[$i];
            $normA += $a[$i] * $a[$i];
            $normB += $b[$i] * $b[$i];
        }

        if ($normA <= 0.0 || $normB <= 0.0) {
            return 0.0;
        }

        return $dot / (sqrt($normA) * sqrt($normB));
    }

    /**
     * The element-wise mean of a set of vectors — a cluster's centroid.
     *
     * @param  array<int, array<int, float>>  $vectors
     * @return array<int, float>
     */
    public static function mean(array $vectors): array
    {
        $vectors = array_values(array_filter($vectors));

        if ($vectors === []) {
            return [];
        }

        $length = count($vectors[0]);
        $sum = array_fill(0, $length, 0.0);

        foreach ($vectors as $vector) {
            for ($i = 0; $i < $length; $i++) {
                $sum[$i] += $vector[$i] ?? 0.0;
            }
        }

        $count = count($vectors);

        return array_map(fn (float $v) => $v / $count, $sum);
    }

    /**
     * The index of the nearest centroid, or null when there are none.
     *
     * @param  array<int, float>  $vector
     * @param  array<int, array<int, float>>  $centroids
     */
    public static function nearest(array $vector, array $centroids): ?int
    {
        $best = null;
        $bestScore = -INF;

        foreach ($centroids as $index => $centroid) {
            $score = static::similarity($vector, $centroid);

            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $index;
            }
        }

        return $best;
    }

    /**
     * k-means over cosine similarity.
     *
     * SEEDED DETERMINISTICALLY, BUT SPREAD OUT — see seeds(). A random seed makes
     * two rebuilds of unchanged data produce different clusters, and cluster
     * identity has to survive a rebuild or every saved filter silently repoints.
     *
     * Stops when nothing moves or after $maxIterations, whichever comes first;
     * an empty cluster keeps its previous centroid rather than being dropped, so
     * the returned assignment array always has exactly k slots.
     *
     * @param  array<int, array<int, float>>  $vectors
     * @return array{assignments: array<int, int>, centroids: array<int, array<int, float>>}
     */
    public static function kmeans(array $vectors, int $k, int $maxIterations = 25): array
    {
        $vectors = array_values($vectors);
        $count = count($vectors);
        $k = max(1, min($k, $count));

        $centroids = static::seeds($vectors, $k);
        $assignments = array_fill(0, $count, 0);

        for ($iteration = 0; $iteration < $maxIterations; $iteration++) {
            $moved = false;

            foreach ($vectors as $i => $vector) {
                $nearest = static::nearest($vector, $centroids) ?? 0;

                if ($assignments[$i] !== $nearest) {
                    $assignments[$i] = $nearest;
                    $moved = true;
                }
            }

            foreach (range(0, $k - 1) as $cluster) {
                $members = [];

                foreach ($assignments as $i => $assigned) {
                    if ($assigned === $cluster) {
                        $members[] = $vectors[$i];
                    }
                }

                if ($members !== []) {
                    $centroids[$cluster] = static::mean($members);
                }
            }

            if (! $moved) {
                break;
            }
        }

        return ['assignments' => $assignments, 'centroids' => $centroids];
    }

    /**
     * Starting centroids: the first vector, then repeatedly the one LEAST like
     * everything chosen so far.
     *
     * THE OBVIOUS SEEDING IS WRONG HERE. Taking the first k vectors is
     * deterministic, which is what cluster identity needs — but callers order by
     * id, a ULID, which is creation order, and candidates arrive in WAVES: a
     * batch of teachers, then a batch of cleaners. The first four are then four
     * near-identical people, the centroids start on top of each other and never
     * separate, and every candidate lands in one giant pool. That is not a
     * hypothetical; it is what this did before.
     *
     * Farthest-point seeding fixes it without giving up determinism: no
     * randomness, so two rebuilds of unchanged data still agree exactly, but the
     * seeds are spread across the space instead of clumped at whatever the input
     * order happens to begin with. This is k-means++ with the random draw
     * replaced by "take the maximum", which is the standard deterministic form.
     *
     * @param  array<int, array<int, float>>  $vectors
     * @return array<int, array<int, float>>
     */
    protected static function seeds(array $vectors, int $k): array
    {
        $seeds = [$vectors[0]];

        while (count($seeds) < $k) {
            $worst = null;
            $worstScore = INF;

            foreach ($vectors as $vector) {
                // How like its NEAREST existing seed this vector is. The point we
                // want next is the one whose best match is still poor.
                $best = -INF;

                foreach ($seeds as $seed) {
                    $best = max($best, static::similarity($vector, $seed));
                }

                if ($best < $worstScore) {
                    $worstScore = $best;
                    $worst = $vector;
                }
            }

            if ($worst === null) {
                break;
            }

            $seeds[] = $worst;
        }

        return $seeds;
    }

    /**
     * How many clusters to ask for, from how many records there are.
     *
     * sqrt(n/2) is the standard rule of thumb, clamped: below 4 the pools are too
     * coarse to narrow anything, and above 20 they are too fine for a person to
     * browse — and both ends cost LLM calls at match time for no benefit.
     */
    public static function clusterCount(int $records): int
    {
        return max(4, min(20, (int) round(sqrt($records / 2))));
    }
}
