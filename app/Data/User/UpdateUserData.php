<?php

namespace App\Data\User;

use App\Rules\CleanUpload;
use App\Rules\PhoneNumber;
use App\Traits\Phone\NormalizesPhones;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\ValidationContext;

class UpdateUserData extends Data
{
    use NormalizesPhones;

    public function __construct(
        public string|Optional $firstname,
        public string|Optional $lastname,
        public string|Optional $username,
        public string|Optional $email,
        public string|Optional $password,
        public string|Optional|null $phone,
        public string|Optional|null $avatar,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        // Ignore the current user so its own name/email/phone pass the unique check.
        $user = request()->route('user');

        return [
            'firstname' => ['sometimes', 'string', 'max:255'],
            'lastname' => ['sometimes', 'string', 'max:255'],
            'username' => ['sometimes', 'string', 'max:255', Rule::unique('users', 'username')->ignore($user)],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'password' => ['sometimes', 'string', 'min:8'],
            'phone' => ['sometimes', 'nullable', 'string', new PhoneNumber, Rule::unique('users', 'phone')->ignore($user)],
            'avatar' => ['sometimes', 'nullable', 'string', new CleanUpload('images')],
        ];
    }
}
