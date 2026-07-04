<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'admin',
            'email' => 'admin@mail.com',
            'password' => 'admin123',
            'role' => 'admin',
        ]);

        User::factory()->create([
            'name' => 'test',
            'email' => 'test@mail.com',
            'password' => 'password',
            'role' => 'user',
            'plan_id' => Plan::where('slug', 'basic')->first()->id,
            'pdf_count' => 2,
            'pdf_count_resets_at' => now()->addMonth(),
        ]);

        User::factory(30)->create();
    }
}
