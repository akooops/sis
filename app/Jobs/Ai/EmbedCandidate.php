<?php

namespace App\Jobs\Ai;

use App\Models\Candidate;
use App\Services\Integrations\Ai;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Turn a candidate's profile into the vector clustering runs on.
 *
 * EMBEDS THE TYPED PROFILE, NOT THE RAW SUBMISSION — the same text the scorer
 * reads. Two people who wrote their application differently but did the same work
 * must land near each other, and that only holds if both are described in the
 * app's words rather than their own.
 *
 * NO NAME, NO EMAIL, NO NATIONALITY in the embedded text. Those carry no signal
 * about what someone can do, and including them would cluster people by who they
 * are instead of by what they do — which is both wrong and the kind of wrong that
 * looks fine until someone audits it.
 */
class EmbedCandidate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public string $candidateId) {}

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [30, 120, 600];
    }

    /**
     * No injectable `Ai` parameter — see EmbedJobOffer::handle() for why an
     * optional nullable hint here is resolved into a blank Integration rather
     * than left null, and takes embeddings down with it.
     */
    public function handle(): void
    {
        $candidate = Candidate::with(['educations', 'experiences', 'languages', 'skills'])->find($this->candidateId);

        if (! $candidate) {
            return;
        }

        $text = static::describe($candidate);

        if (trim($text) === '') {
            return;
        }

        try {
            $vectors = Ai::default()->embed([$text], [
                'dimensions' => Candidate::EMBEDDING_DIMENSIONS,
            ]);
        } catch (Throwable $e) {
            Log::channel('integrations')->error('jobs.embedding-failed', [
                'candidate' => $candidate->id,
                'error' => $e->getMessage(),
            ]);

            return;
        }

        if (($vectors[0] ?? []) === []) {
            return;
        }

        // saveQuietly: a machine writing a machine's vector is not a profile edit
        // and does not belong in the audit trail. Same reasoning as the summary.
        $candidate->forceFill([
            'embedding' => $vectors[0],
            'embedded_at' => now(),
        ])->saveQuietly();
    }

    /**
     * What a candidate looks like to the embedding model.
     *
     * Public and static so RebuildClusters can build the same text for a batch
     * without instantiating a job per record — the description has to be
     * identical either way or the vectors are not comparable.
     */
    public static function describe(Candidate $candidate): string
    {
        $candidate->loadMissing(['educations', 'experiences', 'languages', 'skills']);

        $parts = [];

        foreach ($candidate->experiences as $experience) {
            $parts[] = trim("{$experience->job_title} at {$experience->company_name}. {$experience->description}");
        }

        foreach ($candidate->educations as $education) {
            $parts[] = trim("{$education->degree} {$education->field_of_study} {$education->institution}. {$education->description}");
        }

        if ($candidate->skills->isNotEmpty()) {
            $parts[] = 'Skills: '.$candidate->skills->pluck('name')->implode(', ');
        }

        if ($candidate->languages->isNotEmpty()) {
            $parts[] = 'Languages: '.$candidate->languages->map(fn ($l) => "{$l->name} {$l->proficiency}")->implode(', ');
        }

        $parts[] = $candidate->yearsOfExperience().' years of experience';

        return implode("\n", array_filter($parts, fn ($p) => trim($p) !== ''));
    }
}
