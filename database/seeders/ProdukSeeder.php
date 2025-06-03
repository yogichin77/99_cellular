<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Produk;
use App\Models\Kategori;
use App\Models\Merek;

class ProdukSeeder extends Seeder
{
    public function run(): void
    {
        $kategoriIds = Kategori::pluck('id')->all();
        $merekIds = Merek::pluck('id')->all();

        for ($i = 1; $i <= 10; $i++) {
            Produk::create([
                'nama_produk' => 'Produk ' . $i,
                'id_kategori' => $kategoriIds[array_rand($kategoriIds)],
                'id_merek' => $merekIds[array_rand($merekIds)],
                'harga_modal' => rand(1000, 10000),
                'harga_jual' => rand(11000, 20000),
                'jumlah_stok' => rand(10, 100),
                'gambar_produk' => null,
            ]);
        }
    }
}
