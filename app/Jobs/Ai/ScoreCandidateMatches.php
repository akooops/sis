<?php

namespace App\Jobs\Ai;

use App\Ai\Prompts\ScoreCandidateForOffer;
use App\Ai\Runner;
use App\Models\Candidate;
use App\Models\CandidateMatch;
use App\Models\JobOffer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Score one candidate against the postings worth scoring them against.
 *
 * NEVER IN THE REQUEST. An applicant is thanked the moment their submission is
 * stored; the scoring happens behind that, and a provider being slow or down
 * must never be something they experience.
 *
 * WHICH POSTINGS: everything open, narrowed to the candidate's clusters ONCE
 * THEY HAVE ANY. Clusters are emergent and only rebuilt nightly, so a brand new
 * candidate has none — and scoring them against every open posting is the right
 * behaviour then, because at the volume where that is expensive, clusters exist.
 *
 * ONE JOB PER CANDIDATE, not per pair: a candidate's postings are scored
 * together so the whole set lands at once and the admin never sees a half-scored
 * list.
 */
class ScoreCandidateMatches implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(public string $candidateId) {}

    /**
     * Spread the retries: a provider that just rate-limited us will still be
     * rate-limiting us a second later.
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [30, 120, 600];
    }

    public function handle(Runner $runner): void
    {
        $candidate = Candidate::with(['educations', 'experiences', 'languages', 'skills', 'country'])
            ->find($this->candidateId);

        if (! $candidate) {
            return;
        }

        foreach ($this->postings($candidate) as $offer) {
            try {
                $result = $runner->run(new ScoreCandidateForOffer($candidate, $offer));
            } catch (Throwable $e) {
                /*
                 * ONE POSTING'S FAILURE IS NOT THE CANDIDATE'S. A model that
                 * refuses or times out on one posting must not cost the scores
                 * that already succeeded, and must not fail the job into a retry
                 * that would re-score every one of them.
                 */
                Log::channel('integrations')->error('jobs.scoring-failed', [
                    'candidate' => $candidate->id,
                    'job_offer' => $offer->id,
                    'error' => $e->getMessage(),
                ]);

                continue;
            }

            CandidateMatch::updateOrCreate(
                ['candidate_id' => $candidate->id, 'job_offer_id' => $offer->id],
                [
                    // Clamped rather than trusted: the schema guarantees a number,
                    // not that the model respected the range it was given.
                    'score' => max(0, min(100, $result['score'])),
                    'comment' => $result['comment'],
                    'matched_at' => now(),
                ],
            );
        }
    }

    /**
     * @return \Illuminate\Support\Collection<int, JobOffer>
     */
    protected function postings(Candidate $candidate): \Illuminate\Support\Collection
    {
        $clusters = $candidate->clusters()->pluck('clusters.id');

        return JobOffer::query()
            ->open()
            ->when($clusters->isNotEmpty(), fn ($query) => $query->whereHas(
                'clusters',
                fn ($inner) => $inner->whereIn('clusters.id', $clusters),
            ))
            ->get();
    }
}
