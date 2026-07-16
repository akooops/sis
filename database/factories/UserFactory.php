<?php

namespace Database\Factories;

use App\States\User\Approved;
use App\States\User\Pending;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'firstname' => fake()->firstName(),
            'lastname' => fake()->lastName(),
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'status' => Approved::class,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * An account still waiting on an admin — it cannot sign in.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Pending::class,
        ]);
    }
}
