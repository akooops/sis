<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Models\Form;
use App\Models\FormSubmission;
use App\Services\Forms\SubmissionAnalytics;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * The per-form analytics dashboard, as ONE call.
 *
 * Not an index and not paginated, so it does not go through QueryBuilder: there
 * is no row to sort or page through, only aggregates. It reads the same
 * `filter[…]` contract as everything else — filter[created_from],
 * filter[created_to], filter[status] — so a range the admin picks here means
 * what it means on the submissions list.
 *
 * One endpoint rather than one per chart because every number comes off the
 * same population: eight small grouped queries in one request beat eight
 * requests each re-deriving the same filtered set, and a dashboard whose tiles
 * arrive at different times shows two different truths at once.
 *
 * Gated by forms.show — the numbers are about one form, and anyone who may read
 * the form may read what it collected. Nothing here exposes an answer, an IP or
 * a submission id.
 */
class FormAnalyticsController extends ApiController
{
    public function __construct(protected SubmissionAnalytics $analytics) {}

    public function show(Form $form): JsonResponse
    {
        // Labels and page names come from the live form; the aggregates come
        // from SQL. Loading the structure once here is what keeps the service
        // from touching a model per field.
        $form->load(['pages.fields']);

        return $this->respond(
            $this->analytics->for($form, $this->filters()),
            'Form analytics retrieved successfully',
        );
    }

    /**
     * The range and status, validated here rather than trusted.
     *
     * An unparsable date is a 422, never a silently dropped bound — the same
     * rule ApiController::date() applies to the list endpoints, because a filter
     * the server cannot read must not quietly widen the result.
     *
     * @return array{from: ?Carbon, to: ?Carbon, status: ?string}
     */
    protected function filters(): array
    {
        $filter = (array) request()->input('filter', []);
        $status = $filter['status'] ?? null;

        if ($status !== null && $status !== '' && ! in_array($status, FormSubmission::STATUSES, true)) {
            throw ValidationException::withMessages([
                'filter.status' => 'That is not a submission status.',
            ]);
        }

        return [
            'from' => $this->boundary($filter['created_from'] ?? null, 'startOfDay'),
            'to' => $this->boundary($filter['created_to'] ?? null, 'endOfDay'),
            'status' => $status ?: null,
        ];
    }

    protected function boundary(mixed $value, string $edge): ?Carbon
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->{$edge}();
        } catch (InvalidFormatException) {
            throw ValidationException::withMessages([
                'filter.'.($edge === 'startOfDay' ? 'created_from' : 'created_to') => __('validation.date', ['attribute' => 'date']),
            ]);
        }
    }
}
