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
        User::create([
            'name' => 'Admin Terverifikasi',
            'email' => 'admin@example.com',
            'password' => Hash::make('12345678'),
            'role' => 'admin',
            'email_verified_at' => now(), // Verifikasi otomatis
        ]);

        User::create([
            'name' => 'Kasir Terverifikasi',
            'email' => 'kasir@example.com',
            'password' => Hash::make('12345678'),
            'role' => 'kasir',
            'email_verified_at' => now(), // Verifikasi otomatis
        ]);
    }
}
