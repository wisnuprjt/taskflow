<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seeds a fixed demo dataset (data/demo.json): 8 users, 29 tasks and 30 comments.
     * Fixed instead of random so `migrate --seed`, database/database.sql and the demo all show
     * the same ids, titles and accounts. Factories are still used by the tests.
     * Attachments are not seeded: their files live in storage, which is not part of the repo.
     */
    public function run(): void
    {
        $data = json_decode(file_get_contents(__DIR__.'/data/demo.json'), true, flags: JSON_THROW_ON_ERROR);

        // Explicit ids keep foreign keys valid; insert in FK order.
        foreach (['users', 'tasks', 'task_comments'] as $table) {
            DB::table($table)->insert($data[$table]);
        }
    }
}
