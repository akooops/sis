<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\UserRole\StoreUserRoleData;
use App\Data\UserRole\UserRoleData;
use App\Http\Controllers\Api\ApiController;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Http\JsonResponse;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * The roles held by one user, as pivot rows — paginated and searched through the
 * related role, not the pivot's own columns. store() is additive and idempotent:
 * re-granting a role the user already has is a no-op, and the response lists
 * only the rows actually created, so an empty 201 means nothing was new.
 * destroy() keys off the pivot's own id, not a (user, role) pair.
 */
class UserRolesController extends ApiController
{
    public function index(User $user): JsonResponse
    {
        $userRoles = QueryBuilder::for($user->userRoles()->with('role'))
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('role_id'),
                $this->searchRelationByColumns('role', ['id', 'name', 'code']),
            ])
            ->allowedIncludes(['user', 'role'])
            ->allowedSorts(['id', 'created_at'])
            ->defaultSort('-created_at')
            ->paginate($this->perPage())
            ->appends(request()->query());

        return $this->respond(UserRoleData::collect($userRoles, PaginatedDataCollection::class), 'User roles retrieved successfully');
    }

    public function store(StoreUserRoleData $data, User $user): JsonResponse
    {
        $created = collect($data->roles)
            ->map(fn (string $roleId) => $user->userRoles()->firstOrCreate(['role_id' => $roleId]))
            ->filter->wasRecentlyCreated
            ->values();

        $created->each->load('role');

        return $this->respond(UserRoleData::collect($created->all()), 'Roles assigned successfully', 201);
    }

    public function destroy(UserRole $userRole): JsonResponse
    {
        $userRole->delete();

        return $this->respond(null, 'Role removed successfully');
    }
}
