<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaksi;
use App\Models\DetailTransaksi;
use App\Models\Produk;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TransaksiController extends Controller
{
    /**
     * Display a listing of the transactions with filters.
     */
    public function index()
    {
        // Ambil semua transaksi lengkap dengan relasi detail dan pelanggan
        $transaksis = Transaksi::with(['detailtransaksis.produk', 'pelanggan','user'])->get();

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
                'sub_total_harga' => 'required|numeric|min:0',
                'total_bayar' => 'required|numeric|min:0',
                'status_pembayaran' => 'required|in:cash,kredit',
                'jatuh_tempo' => 'nullable|date|required_if:status_pembayaran,kredit',
                'diskon' => 'nullable|numeric|min:0',
                'id_pelanggan' => 'nullable|exists:pelanggans,id_pelanggan',
                'id_user' => 'required|exists:users,id',
                'items' => 'required|array|min:1',
                'items.*.id_produk' => 'required|exists:produks,id_produk',
                'items.*.jumlah' => 'required|integer|min:1',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            return DB::transaction(function () use ($request) {
                // Create transaction
                $transaksi = Transaksi::create([
                    'sub_total_harga' => $request->sub_total_harga,
                    'total_bayar' => $request->total_bayar,
                    'total_kurang' => $this->calculateTotalKurang($request),
                    'status_pembayaran' => $request->status_pembayaran,
                    'jatuh_tempo' => $request->status_pembayaran === 'kredit'
                        ? Carbon::parse($request->jatuh_tempo)
                        : null,
                    'diskon' => $request->diskon ?? 0,
                    'id_pelanggan' => $request->id_pelanggan,
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
                        'id_produk' => $produk->id_produk,
                        'jumlah' => $item['jumlah'],
                        'harga_satuan' => $produk->harga_jual,
                        'total_harga' => $item['jumlah'] * $produk->harga_jual,
                    ]);
                }

                return response()->json([
                    'message' => 'Transaksi berhasil dibuat',
                    'data' => $transaksi->load('detailTransaksis.produk')
                ], 201);
            });
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal membuat transaksi', $e);
        }
    }

    /**
     * Display the specified transaction.
     */
    public function show($id)
    {
        try {
            $transaksi = Transaksi::with(['pelanggan', 'detailTransaksis.produk', 'user'])
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
            'sub_total_harga' => 'required|numeric',
            'total_bayar' => 'required|numeric',
            'status_pembayaran' => 'required|in:cash,kredit',
            'jatuh_tempo' => 'nullable|date',
            'diskon' => 'required|numeric|min:0',
            'id_pelanggan' => 'nullable|exists:pelanggans,id_pelanggan',
            'id_user' => 'required|exists:users,id',
            'items' => 'required|array|min:1',
            'items.*.id_detail_transaksi' => 'nullable|integer', // ID detail transaksi, bisa null jika item baru
            'items.*.id_produk' => 'required|exists:produks,id_produk',
            'items.*.jumlah' => 'required|integer|min:1',
            'items.*.harga_satuan' => 'required|numeric|min:0', // Pastikan harga satuan dikirim dari frontend
        ]);

        $transaksi->sub_total_harga = $validated['sub_total_harga'];
        $transaksi->diskon = $validated['diskon'];
        $transaksi->total_bayar = $validated['total_bayar'];
        $transaksi->total_kurang = max(0, $validated['sub_total_harga'] - $validated['diskon'] - $validated['total_bayar']);
        $transaksi->status_pembayaran = $validated['status_pembayaran'];
        $transaksi->jatuh_tempo = $validated['jatuh_tempo'];
        $transaksi->id_pelanggan = $validated['id_pelanggan'];
        $transaksi->id_user = $validated['id_user'];
        $transaksi->save();

        // Handle detail_transaksis
        $existingDetailIds = $transaksi->detailTransaksis->pluck('id')->toArray();
        $newDetailIds = [];

        foreach ($validated['items'] as $itemData) {
            if (isset($itemData['id_detail_transaksi']) && $itemData['id_detail_transaksi'] > 0) {
                // Update existing detail
                $detail = $transaksi->detailTransaksis->firstWhere('id', $itemData['id_detail_transaksi']);
                if ($detail) {
                    $detail->update([
                        'id_produk' => $itemData['id_produk'],
                        'jumlah' => $itemData['jumlah'],
                        'harga_satuan' => $itemData['harga_satuan'],
                        'total_harga' => $itemData['jumlah'] * $itemData['harga_satuan'],
                    ]);
                    $newDetailIds[] = $detail->id;
                }
            } else {
                // Create new detail
                $detail = $transaksi->detailTransaksis()->create([
                    'id_produk' => $itemData['id_produk'],
                    'jumlah' => $itemData['jumlah'],
                    'harga_satuan' => $itemData['harga_satuan'],
                    'total_harga' => $itemData['jumlah'] * $itemData['harga_satuan'],
                ]);
                $newDetailIds[] = $detail->id;
            }
        }

        // Delete details that are no longer in the items list
        $detailsToDelete = array_diff($existingDetailIds, $newDetailIds);
        if (!empty($detailsToDelete)) {
            $transaksi->detailTransaksis()->whereIn('id', $detailsToDelete)->delete();
        }

        // Load relations for the response
        $transaksi->load(['pelanggan', 'user', 'detailTransaksis.produk']);

        return response()->json($transaksi->refresh(), 200); // Return the updated transaction
    }


    /**
     * Helper function to calculate total_kurang.
     * This function should be defined in your controller or a helper class.
     */
    protected function calculateTotalKurang(Request $request)
    {
        return ($request->sub_total_harga - ($request->diskon ?? 0)) - $request->total_bayar;
    }


    public function destroy($id)
    {
        try {
            return DB::transaction(function () use ($id) {
                $transaksi = Transaksi::with('detailTransaksis')->findOrFail($id);

                // Restore product stock
                foreach ($transaksi->detailTransaksis as $detail) {
                    Produk::where('id_produk', $detail->id_produk)
                        ->increment('jumlah_stok', $detail->jumlah);
                }

                // Delete transaction
                $transaksi->detailTransaksis()->delete();
                $transaksi->delete();

                return response()->json([
                    'message' => 'Transaksi berhasil dihapus'
                ], 200);
            });
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menghapus transaksi', $e);
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
