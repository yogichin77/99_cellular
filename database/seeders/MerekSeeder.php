<?php

namespace Database\Seeders;

use App\Models\Merek;
use Illuminate\Database\Seeder;

class MerekSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Merek::factory()->count(10)->create();
    }
}