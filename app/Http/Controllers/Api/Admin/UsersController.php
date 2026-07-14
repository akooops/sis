<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\User\StoreUserData;
use App\Data\User\UpdateUserData;
use App\Data\User\UserData;
use App\Http\Controllers\Api\ApiController;
use App\Models\User;
use App\Services\Uploads\UploadService;
use Illuminate\Http\JsonResponse;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class UsersController extends ApiController
{
    public function index(): JsonResponse
    {
        $users = QueryBuilder::for(User::class)
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::partial('email'),
                AllowedFilter::partial('firstname'),
                AllowedFilter::partial('lastname'),
                AllowedFilter::partial('username'),
                $this->search(['firstname', 'lastname', 'username', 'email']),
                $this->relatedId('role', 'roles'),
                AllowedFilter::trashed(),
            ])
            ->allowedSorts(['id', 'firstname', 'lastname', 'username', 'email', 'created_at'])
            ->defaultSort('-created_at')
            ->allowedIncludes(['roles'])
            ->paginate($this->perPage())
            ->appends(request()->query());

        return $this->respond(UserData::collect($users, PaginatedDataCollection::class), 'Users retrieved successfully');
    }

    public function show(User $user): JsonResponse
    {
        return $this->respond(UserData::from($user->load('roles')), 'User retrieved successfully');
    }

    public function store(StoreUserData $data): JsonResponse
    {
        $user = User::create([
            'firstname' => $data->firstname,
            'lastname' => $data->lastname,
            'username' => $data->username,
            'email' => $data->email,
            'password' => $data->password,
            'phone' => $data->phone,
            'verified_at' => now(),
        ]);

        if (! $data->avatar instanceof Optional && $data->avatar) {
            UploadService::attach($data->avatar, $user, 'avatar');
        }

        return $this->respond(UserData::from($user->load('roles')), 'User created successfully', 201);
    }

    public function update(UpdateUserData $data, User $user): JsonResponse
    {
        $user->update(collect($data->toArray())->except('avatar')->all());

        if (! $data->avatar instanceof Optional && $data->avatar) {
            UploadService::attach($data->avatar, $user, 'avatar');
        }

        return $this->respond(UserData::from($user->fresh()->load('roles')), 'User updated successfully');
    }

    public function destroy(User $user): JsonResponse
    {
        $user->delete();

        return $this->respond(null, 'User deleted successfully');
    }

    public function restore(string $user): JsonResponse
    {
        $user = User::onlyTrashed()->findOrFail($user);
        $user->restore();

        return $this->respond(UserData::from($user->load('roles')), 'User restored successfully');
    }

    public function forceDestroy(string $user): JsonResponse
    {
        User::onlyTrashed()->findOrFail($user)->forceDelete();

        return $this->respond(null, 'User permanently deleted successfully');
    }

    /** Approve a pending account (e.g. one self-registered via Azure). */
    public function verify(User $user): JsonResponse
    {
        $user->forceFill(['verified_at' => now()])->save();

        return $this->respond(UserData::from($user->load('roles')), 'User approved successfully');
    }

    /** Revoke approval — the user can no longer sign in until re-approved. */
    public function unverify(User $user): JsonResponse
    {
        $user->forceFill(['verified_at' => null])->save();

        return $this->respond(UserData::from($user->load('roles')), 'User approval revoked successfully');
    }
}
