<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create(
            [
                'name' => 'John Doe',
                'email' => 'test@example.com',
                'password' => 'password',
            ]
        );

        User::factory()->count(9)->create();
    }
}
