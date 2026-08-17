<?php

namespace App\Jobs\Ai;

use App\Models\JobOffer;
use App\Services\Integrations\Ai;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Put a posting in the SAME vector space as the candidates.
 *
 * That shared space is the whole trick: RebuildClusters places each posting by
 * cosine against the centroids the candidates were clustered into, so there is
 * one taxonomy rather than two that have to be kept in step. Without this job
 * every posting has a null embedding, lands in no pool, and match narrowing
 * silently falls back to scoring everyone against everything.
 *
 * Embedded in the DEFAULT locale only. The vectors have to be comparable, and a
 * posting embedded in Arabic while the candidates were described in English
 * would sit somewhere unrelated in the space — a translation is the same job, but
 * not the same vector.
 */
class EmbedJobOffer implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public string $jobOfferId) {}

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [30, 120, 600];
    }

    /**
     * NO `?Ai $ai = null` PARAMETER, and that is not a style choice.
     *
     * Laravel resolves handle()'s dependencies through the container, and `Ai`
     * is constructible — so an optional, nullable hint is never left null: the
     * container builds one wrapping a BLANK Integration, whose `driver` is null.
     * That skipped the honest "No AI integration is configured." and threw a
     * TypeError out of Registry::driver(null) instead. Worse, it would have
     * shadowed Ai::default() once a real provider WAS configured, so embeddings
     * could never have worked. Ai::default() is the seam; it reads the admin's
     * chosen integration and nothing can inject past it.
     */
    public function handle(): void
    {
        $offer = JobOffer::find($this->jobOfferId);

        if (! $offer) {
            return;
        }

        $text = static::describe($offer);

        if (trim($text) === '') {
            return;
        }

        try {
            $vectors = Ai::default()->embed([$text], [
                // The same width as a candidate's, or cosine between them is
                // meaningless — Vectors::similarity would compare only the
                // overlapping prefix and quietly return a number anyway.
                'dimensions' => \App\Models\Candidate::EMBEDDING_DIMENSIONS,
            ]);
        } catch (Throwable $e) {
            Log::channel('integrations')->error('jobs.offer-embedding-failed', [
                'job_offer' => $offer->id,
                'error' => $e->getMessage(),
            ]);

            return;
        }

        if (($vectors[0] ?? []) === []) {
            return;
        }

        $offer->forceFill([
            'embedding' => $vectors[0],
            'embedded_at' => now(),
        ])->saveQuietly();
    }

    /**
     * The posting as the embedding model sees it.
     *
     * Deliberately the SHAPE OF A CANDIDATE: a role and a place, then the skills
     * and the requirements. A vacancy described the way its future holder would
     * describe themselves lands nearer the right people.
     */
    public static function describe(JobOffer $offer): string
    {
        $locale = \App\Models\Language::defaultCode();

        $skills = JobOffer::splitSkills($offer->getTranslation('skills', $locale, true));

        return implode("\n", array_filter([
            $offer->getTranslation('title', $locale, true) ?: $offer->name,
            $offer->employment_type.' '.$offer->work_mode,
            $offer->education_level ? 'Education required: '.$offer->education_level : null,
            $offer->experience_years ? $offer->experience_years.' years of experience required' : null,
            $skills !== [] ? 'Skills: '.implode(', ', $skills) : null,
            strip_tags((string) $offer->getTranslation('description', $locale, true)),
            strip_tags((string) $offer->getTranslation('content', $locale, true)),
        ]));
    }
}
