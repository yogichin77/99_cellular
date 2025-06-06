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
            'name' => 'Admin Utama',
            'email' => 'admin@example.com',
            'password' => Hash::make('12345678'), // Password standar: 'password'
            'role' => 'admin', // Sesuaikan dengan role 'admin' di sistem Anda
            'created_at' => now(),
            'email_verified_at' => now(), // Anggap sudah terverifikasi
            'updated_at' => now(),
        ]);
        User::create([
            'name' => 'Kasir Utama',
            'email' => 'kasir@example.com',
            'password' => Hash::make('12345678'), // Password standar: 'password'
            'role' => 'admin', // Sesuaikan dengan role 'admin' di sistem Anda
            'created_at' => now(),
            'email_verified_at' => now(), // Anggap sudah terverifikasi
            'updated_at' => now(),
        ]);
    }
}
