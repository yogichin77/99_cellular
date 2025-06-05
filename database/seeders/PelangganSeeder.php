<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Pelanggan;
use Faker\Factory as Faker;

class PelangganSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('id_ID');
        $numberOfCustomers = 20; // <--- PASTIKAN ANGKA INI CUKUP BESAR

        for ($i = 0; $i < $numberOfCustomers; $i++) {
            Pelanggan::create([
                'nama_pelanggan' => $faker->name(),
                'alamat' => $faker->address(),
                'no_handphone' => $faker->phoneNumber(),
                'nama_toko' => $faker->unique()->company(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $this->command->info("{$numberOfCustomers} pelanggan telah berhasil di-seed.");
    }
}