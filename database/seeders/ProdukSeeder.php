<?php

namespace Database\Seeders;

use App\Models\Produk;
use App\Models\Kategori;
use App\Models\Merek;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Faker\Factory as Faker;

class ProdukSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('id_ID');

        $kategoriIds = Kategori::pluck('id')->all();
        $merekIds = Merek::pluck('id')->all();

        // Daftar nama produk yang lebih realistis
        $realProductNames = [
            'Laptop Gaming Ultra',
            'Smartphone Pro X',
            'Smart TV 4K HDR',
            'Headphone Nirkabel Premium',
            'Kamera Mirrorless Canggih',
            'Keyboard Mekanik RGB',
            'Mouse Gaming Ergonomis',
            'Speaker Bluetooth Portabel',
            'Monitor Curved Ultrawide',
            'Printer Multifungsi Laser',
            'Drone Lipat Kompak',
            'Smartwatch Fitness Tracker',
            'Power Bank Fast Charging',
            'SSD Eksternal Super Cepat',
            'Router Wi-Fi Mesh',
            'Projector Mini HD',
            'Vacuum Cleaner Robot',
            'Mesin Cuci Otomatis Inverter',
            'Kulkas Dua Pintu Jumbo',
            'Microwave Digital',
            'Blender Serbaguna',
            'Setrika Uap Otomatis',
            'Rice Cooker Pintar',
            'Panci Elektrik Multifungsi',
            'Sepatu Lari Ultra Ringan',
            'Kaos Olahraga Dry-Fit',
            'Celana Jeans Slim Fit',
            'Jaket Bomber Casual',
            'Topi Baseball Klasik',
            'Tas Ransel Anti Maling',
            'Dompet Kulit Asli',
            'Kacamata Anti Radiasi',
            'Parfum Pria Woody',
            'Parfum Wanita Floral',
            'Buku Fiksi Fantasi',
            'Novel Thriller Misteri',
            'Komik Manga Populer',
            'Alat Tulis Set Lengkap',
            'Pensil Warna Premium',
            'Casing HP Anti Bentur',
            'Screen Protector Tempered Glass',
            'Lampu Meja LED',
            'Smart Plug Otomatis',
            'Google Nest Mini',
            'Amazon Echo Dot',
        ];

        // Path ke folder gambar dummy sumber di direktori public
        $sourceImagesPath = public_path('img/produk_dummy');

        // Pastikan folder sumber ada dan berisi gambar
        if (!File::isDirectory($sourceImagesPath)) {
            echo "Direktori sumber gambar dummy tidak ditemukan: " . $sourceImagesPath . "\n";
            echo "Harap buat direktori ini dan masukkan gambar dummy di dalamnya.\n";
            for ($i = 0; $i < count($realProductNames); $i++) {
                Produk::create([
                    'nama_produk' => $realProductNames[$i % count($realProductNames)],
                    'deskripsi_produk' => $faker->paragraph(3, true),
                    'id_kategori' => $kategoriIds[array_rand($kategoriIds)],
                    'id_merek' => $merekIds[array_rand($merekIds)],
                    'harga_modal' => rand(10000, 500000),
                    'harga_jual' => rand(550000, 1000000),
                    'jumlah_stok' => rand(10, 100),
                    'gambar_produk' => null,
                    'barcode' => $faker->unique()->ean13(), // Menambahkan barcode
                ]);
            }
            return;
        }

        // Ambil semua file gambar dari folder sumber
        $sourceImageFiles = collect(File::files($sourceImagesPath))
            ->filter(fn($file) => in_array(strtolower($file->getExtension()), ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp']))
            ->all();

        if (empty($sourceImageFiles)) {
            echo "Tidak ada file gambar yang valid ditemukan di: " . $sourceImagesPath . "\n";
            for ($i = 0; $i < count($realProductNames); $i++) {
                Produk::create([
                    'nama_produk' => $realProductNames[$i % count($realProductNames)],
                    'deskripsi_produk' => $faker->paragraph(3, true),
                    'id_kategori' => $kategoriIds[array_rand($kategoriIds)],
                    'id_merek' => $merekIds[array_rand($merekIds)],
                    'harga_modal' => rand(10000, 500000),
                    'harga_jual' => rand(550000, 1000000),
                    'jumlah_stok' => rand(10, 100),
                    'gambar_produk' => null,
                    'barcode' => $faker->unique()->ean13(), // Menambahkan barcode
                ]);
            }
            return;
        }

        // Hapus semua gambar sebelumnya di direktori 'produk' di storage/app/public (opsional, untuk kebersihan)
        Storage::disk('public')->deleteDirectory('produk');
        Storage::disk('public')->makeDirectory('produk');

        // Loop untuk membuat produk, disesuaikan dengan jumlah nama produk atau minimal 10
        $numberOfProductsToCreate = max(10, count($realProductNames));
        for ($i = 0; $i < $numberOfProductsToCreate; $i++) {
            $imagePathInStorage = null;

            $productName = $realProductNames[$i % count($realProductNames)];

            $randomSourceFile = $sourceImageFiles[array_rand($sourceImageFiles)];
            $fileExtension = $randomSourceFile->getExtension();
            $newFileName = uniqid() . '.' . $fileExtension;

            Storage::disk('public')->putFileAs(
                'produk',
                $randomSourceFile->getPathname(),
                $newFileName
            );

            $imagePathInStorage = 'produk/' . $newFileName;

            Produk::create([
                'nama_produk' => $productName,
                'deskripsi_produk' => $faker->paragraph(3, true),
                'id_kategori' => $kategoriIds[array_rand($kategoriIds)],
                'id_merek' => $merekIds[array_rand($merekIds)],
                'harga_modal' => rand(10000, 500000),
                'harga_jual' => rand(550000, 1000000),
                'jumlah_stok' => rand(10, 100),
                'gambar_produk' => $imagePathInStorage,
                'barcode' => $faker->unique()->ean13(), // Menambahkan barcode
            ]);
        }
    }
}