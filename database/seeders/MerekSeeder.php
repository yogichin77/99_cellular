<?php

namespace Database\Seeders;

use App\Models\Merek; // Pastikan mengimpor model Kategori
use Illuminate\Database\Seeder;
use Faker\Factory as Faker; // Import Faker

class MerekSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('id_ID'); // Inisialisasi Faker dengan locale Indonesia

        $merekNames = [
            'iphone',
            'Xiomi',
            'Vivo',
            'Oppo',
            'Telkomsel',
            'Indosat',
            'XL',
            'Smartfren',
            'Samsung',
            'Asus',
            'Lenovo',
            'Huawei',
            'Xiaomi',
            'Realme',
            'Nokia',
            'Motorola',
            'Sony',
            'Google',
            'Oneplus',
        ];

        foreach ($merekNames as $name) {
            Merek::create([
                'nama_merek' => $name,
                'deskripsi_merek' => $faker->paragraph(3, true), // Menghasilkan 3 kalimat paragraf
            ]);
        }

        // Jika Anda sebelumnya menggunakan factory, Anda bisa mengintegrasikannya seperti ini:
        // Kategori::factory()->count(10)->create([
        //     'deskripsi_kategori' => $faker->paragraph(3, true),
        // ]);
        // Namun, jika Anda ingin kontrol lebih pada nama kategori, pendekatan foreach di atas lebih baik.
    }
}