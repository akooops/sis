<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Services\Analytics\DashboardMetrics;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

/**
 * The admin dashboard, as ONE call.
 *
 * Not an index and not paginated, so it does not go through QueryBuilder: there
 * is no row to sort or page through, only aggregates. It reads the same
 * `filter[...]` contract as everything else - filter[created_from],
 * filter[created_to] - so a range picked here means what it means on the
 * submissions list.
 *
 * One endpoint rather than one per card because every number answers for the
 * same range, and a dashboard whose cards arrive at different times shows two
 * different truths at once.
 *
 * Gated by analytics.index, which is SEPARATE from dashboards.index: the latter
 * gates the page itself, so an editor still reaches their landing page without
 * necessarily being able to read the school's traffic. Each activity section is
 * then gated again, per domain, inside the service.
 */
class DashboardController extends ApiController
{
    public function __construct(protected DashboardMetrics $metrics) {}

    public function show(): JsonResponse
    {
        return $this->respond(
            $this->metrics->for($this->filters(), $this->permissionChecker()),
            'Dashboard metrics retrieved successfully',
        );
    }

    /**
     * The range, validated here rather than trusted.
     *
     * @return array{from: ?Carbon, to: ?Carbon}
     */
    protected function filters(): array
    {
        $filter = (array) request()->input('filter', []);

        return [
            'from' => $this->boundary($filter['created_from'] ?? null, 'startOfDay', 'filter.created_from'),
            'to' => $this->boundary($filter['created_to'] ?? null, 'endOfDay', 'filter.created_to'),
        ];
    }

    /**
     * How the service asks whether this caller may read a section.
     *
     * Passed in as a closure so DashboardMetrics never touches auth itself and
     * stays a pure query object. Mirrors VerifyPermissions exactly, including
     * the enable_permissions escape hatch, so a section cannot be readable
     * through this endpoint but not through its own module.
     */
    protected function permissionChecker(): callable
    {
        return function (string $code): bool {
            if (! config('app.enable_permissions')) {
                return true;
            }

            if ($user = request()->user()) {
                return $user->hasPermission($code);
            }

            $apiKey = request()->attributes->get('apiKey');

            return $apiKey ? $apiKey->hasPermission($code) : false;
        };
    }
}
