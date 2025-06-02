<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProdukSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('produks')->insert([
            [
                'nama_produk'   => 'Kopi Hitam',
                'id_kategori'   => 1, // pastikan id_kategori ini ada
                'id_merek'      => 1, // pastikan id_merek ini ada
                'harga_modal'   => 50000,
                'harga_jual'    => 100000,
                'jumlah_stok'   => 50,
                'gambar_produk' => 'https://via.placeholder.com/100',
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
        ]);
    }
}
