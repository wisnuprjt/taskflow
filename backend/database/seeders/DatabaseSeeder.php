<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $users = collect([
            User::factory()->admin()->create(['name' => 'Admin Demo', 'email' => 'admin@example.com']),
            User::factory()->create(['name' => 'User Demo', 'email' => 'user@example.com']),
        ])->merge(User::factory(4)->create());

        $tasks = Task::factory(20)
            ->sequence(fn () => [
                'created_by' => $users->random()->id,
                'assigned_user_id' => fake()->boolean(80) ? $users->random()->id : null,
            ])
            ->create();

        TaskComment::factory(30)
            ->sequence(fn () => [
                'task_id' => $tasks->random()->id,
                'user_id' => $users->random()->id,
            ])
            ->create();
    }
}
