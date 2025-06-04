<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\Produk;
use App\Models\Transaksi;
use Illuminate\Http\Request;
use App\Models\DetailTransaksi;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class TransaksiController extends Controller
{
    /**
     * Display a listing of the transactions with filters.
     */
    public function index()
    {
        // Ambil semua transaksi lengkap dengan relasi detail dan pelanggan
        $transaksis = Transaksi::with(['detailtransaksis.produk', 'pelanggan', 'user'])->get();

        return response()->json([
            'success' => true,
            'message' => 'Data transaksi berhasil diambil',
            'data' => $transaksis
        ]);
    }

    /**
     * Store a newly created transaction.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'sub_total_bayar' => 'required|numeric|min:0',
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

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }


            return DB::transaction(function () use ($request) {
                $subTotal = collect($request->items)->sum('total_harga');
                $totalSetelahDiskon = $subTotal - ($request->diskon ?? 0);
                $totalKurang = max(0, $totalSetelahDiskon - $request->total_bayar);
                $transaksi = Transaksi::create([
                    'sub_total_bayar' => $request->sub_total_bayar,
                    'total_bayar' => $request->total_bayar,
                    'total_kurang' => $totalKurang,
                    'status_pembayaran' => $request->status_pembayaran,
                    'jatuh_tempo' => $request->status_pembayaran === 'kredit'
                        ? Carbon::parse($request->jatuh_tempo)
                        : null,
                    'diskon' => $request->diskon ?? 0,
                    'id_pelanggan' => $request->id_pelanggan ?: null,
                    'id_user' => $request->id_user,
                ]);

                // Process items
                foreach ($request->items as $item) {
                    $produk = Produk::findOrFail($item['id_produk']);

                    if ($produk->jumlah_stok < $item['jumlah']) {
                        throw new \Exception("Stok produk {$produk->nama_produk} tidak mencukupi");
                    }

                    // Update stock
                    $produk->decrement('jumlah_stok', $item['jumlah']);

                    // Create transaction detail
                    DetailTransaksi::create([
                        'id_transaksi' => $transaksi->id,
                        'id_produk' => $produk->id,
                        'jumlah' => $item['jumlah'],
                        'harga_satuan' => $produk->harga_jual,
                        'total_harga' => $item['jumlah'] * $produk->harga_jual,
                    ]);
                }

                return response()->json([
                    'message' => 'Transaksi berhasil dibuat',
                    'data' => $transaksi->load('detailtransaksis.produk')
                ], 201);
            });
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(), // Tampilkan pesan error spesifik
                'error' => env('APP_DEBUG') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    /**
     * Display the specified transaction.
     */
    public function show($id)
    {
        try {
            $transaksi = Transaksi::with(['pelanggan', 'detailtransaksis.produk', 'user'])
                ->findOrFail($id);

            return response()->json([
                'message' => 'Detail transaksi berhasil dimuat',
                'data' => $transaksi
            ], 200);
        } catch (\Exception $e) {
            return $this->errorResponse('Transaksi tidak ditemukan', $e, 404);
        }
    }

    /**
     * Update the specified transaction.
     */
    public function update(Request $request, Transaksi $transaksi)
    {
        $validated = $request->validate([
            'sub_total_bayar' => 'required|numeric',
            'total_bayar' => 'required|numeric',
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
            'items.*.harga_satuan' => 'required|numeric|min:0',
        ]);

        return DB::transaction(function () use ($request, $transaksi, $validated) {
            // Update transaction
            $transaksi->update([
                'sub_total_bayar' => $validated['sub_total_bayar'],
                'total_bayar' => $validated['total_bayar'],
                'total_kurang' => $this->calculateTotalKurangFromValues(
                    $validated['sub_total_bayar'],
                    $validated['diskon'],
                    $validated['total_bayar']
                ),
                'status_pembayaran' => $validated['status_pembayaran'],
                'jatuh_tempo' => $validated['jatuh_tempo'],
                'diskon' => $validated['diskon'],
                'id_pelanggan' => $validated['id_pelanggan'],
                'id_user' => $validated['id_user'],
                'items.*.jumlah' => [
                    'required',
                    'integer',
                    'min:1',
                    function ($attribute, $value, $fail) use ($request) {
                        $id_produk = explode('.', $attribute)[1];
                        $produk = Produk::find($request->items[$id_produk]['id_produk']);
                        if ($produk->jumlah_stok < $value) {
                            $fail("Stok {$produk->nama_produk} tidak mencukupi");
                        }
                    }
                ]
            ]);
            $existingDetailIds = $transaksi->detailtransaksis->pluck('id')->toArray();
            $newDetailIds = [];

            foreach ($validated['items'] as $itemData) {
                $produk = Produk::findOrFail($itemData['id_produk']);
                $stokTersedia = $produk->jumlah_stok;

                if (isset($itemData['id_detail_transaksi'])) {
                    $detailLama = DetailTransaksi::find($itemData['id_detail_transaksi']);
                    $stokTersedia += $detailLama->jumlah;
                }

                if ($stokTersedia < $itemData['jumlah']) {
                    throw new \Exception("Stok tidak cukup");
                }
                if (isset($itemData['id_detail_transaksi'])) {

                    $detail = $transaksi->detailtransaksis->firstWhere('id', $itemData['id_detail_transaksi']);
                    if ($detail) {

                        Produk::where('id', $detail->id_produk)
                            ->increment('jumlah_stok', $detail->jumlah);


                        $detail->update([
                            'id_produk' => $itemData['id_produk'],
                            'jumlah' => $itemData['jumlah'],
                            'harga_satuan' => $produk->harga_jual,
                            'total_harga' => $itemData['jumlah'] * $produk->harga_jual,
                        ]);


                        $produk->decrement('jumlah_stok', $itemData['jumlah']);

                        $newDetailIds[] = $detail->id;
                    }
                } else {

                    $detail = $transaksi->detailtransaksis()->create([
                        'id_produk' => $itemData['id_produk'],
                        'jumlah' => $itemData['jumlah'],
                        'harga_satuan' => $produk->harga_jual,
                        'total_harga' => $itemData['jumlah'] * $produk->harga_jual,
                    ]);
                    $produk->decrement('jumlah_stok', $itemData['jumlah']);
                    $newDetailIds[] = $detail->id;
                }
            }
            $detailsToDelete = array_diff($existingDetailIds, $newDetailIds);
            if (!empty($detailsToDelete)) {
                foreach ($transaksi->detailtransaksis()->whereIn('id', $detailsToDelete)->get() as $detail) {
                    // Kembalikan stok sebelum dihapus
                    Produk::where('id', $detail->id_produk)
                        ->increment('jumlah_stok', $detail->jumlah);
                }
                $transaksi->detailtransaksis()->whereIn('id', $detailsToDelete)->delete();
            }
            return response()->json($transaksi->refresh()->load(['pelanggan', 'user', 'detailtransaksis.produk']), 200);
        });
    }


    protected function calculateTotalKurangFromValues($sub_total, $diskon, $total_bayar)
    {
        $total = ($sub_total - $diskon) - $total_bayar;
        return max(0, $total);
    }

    public function destroy($id)
    {
        try {
            return DB::transaction(function () use ($id) {
                $transaksi = Transaksi::with('detailtransaksis')->findOrFail($id);

                // Restore product stock
                foreach ($transaksi->detailtransaksis as $detail) {
                    Produk::where('id', $detail->id_produk)
                        ->increment('jumlah_stok', $detail->jumlah);
                }

                // Delete transaction
                $transaksi->detailtransaksis()->delete();
                $transaksi->delete();

                return response()->json([
                    'message' => 'Transaksi berhasil dihapus'
                ], 200);
            });
        } catch (\Exception $e) {
            Log::error('Delete error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Gagal menghapus transaksi',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }

    private function errorResponse($message, $exception, $code = 500)
    {
        Log::error($message . ': ' . $exception->getMessage());

        return response()->json([
            'message' => $message,
            'error' => env('APP_DEBUG') ? $exception->getMessage() : null
        ], $code);
    }
}
