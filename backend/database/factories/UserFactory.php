<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function admin(): static
    {
        return $this->afterCreating(fn (User $user) => $user->forceFill([
            'role' => UserRole::ADMIN,
        ])->save());
    }

    public function gestor(): static
    {
        return $this->afterCreating(fn (User $user) => $user->forceFill([
            'role' => UserRole::GESTOR,
        ])->save());
    }

    public function regular(): static
    {
        return $this->afterCreating(fn (User $user) => $user->forceFill([
            'role' => UserRole::USER,
        ])->save());
    }
}
