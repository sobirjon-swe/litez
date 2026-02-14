<?php

namespace Database\Factories;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\TaskType;
use App\Models\Client;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'client_id' => Client::factory(),
            'type' => fake()->randomElement(TaskType::cases()),
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'priority' => fake()->randomElement(TaskPriority::cases()),
            'status' => TaskStatus::Pending,
            'deadline' => fake()->dateTimeBetween('+1 day', '+30 days'),
            'is_recurring' => false,
        ];
    }

    public function recurring(string $type = 'daily'): static
    {
        return $this->state(fn () => [
            'is_recurring' => true,
            'recurrence_type' => $type,
        ]);
    }

    public function withReminder(int $minutes = 30, string $via = 'email'): static
    {
        return $this->state(fn () => [
            'remind_before_minutes' => $minutes,
            'remind_via' => $via,
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn () => [
            'deadline' => fake()->dateTimeBetween('-7 days', '-1 hour'),
            'status' => TaskStatus::Pending,
        ]);
    }
}
