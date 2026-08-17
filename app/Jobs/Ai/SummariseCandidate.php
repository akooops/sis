<?php

namespace App\Jobs\Ai;

use App\Ai\Prompts\SummariseCandidate as SummariseCandidatePrompt;
use App\Ai\Runner;
use App\Models\Candidate;
use App\Models\CandidateSkill;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Write the candidate-level summary an admin reads before opening anything.
 *
 * SEPARATE FROM SCORING, and dispatched separately, because the two fail
 * independently and mean different things: a summary that never arrives leaves a
 * blank paragraph, a score that never arrives leaves a candidate unranked. Losing
 * one must not cost the other.
 */
class SummariseCandidate implements ShouldQueue
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

    public function handle(Runner $runner): void
    {
        $candidate = Candidate::with(['educations', 'experiences', 'languages', 'skills', 'country'])
            ->find($this->candidateId);

        if (! $candidate) {
            return;
        }

        try {
            $result = $runner->run(new SummariseCandidatePrompt($candidate));
        } catch (Throwable $e) {
            Log::channel('integrations')->error('jobs.summary-failed', [
                'candidate' => $candidate->id,
                'error' => $e->getMessage(),
            ]);

            return;
        }

        $candidate->forceFill([
            'ai_comment' => $result['summary'],
            'summarised_at' => now(),
        ])->saveQuietly();

        $this->addTags($candidate, $result['tags']);
    }

    /**
     * The model's tags, added to the candidate's skills.
     *
     * ADDED, NEVER REPLACING. The skills the applicant typed are their own words
     * and are the record; these are an inference on top, and overwriting the
     * former with the latter would quietly rewrite what someone said about
     * themselves. Deduplicated on `fold`, so a tag matching a stated skill is
     * simply not added twice.
     *
     * saveQuietly on the candidate above, and no observer on skills: this is a
     * machine writing a machine's opinion, not a person editing a profile.
     *
     * @param  array<int, string>  $tags
     */
    protected function addTags(Candidate $candidate, array $tags): void
    {
        $existing = $candidate->skills()->pluck('fold')->flip();

        foreach ($tags as $tag) {
            $name = trim($tag);
            $fold = CandidateSkill::fold($name);

            if ($name === '' || $existing->has($fold)) {
                continue;
            }

            $existing[$fold] = true;

            CandidateSkill::create([
                'candidate_id' => $candidate->id,
                'name' => $name,
                'fold' => $fold,
            ]);
        }
    }
}
