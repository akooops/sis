<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\CandidateMatch\CandidateMatchData;
use App\Http\Controllers\Api\ApiController;
use App\Models\CandidateMatch;
use App\Models\JobApplication;
use Illuminate\Http\JsonResponse;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * ONE SCORING TABLE, READ FROM BOTH ENDS.
 *
 * index() is the whole controller: `candidate-matches.index` is the only seeded
 * permission, and a match has no page of its own. The candidate drawer reads it
 * filtered by candidate ("other postings this person suits"); the job offer
 * drawer reads it filtered by job_offer ("good people we already have"). Same
 * rows, same numbers, two directions — which is exactly why there is one table
 * rather than a second one for recommendations.
 */
class CandidateMatchesController extends ApiController
{
    public function index(): JsonResponse
    {
        $matches = QueryBuilder::for(CandidateMatch::class)
            /*
             * select() FIRST, and it is not decoration: addSelect() on a builder
             * with no columns set compiles to `SELECT (subquery) AS
             * application_id` and silently drops every real column — which then
             * looks like a DTO failure rather than a query one.
             */
            ->select('candidate_matches.*')
            ->addSelect([
                // "Did they actually apply?" — the one fact this table does not
                // hold, resolved without a join so the row count is unaffected.
                'application_id' => JobApplication::query()
                    ->select('id')
                    ->whereColumn('job_applications.candidate_id', 'candidate_matches.candidate_id')
                    ->whereColumn('job_applications.job_offer_id', 'candidate_matches.job_offer_id')
                    ->limit(1),
                'application_status' => JobApplication::query()
                    ->select('status')
                    ->whereColumn('job_applications.candidate_id', 'candidate_matches.candidate_id')
                    ->whereColumn('job_applications.job_offer_id', 'candidate_matches.job_offer_id')
                    ->limit(1),
            ])
            ->with(['candidate', 'jobOffer'])
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('candidate_id'),
                AllowedFilter::exact('job_offer_id'),
                // The model's own definition of "worth showing", reused rather
                // than restated — its whereNotNull is the statement that an
                // unscored pair is not a poor fit.
                AllowedFilter::scope('strong'),
                AllowedFilter::callback('unscored', function ($query, $value) {
                    filter_var($value, FILTER_VALIDATE_BOOLEAN)
                        ? $query->whereNull('score')
                        : $query->whereNotNull('score');
                }),
                // Cheap here, unlike on applications: `score` is a real column on
                // this table with indexes on (job_offer_id, score) and
                // (candidate_id, score), so filtering and sorting are both backed.
                AllowedFilter::callback('score_min', fn ($query, $value) => $query->whereNotNull('score')->where('score', '>=', (int) $value)),
                AllowedFilter::callback('score_max', fn ($query, $value) => $query->whereNotNull('score')->where('score', '<=', (int) $value)),
                AllowedFilter::callback('applied', function ($query, $value) {
                    $applied = fn ($sub) => $sub->selectRaw('1')
                        ->from('job_applications')
                        ->whereColumn('job_applications.candidate_id', 'candidate_matches.candidate_id')
                        ->whereColumn('job_applications.job_offer_id', 'candidate_matches.job_offer_id');

                    filter_var($value, FILTER_VALIDATE_BOOLEAN)
                        ? $query->whereExists($applied)
                        : $query->whereNotExists($applied);
                }),
                $this->searchRelationByColumns('candidate', ['id', 'first_name', 'last_name', 'email']),
            ])
            ->allowedSorts(['id', 'score', 'matched_at', 'created_at'])
            /*
             * MySQL puts NULL LAST on ORDER BY … DESC, so this ranks scored
             * candidates first and drops the ones the queue has not reached to the
             * bottom — which is the ordering scopeStrong implies, without hiding
             * them behind a filter nobody set.
             */
            ->defaultSort('-score')
            ->paginate($this->perPage())
            ->appends(request()->query());

        return $this->respond(CandidateMatchData::collect($matches, PaginatedDataCollection::class), 'Candidate matches retrieved successfully');
    }
}
