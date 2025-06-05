<?php

namespace Database\Seeders;

use App\Models\Kategori;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        $this->call([
            KategoriSeeder::class,
            MerekSeeder::class,
            ProdukSeeder::class,
            PelangganSeeder::class,
            VerifiedUserSeeder::class,
            TransaksiSeeder::class,

        ]);
    }
}
