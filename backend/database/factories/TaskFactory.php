<?php

namespace Database\Factories;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => rtrim(fake()->sentence(4), '.'),
            'description' => fake()->optional(0.8)->paragraph(),
            'status' => fake()->randomElement(TaskStatus::cases()),
            'priority' => fake()->randomElement(TaskPriority::cases()),
            'assigned_user_id' => null,
            'created_by' => User::factory(),
            'due_date' => fake()->optional(0.7)->dateTimeBetween('-1 week', '+1 month'),
        ];
    }
}
