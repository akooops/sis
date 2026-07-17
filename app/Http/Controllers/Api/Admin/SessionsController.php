<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\Session\SessionData;
use App\Http\Controllers\Api\ApiController;
use App\Models\Session;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class SessionsController extends ApiController
{
    public function index(User $user): JsonResponse
    {
        $sessions = QueryBuilder::for($user->sessions())
            ->allowedFilters([
                AllowedFilter::exact('id'),
                $this->search(['id', 'ip_address', 'user_agent']),
            ])
            ->defaultSort('-last_activity')
            ->allowedSorts(['id', 'last_activity', 'ip_address', 'created_at'])
            ->paginate($this->perPage())
            ->appends(request()->query());

        return $this->respond(SessionData::collect($sessions, PaginatedDataCollection::class), 'User sessions retrieved successfully');
    }

    public function destroy(Session $session): JsonResponse
    {
        $session->delete();

        return $this->respond(null, 'Session revoked successfully');
    }
}
