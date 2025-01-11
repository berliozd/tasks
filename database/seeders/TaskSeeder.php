<?php

namespace Database\Seeders;

use App\Models\Task;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Task::factory(5)->sequence(
            [
                'user_id' => 1,
                'created_at' => now()->subtract('day', 4),
                'updated_at' => now()->subtract('day', 3),
                'completed_at' => now()->subtract('day', 1),
                'label' => 'Task 1',
                'description' => 'Description 1',
            ],
            [
                'user_id' => 1,
                'created_at' => now()->subtract('day', 7),
                'updated_at' => now()->subtract('day', 6),
                'completed_at' => now()->subtract('day', 5),
                'label' => 'Task 2',
                'description' => 'Description 2',
            ],
            [
                'user_id' => 1,
                'created_at' => now()->subtract('day', 12),
                'updated_at' => now()->subtract('day', 10),
                'completed_at' => now()->subtract('day', 9),
                'label' => 'Task 3',
                'description' => 'Description 3',
            ],
            [
                'user_id' => 1,
                'created_at' => now()->subtract('day', 19),
                'updated_at' => now()->subtract('day', 12),
                'completed_at' => now()->subtract('day', 3),
                'label' => 'Task 4',
                'description' => 'Description 4',
            ],
            [
                'user_id' => 1,
                'created_at' => now()->subtract('day', 16),
                'updated_at' => now()->subtract('day', 15),
                'completed_at' => now()->subtract('day', 14),
                'label' => 'Task 5',
                'description' => 'Description 5',
            ]
        )->create();
    }
}
