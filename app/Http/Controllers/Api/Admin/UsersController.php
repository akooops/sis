<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\User\StoreUserData;
use App\Data\User\UpdateUserData;
use App\Data\User\UserData;
use App\Http\Controllers\Api\ApiController;
use App\Models\User;
use App\Services\Uploads\UploadService;
use App\States\User\Approved;
use App\States\User\Rejected;
use App\States\User\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\ModelStates\Exceptions\TransitionNotFound;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Accounts and the approval workflow that governs them (App\States\User\UserStatus).
 * Status is never part of an update payload — it moves only through
 * approve/reject/verify below, and a transition the state machine forbids comes
 * back as a 422 rather than a 500. store() writes Approved explicitly, past the
 * Pending default: an admin creating the account is the approval.
 */
class UsersController extends ApiController
{
    public function index(): JsonResponse
    {
        $users = QueryBuilder::for(User::class)
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('status'),
                $this->search(['id', 'firstname', 'lastname', 'username', 'email', 'phone']),
            ])
            ->allowedSorts(['id', 'firstname', 'lastname', 'username', 'email', 'created_at'])
            ->defaultSort('-created_at')
            ->paginate($this->perPage())
            ->appends(request()->query());

        return $this->respond(UserData::collect($users, PaginatedDataCollection::class), 'Users retrieved successfully');
    }

    public function show(User $user): JsonResponse
    {
        return $this->respond(UserData::from($user), 'User retrieved successfully');
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
            'status' => Approved::class,
        ]);

        if (! $data->avatar instanceof Optional && $data->avatar) {
            UploadService::attach($data->avatar, $user, 'avatar');
        }

        return $this->respond(UserData::from($user), 'User created successfully', 201);
    }

    public function update(UpdateUserData $data, User $user): JsonResponse
    {
        $user->update(collect($data->toArray())->except('avatar')->all());

        if (! $data->avatar instanceof Optional && $data->avatar) {
            UploadService::attach($data->avatar, $user, 'avatar');
        }

        return $this->respond(UserData::from($user->fresh()), 'User updated successfully');
    }

    /**
     * A real delete — there is no soft delete to fall back on. Role rows go with
     * it (FK cascade) and UserObserver frees the avatar back into the reusable
     * pool rather than deleting the file.
     */
    public function destroy(User $user): JsonResponse
    {
        $user->delete();

        return $this->respond(null, 'User deleted successfully');
    }

    /**
     * Let the account in — `approved` is the only status that can sign in.
     * Reachable from every other status, so a rejection is never final.
     */
    public function approve(User $user): JsonResponse
    {
        try {
            $user->status->transitionTo(Approved::class);
        } catch (TransitionNotFound) {
            throw ValidationException::withMessages([
                'status' => "A {$user->status->getValue()} user cannot be approved.",
            ]);
        }

        return $this->respond(UserData::from($user->fresh()), 'User approved successfully');
    }

    /**
     * Refuse the account. Reachable from every live status, so access can always
     * be revoked; approve() reopens it.
     */
    public function reject(User $user): JsonResponse
    {
        try {
            $user->status->transitionTo(Rejected::class);
        } catch (TransitionNotFound) {
            throw ValidationException::withMessages([
                'status' => "A {$user->status->getValue()} user cannot be rejected.",
            ]);
        }

        return $this->respond(UserData::from($user->fresh()), 'User rejected successfully');
    }

    /**
     * Confirm who they are without letting them in — what Azure SSO does on
     * self-signup, done by hand. Only a pending account can be verified.
     */
    public function verify(User $user): JsonResponse
    {
        try {
            $user->status->transitionTo(Verified::class);
        } catch (TransitionNotFound) {
            throw ValidationException::withMessages([
                'status' => "A {$user->status->getValue()} user cannot be verified.",
            ]);
        }

        return $this->respond(UserData::from($user->fresh()), 'User verified successfully');
    }
}
