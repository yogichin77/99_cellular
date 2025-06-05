<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\Produk;
use App\Models\Transaksi;
use Illuminate\Http\Request;
use App\Models\DetailTransaksi;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log; // Pastikan ini diimpor
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class TransaksiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Ambil semua transaksi beserta relasi detailtransaksis, pelanggan, dan user
        $transaksis = Transaksi::with(['detailtransaksis.produk', 'pelanggan', 'user'])->get();

        // Kembalikan respons JSON
        return response()->json([
            'success' => true,
            'message' => 'Data transaksi berhasil diambil',
            'data' => $transaksis
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            // Validasi data input
            $validator = Validator::make($request->all(), [
                'sub_total_bayar' => 'required|numeric|min:0', // Meskipun dihitung ulang, ini sebagai baseline
                'total_bayar' => 'required|numeric|min:0',
                'status_pembayaran' => 'required|in:cash,kredit',
                'jatuh_tempo' => ['nullable', 'date', Rule::requiredIf($request->status_pembayaran === 'kredit')],
                'diskon' => 'nullable|numeric|min:0',
                'id_pelanggan' => 'nullable|exists:pelanggans,id',
                'id_user' => 'required|exists:users,id',
                'items' => 'required|array|min:1',
                'items.*.id_produk' => 'required|exists:produks,id',
                'items.*.jumlah' => 'required|integer|min:1',
            ]);

            // Jika validasi gagal, kembalikan error
            if ($validator->fails()) {
                Log::warning('Validasi gagal saat membuat transaksi', ['errors' => $validator->errors()->toArray()]);
                return response()->json(['errors' => $validator->errors()], 422);
            }

            // Jalankan operasi dalam sebuah transaksi database
            return DB::transaction(function () use ($request) {
                // Perhitungan subTotal dari items yang dikirim (lebih akurat)
                $calculatedSubTotal = 0;
                foreach ($request->items as $item) {
                    $produk = Produk::findOrFail($item['id_produk']);
                    $calculatedSubTotal += $item['jumlah'] * $produk->harga_jual;
                }

                $diskon = $request->diskon ?? 0;
                $totalSetelahDiskon = $calculatedSubTotal - $diskon;
                $totalBayar = $request->total_bayar;
                $totalKurang = max(0, $totalSetelahDiskon - $totalBayar);

                // Buat record transaksi utama
                $transaksi = Transaksi::create([
                    'sub_total_bayar' => $calculatedSubTotal, // Menggunakan sub_total yang dihitung
                    'total_bayar' => $totalBayar,
                    'total_kurang' => $totalKurang,
                    'status_pembayaran' => $request->status_pembayaran,
                    'jatuh_tempo' => $request->status_pembayaran === 'kredit'
                        ? Carbon::parse($request->jatuh_tempo)
                        : null,
                    'diskon' => $diskon,
                    'id_pelanggan' => $request->id_pelanggan ?: null,
                    'id_user' => $request->id_user,
                ]);

                // Proses setiap item dalam transaksi
                foreach ($request->items as $item) {
                    $produk = Produk::findOrFail($item['id_produk']);

                    // Periksa stok produk
                    if ($produk->jumlah_stok < $item['jumlah']) {
                        throw new \Exception("Stok produk {$produk->nama_produk} tidak mencukupi");
                    }

                    // Kurangi stok produk
                    $produk->decrement('jumlah_stok', $item['jumlah']);

                    // Buat detail transaksi
                    DetailTransaksi::create([
                        'id_transaksi' => $transaksi->id, // Menggunakan ID transaksi yang baru dibuat
                        'id_produk' => $produk->id,
                        'jumlah' => $item['jumlah'],
                        'harga_satuan' => $produk->harga_jual,
                        'total_harga' => $item['jumlah'] * $produk->harga_jual,
                    ]);
                }

                // Kembalikan respons sukses
                return response()->json([
                    'message' => 'Transaksi berhasil dibuat',
                    'data' => $transaksi->load('detailtransaksis.produk') // Load relasi untuk respons
                ], 201);
            });
        } catch (\Exception $e) {
            // Log error jika terjadi exception
            Log::error('Error saat membuat transaksi: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            return response()->json([
                'message' => $e->getMessage(),
                'error' => env('APP_DEBUG') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            // Temukan transaksi berdasarkan ID beserta relasi
            $transaksi = Transaksi::with(['pelanggan', 'detailtransaksis.produk', 'user'])
                ->findOrFail($id);

            // Kembalikan respons JSON
            return response()->json([
                'message' => 'Detail transaksi berhasil dimuat',
                'data' => $transaksi
            ], 200);
        } catch (\Exception $e) {
            // Tangani jika transaksi tidak ditemukan
            Log::error('Transaksi tidak ditemukan: ' . $e->getMessage(), ['id' => $id]);
            return response()->json([
                'message' => 'Transaksi tidak ditemukan',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Transaksi $transaksi)
    {
        try {
            // Validasi data input
            $validated = $request->validate([
                'sub_total_bayar' => 'required|numeric|min:0',
                'total_bayar' => 'required|numeric|min:0',
                'status_pembayaran' => 'required|in:cash,kredit',
                'jatuh_tempo' => [
                    'nullable',
                    'date',
                    Rule::requiredIf($request->status_pembayaran === 'kredit')
                ],
                'diskon' => 'required|numeric|min:0',
                'id_pelanggan' => 'nullable|exists:pelanggans,id',
                'id_user' => 'required|exists:users,id',
                'items' => 'required|array|min:1',
                'items.*.id_detail_transaksi' => 'nullable|integer',
                'items.*.id_produk' => 'required|exists:produks,id',
                'items.*.jumlah' => 'required|integer|min:1',
            ]);

            // <<< PERUBAHAN PENTING DI SINI >>>
            // Ambil ID transaksi SEBELUM masuk ke closure DB::transaction
            // Ini adalah upaya untuk memastikan ID tetap ada
            $transaksiIdFromRoute = $transaksi->id;

            // Ini baris log yang SANGAT PENTING
            Log::info('DEBUG: ID Transaksi dari Route Model Binding di awal update(): ' . ($transaksiIdFromRoute ?? 'NULL'));


            // Jalankan operasi dalam sebuah transaksi database
            return DB::transaction(function () use ($request, $transaksi, $validated, $transaksiIdFromRoute) { // Tambahkan $transaksiIdFromRoute di 'use'
                // PENTING: Gunakan $transaksiIdFromRoute untuk semua operasi terkait ID transaksi
                // Ganti $transaksiIdUntukDetail dengan $transaksiIdFromRoute
                $transaksiIdUntukDetail = $transaksiIdFromRoute; // Ini bisa dihapus jika langsung pakai $transaksiIdFromRoute

                Log::info('Memulai update transaksi ID: ' . ($transaksiIdUntukDetail ?? 'NULL'), [
                    'request_payload' => $request->all(),
                    // Tambahkan current_transaksi_id di sini, bukan seluruh state
                    'current_transaksi_id' => $transaksiIdFromRoute,
                    'current_transaksi_state_all_attributes' => $transaksi->toArray(), // Untuk debug lebih lanjut jika perlu
                    'current_detail_transaksi_state' => $transaksi->detailtransaksis->toArray()
                ]);

                // Hitung ulang sub_total_bayar dari item yang dikirim
                $calculatedSubTotal = 0;
                foreach ($validated['items'] as $item) {
                    $produk = Produk::findOrFail($item['id_produk']);
                    $calculatedSubTotal += $item['jumlah'] * $produk->harga_jual;
                }

                $diskon = min($validated['diskon'], $calculatedSubTotal);
                $totalSetelahDiskon = $calculatedSubTotal - $diskon;
                $totalBayar = $validated['total_bayar'];
                $totalKurang = max(0, $totalSetelahDiskon - $totalBayar);

                // Update data transaksi utama
                // Pastikan kita mengupdate objek $transaksi yang sama
                $transaksi->update([
                    'sub_total_bayar' => $calculatedSubTotal,
                    'total_bayar' => $totalBayar,
                    'total_kurang' => $totalKurang,
                    'status_pembayaran' => $validated['status_pembayaran'],
                    'jatuh_tempo' => $validated['status_pembayaran'] === 'kredit'
                        ? Carbon::parse($validated['jatuh_tempo'])
                        : null,
                    'diskon' => $diskon,
                    'id_pelanggan' => $validated['id_pelanggan'],
                    'id_user' => $validated['id_user'],
                ]);

                Log::info('Transaksi utama berhasil diupdate. ID transaksi: ' . ($transaksi->id ?? 'NULL'));

                // **MANAJEMEN STOK PRODUK:**
                // Pendekatan: Hitung perubahan stok bersih (net change) untuk setiap produk.
                $produkChanges = [];

                // 1. Tambahkan kembali semua stok dari detail yang ADA SEBELUM perubahan
                foreach ($transaksi->detailtransaksis as $detail) {
                    if (!isset($produkChanges[$detail->id_produk])) {
                        $produkChanges[$detail->id_produk] = 0;
                    }
                    $produkChanges[$detail->id_produk] += $detail->jumlah;
                }

                // 2. Kurangi stok untuk detail BARU/YANG DIUPDATE
                foreach ($validated['items'] as $item) {
                    if (!isset($produkChanges[$item['id_produk']])) {
                        $produkChanges[$item['id_produk']] = 0;
                    }
                    $produkChanges[$item['id_produk']] -= $item['jumlah'];
                }

                // Validasi apakah stok cukup
                foreach ($produkChanges as $produkId => $change) {
                    $produk = Produk::findOrFail($produkId);
                    if ($produk->jumlah_stok + $change < 0) {
                        throw new \Exception("Stok produk {$produk->nama_produk} tidak mencukupi untuk perubahan ini.");
                    }
                }

                // Proses update atau buat detail transaksi
                $existingDetailIds = $transaksi->detailtransaksis->pluck('id')->toArray();
                $newDetailIds = [];

                foreach ($validated['items'] as $item) {
                    $produk = Produk::findOrFail($item['id_produk']);
                    $hargaSatuan = $produk->harga_jual;
                    $totalHarga = $item['jumlah'] * $hargaSatuan;

                    if (isset($item['id_detail_transaksi']) && $item['id_detail_transaksi'] !== null) {
                        Log::info('Mencoba memperbarui detail transaksi yang ada.', [
                            'detail_id_dari_frontend' => $item['id_detail_transaksi'],
                            'id_transaksi_saat_ini' => $transaksiIdFromRoute, // Gunakan variabel ini
                            'produk_id' => $item['id_produk'],
                            'jumlah' => $item['jumlah']
                        ]);

                        $detail = DetailTransaksi::where('id', $item['id_detail_transaksi'])
                            ->where('id_transaksi', $transaksiIdFromRoute) // Gunakan variabel ini
                            ->first();

                        if ($detail) {
                            $detail->update([
                                'id_produk' => $item['id_produk'],
                                'jumlah' => $item['jumlah'],
                                'harga_satuan' => $hargaSatuan,
                                'total_harga' => $totalHarga,
                            ]);
                            $newDetailIds[] = $detail->id;
                        } else {
                            Log::warning('DetailTransaksi ID tidak ditemukan untuk update (mungkin dihapus manual atau ID salah). Membuat item baru.', [
                                'missing_detail_id' => $item['id_detail_transaksi'],
                                'transaksi_id_context' => $transaksiIdFromRoute, // Gunakan variabel ini
                                'item_data' => $item
                            ]);
                            $newDetail = DetailTransaksi::create([
                                'id_transaksi' => $transaksiIdFromRoute, // Gunakan variabel ini
                                'id_produk' => $item['id_produk'],
                                'jumlah' => $item['jumlah'],
                                'harga_satuan' => $hargaSatuan,
                                'total_harga' => $totalHarga,
                            ]);
                            $newDetailIds[] = $newDetail->id;
                        }
                    } else {
                        Log::info('Mencoba membuat detail transaksi baru.', [
                            'id_transaksi_untuk_new_detail' => $transaksiIdFromRoute, // Gunakan variabel ini
                            'produk_id' => $item['id_produk'],
                            'jumlah' => $item['jumlah']
                        ]);
                        $detail = DetailTransaksi::create([
                            'id_transaksi' => $transaksiIdFromRoute, // Gunakan variabel ini
                            'id_produk' => $item['id_produk'],
                            'jumlah' => $item['jumlah'],
                            'harga_satuan' => $hargaSatuan,
                            'total_harga' => $totalHarga,
                        ]);
                        $newDetailIds[] = $detail->id;
                    }
                }

                // Hapus detail transaksi yang tidak lagi ada di payload baru
                $detailsToDelete = array_diff($existingDetailIds, $newDetailIds);
                if (!empty($detailsToDelete)) {
                    Log::info('Menghapus detail transaksi lama yang tidak lagi relevan.', ['ids_to_delete' => $detailsToDelete]);
                    DetailTransaksi::whereIn('id', $detailsToDelete)->delete();
                }

                // Update stok produk di database
                foreach ($produkChanges as $produkId => $change) {
                    if ($change > 0) {
                        Produk::where('id', $produkId)->increment('jumlah_stok', $change);
                    } elseif ($change < 0) {
                        Produk::where('id', $produkId)->decrement('jumlah_stok', abs($change));
                    }
                }

                // Refresh model transaksi dan load relasi untuk respons
                $transaksi->refresh()->load(['pelanggan', 'user', 'detailtransaksis.produk']);

                Log::info('Update transaksi berhasil ID: ' . ($transaksi->id ?? 'NULL'), [
                    'final_transaksi_data' => $transaksi->toArray(),
                    'final_detail_transaksi_data' => $transaksi->detailtransaksis->toArray()
                ]);

                // Kembalikan respons sukses
                return response()->json([
                    'message' => 'Transaksi berhasil diperbarui',
                    'data' => $transaksi
                ], 200);
            });
        } catch (\Exception $e) {
            Log::error('Error update transaksi: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'message' => 'Gagal memperbarui transaksi',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            // Jalankan operasi dalam sebuah transaksi database
            return DB::transaction(function () use ($id) {
                // Temukan transaksi beserta detailnya
                $transaksi = Transaksi::with('detailtransaksis')->findOrFail($id);

                // Kembalikan stok produk yang terkait dengan transaksi ini
                foreach ($transaksi->detailtransaksis as $detail) {
                    Produk::where('id', $detail->id_produk)
                        ->increment('jumlah_stok', $detail->jumlah);
                }

                // Hapus semua detail transaksi
                $transaksi->detailtransaksis()->delete();
                // Hapus transaksi utama
                $transaksi->delete();

                // Kembalikan respons sukses
                return response()->json([
                    'message' => 'Transaksi berhasil dihapus'
                ], 200);
            });
        } catch (\Exception $e) {
            // Log error jika terjadi exception
            Log::error('Delete error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString(), 'transaksi_id' => $id]);
            return response()->json([
                'message' => 'Gagal menghapus transaksi',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function getMonthlyRevenue(Request $request)
    {
        try {
            $year = $request->input('year', Carbon::now()->year);

            $monthlyRevenue = Transaksi::select(
                DB::raw('MONTH(created_at) as month'),
                DB::raw('SUM(total_bayar) as total_revenue')
            )
                ->whereYear('created_at', $year)
                ->groupBy('month')
                ->orderBy('month')
                ->get(); // Hapus ->keyBy('month')->toArray() sementara untuk debugging

            // dd($monthlyRevenue); // Debug 1: Lihat hasil query mentah

            $fullYearRevenue = [];
            for ($i = 1; $i <= 12; $i++) {
                $monthName = Carbon::create(null, $i, 1)->translatedFormat('M');

                // Debug 2: Temukan entri untuk bulan saat ini
                $currentMonthData = $monthlyRevenue->where('month', $i)->first();

                // dd($currentMonthData); // Debug 3: Lihat data per bulan

                $amount = 0;
                $rawAmount = 0;

                if ($currentMonthData) {
                    $rawAmount = $currentMonthData->total_revenue;
                    $amount = round($rawAmount / 1000000, 2);
                }

                $fullYearRevenue[] = [
                    'month_num' => $i,
                    'month' => $monthName,
                    'amount' => $amount,
                    'raw_amount' => $rawAmount,
                ];
            }

            // dd($fullYearRevenue); // Debug 4: Lihat hasil akhir sebelum return

            return response()->json([
                'message' => 'Monthly revenue data fetched successfully',
                'data' => $fullYearRevenue
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching monthly revenue: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            // dd($e->getMessage(), $e->getTraceAsString()); // Debug 5: Akan menampilkan error di browser
            return response()->json([
                'message' => 'Failed to fetch monthly revenue',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function getAnnualRevenue(Request $request)
    {
        try {
            $startYear = $request->input('start_year', Carbon::now()->subYears(4)->year); // Default 5 tahun terakhir
            $endYear = $request->input('end_year', Carbon::now()->year);

            // Fetch annual revenue from database
            $annualRevenue = Transaksi::select(
                DB::raw('YEAR(created_at) as year'),
                DB::raw('SUM(total_bayar) as total_revenue')
            )
            ->whereBetween(DB::raw('YEAR(created_at)'), [$startYear, $endYear])
            ->groupBy('year')
            ->orderBy('year')
            ->get();

            // Prepare data to ensure all years in the range are present, fill missing with 0
            $fullAnnualRevenue = [];
            for ($year = $startYear; $year <= $endYear; $year++) {
                $existingData = $annualRevenue->where('year', $year)->first();
                if ($existingData) {
                    $fullAnnualRevenue[] = [
                        'year' => $year,
                        'amount' => round(floatval($existingData->total_revenue) / 1000000, 2), // Dalam jutaan
                        'raw_amount' => floatval($existingData->total_revenue),
                    ];
                } else {
                    $fullAnnualRevenue[] = [
                        'year' => $year,
                        'amount' => 0,
                        'raw_amount' => 0,
                    ];
                }
            }

            return response()->json([
                'message' => 'Annual revenue data fetched successfully',
                'data' => $fullAnnualRevenue
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error fetching annual revenue: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            return response()->json([
                'message' => 'Failed to fetch annual revenue',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }

   public function getTransactionStatus(Request $request)
    {
        try {
            $year = $request->input('year', Carbon::now()->year);

            $transactionStatus = Transaksi::select(
                // PERBAIKAN DI SINI: Ganti 'metode_pembayaran' menjadi 'status_pembayaran'
                'status_pembayaran as name',
                DB::raw('COUNT(*) as total_transactions')
            )
            ->whereNotNull('status_pembayaran') // PERBAIKAN DI SINI
            ->whereYear('created_at', $year)
            ->groupBy('status_pembayaran') // PERBAIKAN DI SINI
            ->orderBy('name')
            ->get();

            // Opsional: Anda bisa memetakan "Cash" dan "Kredit" ke warna
            $mappedStatus = $transactionStatus->map(function ($item) {
                $color = 'gray'; // Default color
                // Pastikan nilai 'cash' dan 'kredit' sesuai dengan data di DB Anda (case-insensitive)
                if (strtolower($item->name) === 'cash') {
                    $color = 'green';
                } elseif (strtolower($item->name) === 'kredit') {
                    $color = 'red';
                }
                return [
                    'name' => $item->name,
                    'value' => (int) $item->total_transactions,
                    'color' => $color,
                ];
            });

            return response()->json([
                'message' => 'Transaction status fetched successfully',
                'data' => $mappedStatus
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error fetching transaction status: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            return response()->json([
                'message' => 'Failed to fetch transaction status',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }
}



