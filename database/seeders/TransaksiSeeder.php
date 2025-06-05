<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Produk;
use App\Models\Pelanggan;
use App\Models\Transaksi;
use Illuminate\Database\Seeder;
use App\Models\DetailTransaksi;
use Faker\Factory as Faker;
use Illuminate\Support\Carbon;

class TransaksiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('id_ID');

        // Ambil semua produk, pelanggan, dan user yang sudah ada
        $produkIds = Produk::pluck('id')->toArray();
        $pelangganIds = Pelanggan::pluck('id')->toArray();
        $userIds = User::pluck('id')->toArray();

        // Pastikan ada data di tabel terkait
        if (empty($produkIds)) {
            $this->command->info('Tidak ada produk. Silakan jalankan ProdukSeeder terlebih dahulu.');
            return;
        }
        if (empty($userIds)) {
            $this->command->info('Tidak ada user. Silakan buat setidaknya satu user terlebih dahulu.');
            return;
        }

        $numberOfTransactions = 200; // Jumlah transaksi yang ingin Anda buat
        $monthsToCover = 18; // Data transaksi selama 18 bulan terakhir (1.5 tahun)

        for ($i = 0; $i < $numberOfTransactions; $i++) {
            // Tentukan tanggal transaksi dalam rentang 18 bulan terakhir
            $createdAt = Carbon::now()->subMonths(rand(0, $monthsToCover - 1))
                ->subDays(rand(0, 30))
                ->subHours(rand(0, 23))
                ->subMinutes(rand(0, 59));

            // Pilih status pembayaran secara acak
            $statusPembayaran = $faker->randomElement(['cash', 'kredit']);

            // Tentukan jatuh tempo jika status kredit
            $jatuhTempo = null;
            if ($statusPembayaran === 'kredit') {
                $jatuhTempo = $createdAt->addDays($faker->numberBetween(7, 60)); // Jatuh tempo 7-60 hari setelah transaksi
            }

            // Tentukan jumlah item dalam transaksi (misal: 1-5 produk per transaksi)
            $numberOfItems = $faker->numberBetween(1, 5);
            $selectedProdukIds = $faker->randomElements($produkIds, $numberOfItems); // Pilih produk unik untuk transaksi ini

            $subTotalBayar = 0;
            $detailTransaksiData = [];

            foreach ($selectedProdukIds as $produkId) {
                $produk = Produk::find($produkId);
                if ($produk) {
                    $jumlah = $faker->numberBetween(1, 10); // Jumlah produk per item
                    // Pastikan stok mencukupi untuk simulasi
                    if ($produk->jumlah_stok >= $jumlah) {
                        $hargaSatuan = $produk->harga_jual;
                        $totalHargaItem = $jumlah * $hargaSatuan;
                        $subTotalBayar += $totalHargaItem;

                        $detailTransaksiData[] = [
                            'id_produk' => $produk->id,
                            'jumlah' => $jumlah,
                            'harga_satuan' => $hargaSatuan,
                            'total_harga' => $totalHargaItem,
                            'created_at' => $createdAt,
                            'updated_at' => $createdAt,
                        ];

                        // Kurangi stok produk secara real-time saat seeding
                        $produk->decrement('jumlah_stok', $jumlah);
                    }
                }
            }

            if (empty($detailTransaksiData)) {
                // Lewati transaksi ini jika tidak ada produk yang berhasil ditambahkan karena stok
                continue;
            }

            $diskon = $faker->randomElement([0, 0, 0, 0, $faker->numberBetween(1000, 100000)]); // Ada peluang diskon

            $totalSetelahDiskon = $subTotalBayar - $diskon;
            $totalBayar = $totalSetelahDiskon; // Default cash
            $totalKurang = 0;

            if ($statusPembayaran === 'kredit') {
                // Untuk kredit, total_bayar bisa lebih kecil dari total_setelah_diskon
                $totalBayar = $faker->numberBetween($totalSetelahDiskon * 0.1, $totalSetelahDiskon * 0.9); // Bayar sebagian
                $totalKurang = $totalSetelahDiskon - $totalBayar;
            }

            $transaksi = Transaksi::create([
                'id_pelanggan' => $faker->randomElement($pelangganIds) ?: null, // Bisa null jika tidak ada pelanggan
                'id_user' => $faker->randomElement($userIds),
                'sub_total_bayar' => $subTotalBayar,
                'diskon' => $diskon,
                'total_bayar' => $totalBayar,
                'total_kurang' => $totalKurang,
                'status_pembayaran' => $statusPembayaran,
                'jatuh_tempo' => $jatuhTempo,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            // Tambahkan id_transaksi ke setiap detail transaksi
            foreach ($detailTransaksiData as &$detail) {
                $detail['id_transaksi'] = $transaksi->id;
            }

            DetailTransaksi::insert($detailTransaksiData); // Insert massal untuk detail transaksi
        }

        $this->command->info("{$numberOfTransactions} transaksi telah berhasil di-seed.");
    }
}
