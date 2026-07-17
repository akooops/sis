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

    public function destroy(User $user): JsonResponse
    {
        $user->delete();

        return $this->respond(null, 'User deleted successfully');
    }

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

    public function logoutDevices(User $user): JsonResponse
    {
        $revoked = 0;

        foreach ($user->sessions()->get() as $session) {
            $session->delete();
            $revoked++;
        }

        return $this->respond(['revoked' => $revoked], "Logged out of {$revoked} device(s) successfully");
    }
}
