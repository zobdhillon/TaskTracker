<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::with('categories')->get();

        foreach ($users as $user) {
            $categories = $user->categories;

            Task::factory()
                ->count(rand(5, 15))
                ->for($user)
                ->for($categories->random())
                ->create();

            Task::factory()
                ->count(rand(5, 15))
                ->for($user)
                ->withoutCategory()
                ->create();

            Task::factory()
                ->count(rand(5, 15))
                ->for($user)
                ->completed()
                ->create();

            Task::factory()
                ->count(rand(5, 15))
                ->for($user)
                ->overdue()
                ->create();
        }
    }
}
