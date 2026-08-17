<?php

namespace App\Jobs\Ai;

use App\Ai\Prompts\NameCluster;
use App\Ai\Runner;
use App\Models\Candidate;
use App\Models\CandidateCluster;
use App\Models\Cluster;
use App\Models\JobOffer;
use App\Models\JobOfferCluster;
use App\Services\Jobs\Vectors;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Rediscover the talent pools, nightly.
 *
 * The categories are not known in advance and change as the school hires, so
 * they are CLUSTERED rather than seeded: k-means over the candidate embeddings,
 * then an LLM names each result.
 *
 * IDENTITY IS CARRIED ACROSS REBUILDS, and that is the part that matters. A naive
 * rebuild deletes and recreates, so every cluster gets a new id every night —
 * which silently repoints every saved filter and every membership an admin
 * curated by hand. Instead each new centroid is matched to the nearest PREVIOUS
 * one and reuses that row; only genuinely new clusters are created.
 *
 * Below Cluster::MIN_CANDIDATES it stands down entirely. Clustering thirty people
 * into eight pools is noise dressed up as insight, and matching falls back to
 * every open posting — which is cheap at that volume anyway.
 */
class RebuildClusters implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 900;

    public function handle(Runner $runner): void
    {
        $candidates = Candidate::query()
            ->embedded()
            // Ordered so "the first k vectors" is a stable seed — see
            // Vectors::kmeans(). Two rebuilds of unchanged data must agree.
            ->orderBy('id')
            ->with(['educations', 'experiences', 'languages', 'skills'])
            ->get();

        if ($candidates->count() < Cluster::MIN_CANDIDATES) {
            Log::channel('integrations')->info('jobs.clustering-skipped', [
                'candidates' => $candidates->count(),
                'minimum' => Cluster::MIN_CANDIDATES,
            ]);

            return;
        }

        $vectors = $candidates->map(fn (Candidate $c) => $c->embedding ?? [])->all();

        $result = Vectors::kmeans($vectors, Vectors::clusterCount($candidates->count()));

        $clusters = $this->reconcile($result['centroids']);

        $this->assignCandidates($candidates, $result['assignments'], $clusters);
        $this->nameClusters($runner, $candidates, $result['assignments'], $clusters);
        $this->assignOffers($clusters);
    }

    /**
     * Match each new centroid to the nearest previous cluster, or make a new row.
     *
     * Greedy nearest-first: a previous cluster is claimed by at most one new
     * centroid, so two new pools cannot both inherit one pool's name and history.
     *
     * @param  array<int, array<int, float>>  $centroids
     * @return array<int, Cluster> keyed by the k-means cluster index
     */
    protected function reconcile(array $centroids): array
    {
        $previous = Cluster::query()->get()->keyBy('id');
        $claimed = [];
        $out = [];

        foreach ($centroids as $index => $centroid) {
            $best = null;
            $bestScore = 0.0;

            foreach ($previous as $cluster) {
                if (isset($claimed[$cluster->id]) || ! $cluster->centroid) {
                    continue;
                }

                $score = Vectors::similarity($centroid, $cluster->centroid);

                if ($score > $bestScore) {
                    $bestScore = $score;
                    $best = $cluster;
                }
            }

            /*
             * 0.6 is a deliberately LOW bar. The alternative to reusing a row is
             * orphaning it, and a pool that drifted a long way is still more
             * recognisably "the same pool" to a person than a brand new one with
             * a brand new name would be.
             */
            $cluster = $best !== null && $bestScore >= 0.6 ? $best : new Cluster(['name' => 'Unnamed pool']);

            if ($best !== null && $bestScore >= 0.6) {
                $claimed[$best->id] = true;
            }

            $cluster->forceFill(['centroid' => $centroid, 'rebuilt_at' => now()])->saveQuietly();

            $out[$index] = $cluster;
        }

        // Pools nothing mapped onto this time. Kept, not deleted: their
        // membership is rewritten below, and a hand-curated pool must survive a
        // night where the maths happened not to reproduce it.
        Cluster::query()
            ->whereNotIn('id', collect($out)->pluck('id'))
            ->update(['size' => 0]);

        return $out;
    }

    /**
     * @param  Collection<int, Candidate>  $candidates
     * @param  array<int, int>  $assignments
     * @param  array<int, Cluster>  $clusters
     */
    protected function assignCandidates(Collection $candidates, array $assignments, array $clusters): void
    {
        // Replaced wholesale: membership is derived, and a stale row would put
        // someone in a pool the maths no longer places them in.
        CandidateCluster::query()->delete();

        $sizes = [];

        foreach ($candidates as $i => $candidate) {
            $cluster = $clusters[$assignments[$i] ?? 0] ?? null;

            if (! $cluster) {
                continue;
            }

            CandidateCluster::create([
                'candidate_id' => $candidate->id,
                'cluster_id' => $cluster->id,
                'distance' => 1 - Vectors::similarity($candidate->embedding ?? [], $cluster->centroid ?? []),
            ]);

            $sizes[$cluster->id] = ($sizes[$cluster->id] ?? 0) + 1;
        }

        foreach ($clusters as $cluster) {
            $cluster->forceFill(['size' => $sizes[$cluster->id] ?? 0])->saveQuietly();
        }
    }

    /**
     * Name each pool from a sample of its members.
     *
     * A LOCKED cluster keeps its name — an admin who titled a pool by hand has
     * said something the maths does not know, and a rebuild must not overwrite it.
     * A failure leaves the previous name rather than blanking it.
     *
     * @param  Collection<int, Candidate>  $candidates
     * @param  array<int, int>  $assignments
     * @param  array<int, Cluster>  $clusters
     */
    protected function nameClusters(Runner $runner, Collection $candidates, array $assignments, array $clusters): void
    {
        foreach ($clusters as $index => $cluster) {
            if ($cluster->is_locked) {
                continue;
            }

            $samples = [];

            foreach ($candidates as $i => $candidate) {
                if (($assignments[$i] ?? null) === $index) {
                    $samples[] = EmbedCandidate::describe($candidate);
                }

                if (count($samples) >= 12) {
                    break;
                }
            }

            if ($samples === []) {
                continue;
            }

            try {
                $named = $runner->run(new NameCluster($samples));
            } catch (Throwable $e) {
                Log::channel('integrations')->error('jobs.cluster-naming-failed', [
                    'cluster' => $cluster->id,
                    'error' => $e->getMessage(),
                ]);

                continue;
            }

            $cluster->forceFill([
                'name' => $named['name'] ?: $cluster->name,
                'description' => $named['description'],
            ])->saveQuietly();
        }
    }

    /**
     * Put every open posting in its nearest pool.
     *
     * ONE SHARED SPACE: postings are embedded with the same model as candidates,
     * so "which pool does this vacancy draw from?" is a cosine against the same
     * centroids. No second clustering pass, and no separate taxonomy to keep in
     * step with the first.
     *
     * @param  array<int, Cluster>  $clusters
     */
    protected function assignOffers(array $clusters): void
    {
        JobOfferCluster::query()->delete();

        $centroids = collect($clusters)->mapWithKeys(fn (Cluster $c) => [$c->id => $c->centroid ?? []]);

        foreach (JobOffer::query()->open()->whereNotNull('embedding')->get() as $offer) {
            $best = null;
            $bestScore = -INF;

            foreach ($centroids as $clusterId => $centroid) {
                $score = Vectors::similarity($offer->embedding ?? [], $centroid);

                if ($score > $bestScore) {
                    $bestScore = $score;
                    $best = $clusterId;
                }
            }

            if ($best !== null) {
                JobOfferCluster::create([
                    'job_offer_id' => $offer->id,
                    'cluster_id' => $best,
                    'distance' => 1 - $bestScore,
                ]);
            }
        }
    }
}
