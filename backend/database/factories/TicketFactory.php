<?php

namespace Database\Factories;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketFactory extends Factory
{
    public function definition(): array
    {
        $openedAt = fake()->dateTimeBetween('-6 months', 'now');

        return [
            'user_id' => User::query()
                ->inRandomOrder()
                ->value('id'),
            'assignee_id' => User::query()
                ->where('role', UserRole::GESTOR->value)
                ->inRandomOrder()
                ->value('id'),
            'title' => fake()->sentence(rand(4, 8)),
            'description' => fake()->paragraph(rand(2, 4)),
            'priority' => fake()->randomElement(TicketPriority::values()),
            'status' => fake()->randomElement(TicketStatus::values()),
            'created_at' => $openedAt,
            'updated_at' => fake()->dateTimeBetween($openedAt, 'now'),
        ];
    }

    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => fake()->randomElement(TicketStatus::openStatuses()),
        ]);
    }

    public function finished(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => fake()->randomElement([
                TicketStatus::RESOLVED->value,
                TicketStatus::CLOSED->value,
            ]),
        ]);
    }

    public function forAssignee(User|int $user): static
    {
        $userId = $user instanceof User ? $user->getKey() : $user;

        return $this->state(fn (array $attributes) => [
            'assignee_id' => $userId,
        ]);
    }

    public function createdBy(User|int $user): static
    {
        $userId = $user instanceof User ? $user->getKey() : $user;

        return $this->state(fn (array $attributes) => [
            'user_id' => $userId,
        ]);
    }

    public function unassigned(): static
    {
        return $this->state(fn (array $attributes) => [
            'assignee_id' => null,
        ]);
    }
}
