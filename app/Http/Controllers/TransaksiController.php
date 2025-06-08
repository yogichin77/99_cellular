<?php

namespace App\Http\Controllers;

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
use App\Models\Kategori;
use Barryvdh\DomPDF\Facade\Pdf;


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
                'sub_total' => 'required|numeric|min:0', // Meskipun dihitung ulang, ini sebagai baseline
                'total_bayar' => 'required|numeric|min:0',
                'metode_pembayaran' => 'required|in:cash,kredit',
                'jatuh_tempo' => ['nullable', 'date', Rule::requiredIf($request->metode_pembayaran === 'kredit')],
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
                    'sub_total' => $calculatedSubTotal, // Menggunakan sub_total yang dihitung
                    'total_bayar' => $totalBayar,
                    'total_kurang' => $totalKurang,
                    'metode_pembayaran' => $request->metode_pembayaran,
                    'jatuh_tempo' => $request->metode_pembayaran === 'kredit'
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
                'sub_total' => 'required|numeric|min:0', // Ini bisa dihitung ulang di backend
                'total_bayar' => 'required|numeric|min:0',
                'metode_pembayaran' => 'required|string|in:cash,kredit',
                'jatuh_tempo' => [
                    'nullable',
                    'date',
                    // Pastikan jatuh tempo diperlukan jika metode pembayaran yang *diminta* adalah kredit
                    Rule::requiredIf($request->metode_pembayaran === 'kredit')
                ],
                'diskon' => 'required|numeric|min:0',
                'id_pelanggan' => 'nullable|exists:pelanggans,id',
                'id_user' => 'required|exists:users,id',
                'items' => 'required|array|min:1',
                'items.*.id_detail_transaksi' => 'nullable|integer',
                'items.*.id_produk' => 'required|exists:produks,id',
                'items.*.jumlah' => 'required|integer|min:1',
                // Anda mungkin ingin menambahkan validasi untuk harga_satuan di sini
                // 'items.*.harga_satuan' => 'required|numeric|min:0',
            ]);

            $transaksiIdFromRoute = $transaksi->id;

            Log::info('DEBUG: ID Transaksi dari Route Model Binding di awal update(): ' . ($transaksiIdFromRoute ?? 'NULL'));

            // Jalankan operasi dalam sebuah transaksi database
            return DB::transaction(function () use ($request, $transaksi, $validated, $transaksiIdFromRoute) {
                $transaksiIdUntukDetail = $transaksiIdFromRoute;

                Log::info('Memulai update transaksi ID: ' . ($transaksiIdUntukDetail ?? 'NULL'), [
                    'request_payload' => $request->all(),
                    'current_transaksi_id' => $transaksiIdFromRoute,
                    'current_transaksi_state_all_attributes' => $transaksi->toArray(),
                    'current_detail_transaksi_state' => $transaksi->detailtransaksis->toArray()
                ]);

                // Hitung ulang sub_total di backend untuk keamanan dan akurasi
                $calculatedSubTotal = 0;
                foreach ($validated['items'] as $item) {
                    $produk = Produk::findOrFail($item['id_produk']);
                    $calculatedSubTotal += $item['jumlah'] * $produk->harga_jual; // Gunakan harga_jual dari produk
                }

                $diskon = min($validated['diskon'], $calculatedSubTotal);
                $totalSetelahDiskon = $calculatedSubTotal - $diskon;
                $totalBayarYangDiterima = $validated['total_bayar']; // Total bayar kumulatif termasuk tambahan
                $totalKurang = max(0, $totalSetelahDiskon - $totalBayarYangDiterima);

                // --- LOGIKA KUNCI UNTUK MENGUBAH STATUS PEMBAYARAN ---
                $newMetodePembayaran = $validated['metode_pembayaran'];
                $newJatuhTempo = $validated['metode_pembayaran'] === 'kredit'
                    ? (isset($validated['jatuh_tempo']) ? Carbon::parse($validated['jatuh_tempo']) : null)
                    : null; // Jika cash, jatuh tempo harus null

                if ($transaksi->metode_pembayaran === 'kredit' && $totalKurang <= 0) {
                    $newMetodePembayaran = 'cash';
                    $newJatuhTempo = null;
                }
                $transaksi->update([
                    'sub_total' => $calculatedSubTotal,
                    'total_bayar' => $totalBayarYangDiterima,
                    'total_kurang' => $totalKurang,
                    'metode_pembayaran' => $newMetodePembayaran,
                    'jatuh_tempo' => $newJatuhTempo,
                    'diskon' => $diskon,
                    'id_pelanggan' => $validated['id_pelanggan'],
                    'id_user' => $validated['id_user'],
                ]);
                Log::info('Transaksi utama berhasil diupdate. ID transaksi: ' . ($transaksi->id ?? 'NULL'));
                $produkChanges = [];
                foreach ($transaksi->detailtransaksis as $detail) {
                    if (!isset($produkChanges[$detail->id_produk])) {
                        $produkChanges[$detail->id_produk] = 0;
                    }
                    $produkChanges[$detail->id_produk] += $detail->jumlah;
                }
                foreach ($validated['items'] as $item) {
                    if (!isset($produkChanges[$item['id_produk']])) {
                        $produkChanges[$item['id_produk']] = 0;
                    }
                    $produkChanges[$item['id_produk']] -= $item['jumlah'];
                }
                foreach ($produkChanges as $produkId => $change) {
                    $produk = Produk::findOrFail($produkId);
                    $stokAkhirPotensial = $produk->jumlah_stok + $change;
                    if ($stokAkhirPotensial < 0) {
                        throw new \Exception("Stok produk {$produk->nama_produk} tidak mencukupi untuk perubahan ini. Stok tersedia: {$produk->jumlah_stok}, Perubahan: {$change}");
                    }
                }
                $existingDetailIds = $transaksi->detailtransaksis->pluck('id')->toArray();
                $newDetailIds = [];
                foreach ($validated['items'] as $item) {
                    $produk = Produk::findOrFail($item['id_produk']);
                    $hargaSatuan = $produk->harga_jual;
                    $totalHarga = $item['jumlah'] * $hargaSatuan;
                    if (isset($item['id_detail_transaksi']) && $item['id_detail_transaksi'] !== null) {
                        $detail = DetailTransaksi::where('id', $item['id_detail_transaksi'])
                            ->where('id_transaksi', $transaksiIdFromRoute)
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
                            Log::warning('DetailTransaksi ID tidak ditemukan untuk update. Membuat item baru.', [
                                'missing_detail_id' => $item['id_detail_transaksi'],
                                'transaksi_id_context' => $transaksiIdFromRoute,
                                'item_data' => $item
                            ]);
                            $newDetail = DetailTransaksi::create([
                                'id_transaksi' => $transaksiIdFromRoute,
                                'id_produk' => $item['id_produk'],
                                'jumlah' => $item['jumlah'],
                                'harga_satuan' => $hargaSatuan,
                                'total_harga' => $totalHarga,
                            ]);
                            $newDetailIds[] = $newDetail->id;
                        }
                    } else {
                        Log::info('Mencoba membuat detail transaksi baru.', [
                            'id_transaksi_untuk_new_detail' => $transaksiIdFromRoute,
                            'produk_id' => $item['id_produk'],
                            'jumlah' => $item['jumlah']
                        ]);
                        $detail = DetailTransaksi::create([
                            'id_transaksi' => $transaksiIdFromRoute,
                            'id_produk' => $item['id_produk'],
                            'jumlah' => $item['jumlah'],
                            'harga_satuan' => $hargaSatuan,
                            'total_harga' => $totalHarga,
                        ]);
                        $newDetailIds[] = $detail->id;
                    }
                }
                $detailsToDelete = array_diff($existingDetailIds, $newDetailIds);
                if (!empty($detailsToDelete)) {
                    Log::info('Menghapus detail transaksi lama yang tidak lagi relevan.', ['ids_to_delete' => $detailsToDelete]);
                    DetailTransaksi::whereIn('id', $detailsToDelete)->delete();
                }
                foreach ($produkChanges as $produkId => $change) {
                    if ($change > 0) {
                        Produk::where('id', $produkId)->increment('jumlah_stok', $change);
                    } elseif ($change < 0) {
                        Produk::where('id', $produkId)->decrement('jumlah_stok', abs($change));
                    }
                }
                $transaksi->refresh()->load(['pelanggan', 'user', 'detailtransaksis.produk']);
                Log::info('Update transaksi berhasil ID: ' . ($transaksi->id ?? 'NULL'), [
                    'final_transaksi_data' => $transaksi->toArray(),
                    'final_detail_transaksi_data' => $transaksi->detailtransaksis->toArray()
                ]);
                return response()->json([
                    'message' => 'Transaksi berhasil diperbarui',
                    'data' => $transaksi
                ], 200);
            });
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error update transaksi: ' . $e->getMessage(), [
                'errors' => $e->errors(),
                'request_data' => $request->all()
            ]);
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
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





    public function destroy($id)
    {
        try {
            return DB::transaction(function () use ($id) {
                $transaksi = Transaksi::with('detailtransaksis')->findOrFail($id);
                foreach ($transaksi->detailtransaksis as $detail) {
                    Produk::where('id', $detail->id_produk)
                        ->increment('jumlah_stok', $detail->jumlah);
                }
                $transaksi->detailtransaksis()->delete();
                $transaksi->delete();
                return response()->json([
                    'message' => 'Transaksi berhasil dihapus'
                ], 200);
            });
        } catch (\Exception $e) {
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
                ->get();
            $fullYearRevenue = [];
            for ($i = 1; $i <= 12; $i++) {
                $monthName = Carbon::create(null, $i, 1)->translatedFormat('M');
                $currentMonthData = $monthlyRevenue->where('month', $i)->first();
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
            return response()->json([
                'message' => 'Monthly revenue data fetched successfully',
                'data' => $fullYearRevenue
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching monthly revenue: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            return response()->json([
                'message' => 'Failed to fetch monthly revenue',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function getAnnualRevenue(Request $request)
    {
        try {
            $startYear = $request->input('start_year', Carbon::now()->subYears(4)->year);
            $endYear = $request->input('end_year', Carbon::now()->year);
            $annualRevenue = Transaksi::select(
                DB::raw('YEAR(created_at) as year'),
                DB::raw('SUM(total_bayar) as total_revenue')
            )
                ->whereBetween(DB::raw('YEAR(created_at)'), [$startYear, $endYear])
                ->groupBy('year')
                ->orderBy('year')
                ->get();
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
                // PERBAIKAN DI SINI: Ganti 'metode_pembayaran' menjadi 'metode_pembayaran'
                'metode_pembayaran as name',
                DB::raw('COUNT(*) as total_transactions')
            )
                ->whereNotNull('metode_pembayaran') // PERBAIKAN DI SINI
                ->whereYear('created_at', $year)
                ->groupBy('metode_pembayaran') // PERBAIKAN DI SINI
                ->orderBy('name')
                ->get();
            $mappedStatus = $transactionStatus->map(function ($item) {
                $color = 'gray';
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


    public function getSalesByCategory(Request $request)
    {
        try {
            // Ambil tahun dari request, default ke tahun saat ini
            $year = $request->input('year', Carbon::now()->year);

            // Query untuk mendapatkan total penjualan per kategori
            $salesByCategory = DB::table('detailtransaksis as dt')
                ->select(
                    'k.nama_kategori as category_name',
                    DB::raw('SUM(dt.total_harga) as total_sales')
                )
                ->join('produks as p', 'dt.id_produk', '=', 'p.id')
                ->join('kategoris as k', 'p.id_kategori', '=', 'k.id')
                ->join('transaksis as t', 'dt.id_transaksi', '=', 't.id')
                ->whereYear('t.created_at', $year) // Filter berdasarkan tahun transaksi
                ->groupBy('k.nama_kategori')
                ->orderBy('total_sales', 'desc')
                ->get();

            // Ambil semua nama kategori yang ada untuk memastikan semua kategori tampil, meskipun tidak ada penjualan
            $allCategories = Kategori::pluck('nama_kategori')->toArray();

            // Inisialisasi data penjualan dengan semua kategori dan total sales 0
            $formattedSales = array_fill_keys($allCategories, 0);

            // Isi data penjualan dengan hasil query
            foreach ($salesByCategory as $sale) {
                $formattedSales[$sale->category_name] = round(floatval($sale->total_sales), 2);
            }

            // Ubah format menjadi array of objects untuk kompatibilitas grafik (opsional, tergantung frontend)
            $chartData = [];
            foreach ($formattedSales as $category => $sales) {
                $chartData[] = [
                    'name' => $category,
                    'value' => $sales,
                    // Anda bisa menambahkan warna di sini jika diperlukan, contoh:
                    // 'color' => '#'.substr(md5($category), 0, 6) // Warna random berdasarkan nama kategori
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Data penjualan berdasarkan kategori berhasil diambil',
                'data' => $chartData
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching sales by category: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data penjualan berdasarkan kategori',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }


    public function getQuickSummary(Request $request)
    {
        try {
            $currentYear = Carbon::now()->year;
            $currentMonth = Carbon::now()->month;

            // Hitung Total Penjualan Tahun Ini
            $totalSalesThisYear = Transaksi::whereYear('created_at', $currentYear)
                ->sum('total_bayar');

            // Hitung Total Transaksi Bulan Ini
            $totalTransactionsThisMonth = Transaksi::whereYear('created_at', $currentYear)
                ->whereMonth('created_at', $currentMonth)
                ->count();

            return response()->json([
                'success' => true,
                'message' => 'Quick summary data fetched successfully',
                'data' => [
                    'total_sales_this_year' => (float) $totalSalesThisYear,
                    'total_transactions_this_month' => (int) $totalTransactionsThisMonth,
                    'year' => $currentYear,
                    'month' => Carbon::now()->translatedFormat('F'), // Nama bulan
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching quick summary: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch quick summary data',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }



    public function getSalesReport(Request $request)
    {
        try {
            // Validasi input
            $validator = Validator::make($request->all(), [
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'category_id' => 'nullable|exists:kategoris,id',
                'product_id' => 'nullable|exists:produks,id',
                'group_by' => 'nullable|in:day,month,year,category,product', // Cara pengelompokan
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid input data',
                    'errors' => $validator->errors()
                ], 422);
            }

            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $categoryId = $request->input('category_id');
            $productId = $request->input('product_id');
            $groupBy = $request->input('group_by', 'day'); // Default group by day

            // Query dasar untuk mengambil detail transaksi
            $query = DetailTransaksi::select(
                DB::raw('SUM(detail_transaksis.total_harga) as total_sales'),
                DB::raw('COUNT(DISTINCT transaksis.id) as total_transactions')
            )
                ->join('transaksis', 'detail_transaksis.id_transaksi', '=', 'transaksis.id')
                ->join('produks', 'detail_transaksis.id_produk', '=', 'produks.id')
                ->join('kategoris', 'produks.id_kategori', '=', 'kategoris.id');

            // Apply date filters
            if ($startDate) {
                $query->whereDate('transaksis.created_at', '>=', $startDate);
            }
            if ($endDate) {
                $query->whereDate('transaksis.created_at', '<=', $endDate);
            }

            // Apply category filter
            if ($categoryId) {
                $query->where('kategoris.id', $categoryId);
            }

            // Apply product filter
            if ($productId) {
                $query->where('produks.id', $productId);
            }

            // Grouping logic
            switch ($groupBy) {
                case 'day':
                    $query->addSelect(DB::raw('DATE(transaksis.created_at) as period_date'))
                        ->groupBy(DB::raw('DATE(transaksis.created_at)'))
                        ->orderBy('period_date', 'asc');
                    break;
                case 'month':
                    $query->addSelect(DB::raw('DATE_FORMAT(transaksis.created_at, "%Y-%m") as period_date'))
                        ->groupBy(DB::raw('DATE_FORMAT(transaksis.created_at, "%Y-%m")'))
                        ->orderBy('period_date', 'asc');
                    break;
                case 'year':
                    $query->addSelect(DB::raw('YEAR(transaksis.created_at) as period_date'))
                        ->groupBy(DB::raw('YEAR(transaksis.created_at)'))
                        ->orderBy('period_date', 'asc');
                    break;
                case 'category':
                    $query->addSelect('kategoris.nama_kategori as group_name')
                        ->groupBy('kategoris.nama_kategori')
                        ->orderBy('total_sales', 'desc');
                    break;
                case 'product':
                    $query->addSelect('produks.nama_produk as group_name')
                        ->groupBy('produks.nama_produk')
                        ->orderBy('total_sales', 'desc');
                    break;
                default:
                    // Default to day if no valid group_by is provided
                    $query->addSelect(DB::raw('DATE(transaksis.created_at) as period_date'))
                        ->groupBy(DB::raw('DATE(transaksis.created_at)'))
                        ->orderBy('period_date', 'asc');
                    break;
            }

            $reportData = $query->get();

            return response()->json([
                'success' => true,
                'message' => 'Sales report fetched successfully',
                'data' => $reportData
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching sales report: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch sales report data',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function exportSalesPdf(Request $request)
    {
        try {
            // Re-use the filtering logic from getSalesReport or build a new one
            $query = Transaksi::with(['pelanggan', 'user']) // Load relations
                ->orderBy('created_at', 'asc');

            // Apply filters from request
            if ($request->has('search') && $request->input('search')) {
                $searchTerm = $request->input('search');
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('id', 'like', '%' . $searchTerm . '%')
                        ->orWhereHas('pelanggan', function ($q) use ($searchTerm) {
                            $q->where('nama_pelanggan', 'like', '%' . $searchTerm . '%')
                                ->orWhere('nama_toko', 'like', '%' . $searchTerm . '%');
                        })
                        ->orWhereHas('user', function ($q) use ($searchTerm) {
                            $q->where('name', 'like', '%' . $searchTerm . '%');
                        });
                });
            }

            if ($request->has('status') && $request->input('status') !== 'all') {
                $query->where('metode_pembayaran', $request->input('status'));
            }

            if ($request->has('start_date') && $request->input('start_date')) {
                $query->whereDate('created_at', '>=', $request->input('start_date'));
            }
            if ($request->has('end_date') && $request->input('end_date')) {
                $query->whereDate('created_at', '<=', $request->input('end_date'));
            }

            $transactions = $query->get();

            // Prepare filter info for the PDF
            $filters = [
                'start_date' => $request->input('start_date'),
                'end_date' => $request->input('end_date'),
                'status' => $request->input('status', 'all'),
                'search' => $request->input('search'),
            ];

            // Load view with data
            $pdf = Pdf::loadView('reports.sales_pdf', [
                'transactions' => $transactions,
                'filters' => $filters,
            ]);

            // Set paper size and orientation if needed
            $pdf->setPaper('a4', 'landscape'); // portrait or landscape

            // Download the PDF
            return $pdf->download('laporan-penjualan-' . Carbon::now()->format('YmdHis') . '.pdf');
        } catch (\Exception $e) {
            Log::error('Error exporting sales PDF: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            // Return a simple response or redirect with error
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate PDF report',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }
}
