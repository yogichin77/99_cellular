<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Produk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProdukController extends Controller
{
    public function index()
    {
        $produk = Produk::with(['kategori', 'merek'])->get();

        return response()->json([
            'success' => true,
            'message' => 'Data produk berhasil diambil',
            'data' => $produk
        ]);
    }

    public function show($id)
    {
        $produk = Produk::with(['kategori', 'merek'])->find($id);
        if (!$produk) {
            return response()->json(['message' => 'Produk tidak ditemukan'], 404);
        }
        return response()->json($produk);
    }

    public function store(Request $request)
    {
        // 1. Validasi Input
        $validator = Validator::make($request->all(), [
            'barcode' => 'nullable|string|max:255|unique:produks,barcode', // <-- Tambahkan ini
            'nama_produk' => 'required|string|max:255',
            'deskripsi_produk' => 'nullable|string|max:2000',
            'id_kategori' => 'required|exists:kategoris,id',
            'id_merek' => 'required|exists:mereks,id',
            'harga_modal' => 'required|numeric|min:0',
            'harga_jual' => 'required|numeric|min:0|gt:harga_modal',
            'jumlah_stok' => 'required|integer|min:0',
            'gambar_produk' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();

        // 2. Simpan Gambar Produk (jika ada)
        if ($request->hasFile('gambar_produk')) {
            $path = $request->file('gambar_produk')->store('produk', 'public');
            $validated['gambar_produk'] = $path;
        }

        // 3. Buat Produk Baru
        // $validated sekarang sudah termasuk 'barcode' dan 'deskripsi_produk'
        $produk = Produk::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil ditambahkan',
            'data' => $produk
        ], 201);
    }

    public function update(Request $request, Produk $produk) // Menggunakan Route Model Binding
    {
        // 1. Validasi Input
        $validator = Validator::make($request->all(), [
            // Pastikan barcode unik, kecuali untuk produk yang sedang diperbarui
            'barcode' => 'nullable|string|max:255|unique:produks,barcode,' . $produk->id, // <-- Tambahkan ini
            'nama_produk' => 'required|string|max:255',
            'deskripsi_produk' => 'nullable|string|max:2000',
            'id_kategori' => 'required|exists:kategoris,id',
            'id_merek' => 'required|exists:mereks,id',
            'harga_modal' => 'required|numeric|min:0',
            'harga_jual' => 'required|numeric|min:0|gt:harga_modal',
            'jumlah_stok' => 'required|integer|min:0',
            'gambar_produk' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();

        // 2. Simpan Gambar Produk Baru dan Hapus Gambar Lama (jika ada perubahan)
        if ($request->hasFile('gambar_produk')) {
            // Hapus gambar lama jika ada
            if ($produk->gambar_produk && Storage::disk('public')->exists($produk->gambar_produk)) {
                Storage::disk('public')->delete($produk->gambar_produk);
            }
            $path = $request->file('gambar_produk')->store('produk', 'public');
            $validated['gambar_produk'] = $path;
        } elseif ($request->boolean('hapus_gambar_produk')) {
            if ($produk->gambar_produk && Storage::disk('public')->exists($produk->gambar_produk)) {
                Storage::disk('public')->delete($produk->gambar_produk);
            }
            $validated['gambar_produk'] = null;
        } else {
            // Penting: Jangan menimpa gambar lama jika tidak ada upload baru
            // Dan jangan juga menghapus nilai yang sudah ada jika tidak ada input 'gambar_produk' atau 'hapus_gambar_produk'
            // Jika Anda ingin memperbarui tanpa mengubah gambar, pastikan 'gambar_produk' tidak ada di $request atau kosongkan secara eksplisit.
            // Jika Anda hanya ingin tidak mengubah gambar, baris ini sudah cukup:
            unset($validated['gambar_produk']);
        }

        // 3. Update Produk
        // $validated sekarang sudah termasuk 'barcode' dan 'deskripsi_produk'
        $produk->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil diperbarui',
            'data' => $produk
        ]);
    }

    public function destroy($id)
    {
        $produk = Produk::find($id);
        if (!$produk) {
            return response()->json(['message' => 'Produk tidak ditemukan'], 404);
        }

        // Hapus gambar terkait jika ada sebelum menghapus produk
        if ($produk->gambar_produk && Storage::disk('public')->exists($produk->gambar_produk)) {
            Storage::disk('public')->delete($produk->gambar_produk);
        }

        $produk->delete();

        return response()->json(['message' => 'Produk berhasil dihapus']);
    }
}