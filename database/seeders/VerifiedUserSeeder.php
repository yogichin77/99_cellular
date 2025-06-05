<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class VerifiedUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Tambahkan user acak jika perlu
        $faker = \Faker\Factory::create('id_ID');
        for ($i = 0; $i < 5; $i++) { // 5 user acak
            User::create([
                'name' => $faker->name(),
                'email' => $faker->unique()->safeEmail(),
                'password' => Hash::make('password'),
                'role' => $faker->randomElement(['kasir', 'admin']), // Sesuaikan role yang ada
                'created_at' => now(),
                'email_verified_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
