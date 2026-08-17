<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\Candidate\CandidateData;
use App\Data\Candidate\UpdateCandidateData;
use App\Http\Controllers\Api\ApiController;
use App\Models\Candidate;
use App\Models\CandidateSkill;
use App\Models\Media;
use App\States\Media\Clean;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Candidates: the PEOPLE who have applied, as distinct from the applications.
 *
 * NO store(). ApplicationProjector is the only thing that creates a candidate,
 * and there is no `candidates.store` permission to gate one with — a person
 * exists in here because they applied, never because an admin typed them in.
 *
 * update() is contact details only; see UpdateCandidateData for what it fights
 * with and why that is stated on the form rather than prevented.
 */
class CandidatesController extends ApiController
{
    public function index(): JsonResponse
    {
        $candidates = QueryBuilder::for(Candidate::class)
            // `skills` feeds the preview chips and `applications_count` is one
            // subquery. `experiences` is deliberately NOT loaded — see the
            // years_of_experience guard on CandidateData.
            ->with(['country', 'skills'])
            ->withCount('applications')
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('country_id'),
                // The drill-in from the Clusters page. A whereHas over
                // candidate_clusters, and worth it: it is the only reason a pool
                // is navigable at all.
                $this->searchRelationById('cluster_id', 'clusters'),
                AllowedFilter::callback('has_cv', function ($query, $value) {
                    // The Filters drawer sends '1'/'0' as strings, so a bare cast
                    // would read '0' as true and the filter would do nothing.
                    filter_var($value, FILTER_VALIDATE_BOOLEAN)
                        ? $query->whereNotNull('cv_media_id')
                        : $query->whereNull('cv_media_id');
                }),
                AllowedFilter::callback('embedded', function ($query, $value) {
                    filter_var($value, FILTER_VALIDATE_BOOLEAN)
                        ? $query->whereNotNull('embedding')
                        : $query->whereNull('embedding');
                }),
                AllowedFilter::callback('skill', function ($query, $value) {
                    // Matched against `fold`, which exists precisely so "IB" and
                    // "ib" are one lookup, and as a PREFIX so the
                    // unique(candidate_id, fold) index is usable.
                    $query->whereHas('skills', fn ($skills) => $skills->where('fold', 'like', CandidateSkill::fold((string) $value).'%'));
                }),
                $this->searchCandidates(),
                $this->date('created_from', '>=', 'startOfDay'),
                $this->date('created_to', '<=', 'endOfDay'),
            ])
            ->allowedSorts(['id', 'first_name', 'last_name', 'email', 'created_at', 'summarised_at', 'embedded_at'])
            ->defaultSort('-created_at')
            ->paginate($this->perPage())
            ->appends(request()->query());

        return $this->respond(CandidateData::collect($candidates, PaginatedDataCollection::class), 'Candidates retrieved successfully');
    }

    public function show(Candidate $candidate): JsonResponse
    {
        $candidate->load([
            'country',
            'cv',
            'educations',
            'experiences',
            'languages',
            'skills',
            'applications.jobOffer',
            'clusters',
        ]);

        /*
         * `matches` is deliberately NOT loaded. The drawer reads them through
         * candidate-matches.index instead, so a screener who holds candidates.show
         * but not candidate-matches.index loses one block rather than being 403'd
         * off the whole record.
         */

        return $this->respond(CandidateData::from($candidate), 'Candidate retrieved successfully');
    }

    public function update(UpdateCandidateData $data, Candidate $candidate): JsonResponse
    {
        $candidate->update($data->toArray());

        return $this->respond(CandidateData::from($candidate->fresh()->load('country')), 'Candidate updated successfully');
    }

    public function destroy(Candidate $candidate): JsonResponse
    {
        // Cascades: applications, match scores, pool memberships and all four
        // profile tables go with them. The CV file does NOT — it belongs to the
        // form submission that carried it. The confirm dialog says both.
        $candidate->delete();

        return $this->respond(null, 'Candidate deleted successfully');
    }

    /**
     * Download a candidate's CV.
     *
     * THE POINTER IS THE BINDING, READ BACKWARDS — and that is the one thing this
     * cannot copy from FormSubmissionsController::file(). There, the assertion is
     * "this media belongs to this submission". Here it is unavailable: a CV's
     * media row has model_type = FormSubmission and collection_name = answers,
     * because the file is OWNED by the submission and only POINTED AT by
     * candidates.cv_media_id. The media side knows nothing about the candidate.
     *
     * So the assertion runs the other way: this must BE the file that column
     * names. The set of media reachable through this route is therefore exactly
     * one per candidate row — enumerating ids buys nothing, and you still need a
     * live signature, a session and candidates.cv.
     *
     * Deliberately NOT also asserting model_type/collection_name: that adds no
     * security on top of the pointer and could only ever produce a false negative
     * the day an admin-side upload path exists.
     */
    public function cv(Candidate $candidate, Media $media): StreamedResponse
    {
        abort_unless($candidate->cv_media_id !== null && $candidate->cv_media_id === $media->getKey(), 404);

        // A quarantined or infected upload is never served. Checked before the
        // disk, because a pending media has no file to reach for yet.
        abort_unless($media->state instanceof Clean, 404);

        $disk = Storage::disk($media->disk);

        abort_unless($disk->exists($media->path), 404);

        // The stored name is a ULID; the applicant's own filename is what downloads.
        return $disk->download($media->path, $media->name);
    }

    /**
     * LIKE across a candidate's identifiers, plus the id.
     *
     * Not $this->search(): the list's Name column renders `full_name`, an APPENDED
     * ACCESSOR with no column behind it, so "Ahmed Ali" matches neither
     * first_name nor last_name on their own and searching for what is on screen
     * would return nothing. concat_ws is what closes that gap.
     */
    protected function searchCandidates(): AllowedFilter
    {
        return AllowedFilter::callback('search', function ($query, $value) {
            $query->where(function ($query) use ($value) {
                $query->where('id', 'like', "%{$value}%")
                    ->orWhere('first_name', 'like', "%{$value}%")
                    ->orWhere('last_name', 'like', "%{$value}%")
                    ->orWhereRaw("concat_ws(' ', first_name, last_name) like ?", ["%{$value}%"])
                    ->orWhere('email', 'like', "%{$value}%")
                    ->orWhere('phone', 'like', "%{$value}%");
            });
        });
    }
}
