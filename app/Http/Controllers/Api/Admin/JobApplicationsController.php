<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\JobApplication\JobApplicationData;
use App\Http\Controllers\Api\ApiController;
use App\Models\JobApplication;
use App\Services\Jobs\CsvApplicationWriter;
use App\States\JobApplication\Called;
use App\States\JobApplication\Contacted;
use App\States\JobApplication\Hired;
use App\States\JobApplication\Rejected;
use App\States\JobApplication\Shortlisted;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\ModelStates\Exceptions\TransitionNotFound;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The HR queue: read-only, plus the five transitions.
 *
 * THERE IS NO store() AND NO update(). An application is the record of something
 * a person did — SubmitController and ApplicationProjector are the only writers —
 * and the only thing an admin changes about it is where it has reached. That is
 * why the permissions are PER TRANSITION rather than one `update`: a school wants
 * a screener who can shortlist but not reject, and a manager who can hire without
 * touching the queue.
 *
 * A disallowed transition is a 422 carrying the reason, never a 500: the state
 * machine's refusal is information the user can act on.
 */
class JobApplicationsController extends ApiController
{
    public function index(): JsonResponse
    {
        $applications = QueryBuilder::for(JobApplication::class)
            // candidateMatches, NOT `match` — match() is a method, and eager
            // loading the collection is what lets it answer without a query per row.
            ->with(['candidate', 'jobOffer', 'candidateMatches'])
            ->allowedFilters($this->filters())
            ->allowedSorts($this->sorts())
            // Nullable, and MySQL sorts NULL last on DESC, so a row the projector
            // never stamped falls to the bottom rather than heading the queue.
            ->defaultSort('-applied_at')
            ->paginate($this->perPage())
            ->appends(request()->query());

        return $this->respond(JobApplicationData::collect($applications, PaginatedDataCollection::class), 'Job applications retrieved successfully');
    }

    public function show(JobApplication $jobApplication): JsonResponse
    {
        $jobApplication->load([
            'candidate.country',
            'candidate.cv',
            'candidate.educations',
            'candidate.experiences',
            'candidate.languages',
            'candidate.skills',
            'jobOffer',
            'candidateMatches',
            'submission',
        ]);

        return $this->respond(JobApplicationData::from($jobApplication), 'Job application retrieved successfully');
    }

    /**
     * The CSV, under the same filters the list is showing.
     *
     * getEloquentBuilder() is the ONLY way to hand the filtered query on: a
     * QueryBuilder is not an Eloquent builder, and ->toBase()->getModel()
     * ->newQuery() both throws and would throw the filters away.
     */
    public function export(CsvApplicationWriter $writer): StreamedResponse
    {
        $query = QueryBuilder::for(JobApplication::class)
            ->allowedFilters($this->filters())
            ->allowedSorts($this->sorts())
            ->defaultSort('-applied_at')
            ->getEloquentBuilder();

        // Counted and audited before a byte is sent — see CsvApplicationWriter::log().
        $writer->log((clone $query)->count());

        return $writer->stream($query);
    }

    public function shortlist(JobApplication $jobApplication): JsonResponse
    {
        return $this->transition($jobApplication, Shortlisted::class, 'shortlisted', 'Application shortlisted successfully');
    }

    public function contact(JobApplication $jobApplication): JsonResponse
    {
        return $this->transition($jobApplication, Contacted::class, 'marked contacted', 'Application marked contacted');
    }

    public function call(JobApplication $jobApplication): JsonResponse
    {
        return $this->transition($jobApplication, Called::class, 'marked called', 'Application marked called');
    }

    public function hire(JobApplication $jobApplication): JsonResponse
    {
        return $this->transition($jobApplication, Hired::class, 'hired', 'Applicant marked hired');
    }

    public function reject(JobApplication $jobApplication): JsonResponse
    {
        return $this->transition($jobApplication, Rejected::class, 'rejected', 'Application rejected');
    }

    public function destroy(JobApplication $jobApplication): JsonResponse
    {
        $jobApplication->delete();

        return $this->respond(null, 'Job application deleted successfully');
    }

    /**
     * One transition, told the same way five times.
     *
     * The refusal is a 422 keyed on `status`, matching UsersController's approve/
     * reject/verify, so the client reads the server's own wording out of
     * ApiError.errors.status rather than guessing which moves were legal.
     *
     * @param  class-string<\App\States\JobApplication\JobApplicationStatus>  $target
     */
    protected function transition(JobApplication $application, string $target, string $verb, string $message): JsonResponse
    {
        try {
            $application->status->transitionTo($target);
        } catch (TransitionNotFound) {
            throw ValidationException::withMessages([
                'status' => "A {$application->status->getValue()} application cannot be {$verb}.",
            ]);
        }

        return $this->respond(
            JobApplicationData::from($application->fresh()->load(['candidate', 'jobOffer', 'candidateMatches'])),
            $message,
        );
    }

    /**
     * Shared by the list and the export, so a CSV can never contain rows the
     * list was not showing.
     *
     * @return array<int, AllowedFilter>
     */
    protected function filters(): array
    {
        return [
            // REQUIRED, not optional: JobApplicationObserver links every job
            // notification to this page with ?filter[id]=…, so dropping it would
            // silently break every one of those links.
            AllowedFilter::exact('id'),
            AllowedFilter::exact('job_offer_id'),
            AllowedFilter::exact('candidate_id'),
            AllowedFilter::exact('status'),
            $this->date('applied_from', '>=', 'startOfDay', 'applied_at'),
            $this->date('applied_to', '<=', 'endOfDay', 'applied_at'),
            $this->searchApplicants(),
            $this->unscored(),
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function sorts(): array
    {
        // No `score`: it lives on candidate_matches, and sorting by it would need
        // a LEFT JOIN that makes `id` ambiguous and breaks the export's keyset walk.
        return ['id', 'status', 'applied_at', 'created_at'];
    }

    /**
     * Search by the applicant, which is the only thing anyone looks a queue row
     * up by — plus the id, so pasting one from a notification finds it.
     *
     * concat_ws is load-bearing: the table's Applicant column renders `full_name`,
     * an APPENDED ACCESSOR with no column behind it, so "Ahmed Ali" matches
     * neither first_name nor last_name alone and searching for what is on screen
     * would return nothing.
     */
    protected function searchApplicants(): AllowedFilter
    {
        return AllowedFilter::callback('search', function ($query, $value) {
            $query->where(function ($query) use ($value) {
                $query->where('job_applications.id', 'like', "%{$value}%")
                    ->orWhereHas('candidate', function ($candidate) use ($value) {
                        $candidate->where(function ($candidate) use ($value) {
                            $candidate->where('first_name', 'like', "%{$value}%")
                                ->orWhere('last_name', 'like', "%{$value}%")
                                ->orWhereRaw("concat_ws(' ', first_name, last_name) like ?", ["%{$value}%"])
                                ->orWhere('email', 'like', "%{$value}%")
                                ->orWhere('phone', 'like', "%{$value}%");
                        });
                    });
            });
        });
    }

    /**
     * "Has the scorer reached this application yet?"
     *
     * UNSCORED MEANS NO SCORED ROW, not a row whose score is null — a candidate the
     * queue has never reached has no candidate_matches row AT ALL, and a filter
     * that only looked for `score is null` would miss every one of them.
     * whereNotExists over the pair covers both cases in one predicate, and
     * unique(candidate_id, job_offer_id) makes each probe a point read.
     *
     * This is the only score question worth asking here. A score RANGE would be
     * the same subquery but could not pair with a sort, and with no AI provider
     * configured it would be a slider over an empty axis.
     */
    protected function unscored(): AllowedFilter
    {
        return AllowedFilter::callback('unscored', function ($query, $value) {
            $scored = fn ($sub) => $sub->selectRaw('1')
                ->from('candidate_matches')
                ->whereColumn('candidate_matches.candidate_id', 'job_applications.candidate_id')
                ->whereColumn('candidate_matches.job_offer_id', 'job_applications.job_offer_id')
                ->whereNotNull('candidate_matches.score');

            // The Filters drawer sends '1'/'0' as strings; a bare cast reads '0'
            // as true and the filter silently does the opposite of what was asked.
            filter_var($value, FILTER_VALIDATE_BOOLEAN)
                ? $query->whereNotExists($scored)
                : $query->whereExists($scored);
        });
    }
}
