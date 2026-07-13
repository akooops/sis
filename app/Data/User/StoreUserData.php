<?php

namespace App\Data\User;

use App\Rules\CleanUpload;
use App\Rules\PhoneNumber;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\ValidationContext;

class StoreUserData extends Data
{
    public function __construct(
        public string $firstname,
        public string $lastname,
        public string $username,
        public string $email,
        public string $password,
        public ?string $phone,
        // Temporary upload id from the upload endpoint (see UploadService).
        public string|Optional|null $avatar,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        return [
            'firstname' => ['required', 'string', 'max:255'],
            'lastname' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->whereNull('deleted_at')],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->whereNull('deleted_at')],
            'password' => ['required', 'string', 'min:8'],
            'phone' => ['nullable', 'string', new PhoneNumber(), Rule::unique('users', 'phone')->whereNull('deleted_at')],
            'avatar' => ['sometimes', 'nullable', 'string', new CleanUpload('images')],
        ];
    }
}
