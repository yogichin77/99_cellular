<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Produk;
use Illuminate\Http\Request;

class PramuniagaController extends Controller
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
        $validated = $request->validate([
            'nama_produk' => 'required|string|max:255',
            'id_kategori' => 'required|exists:kategoris,id',
            'id_merek' => 'required|exists:mereks,id',
            'harga_modal' => 'required|numeric|min:0',
            'harga_jual' => 'required|numeric|min:0',
            'jumlah_stok' => 'required|integer|min:0',
            'gambar_produk' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if ($request->hasFile('gambar_produk')) {
            $path = $request->file('gambar_produk')->store('produk', 'public');
            $validated['gambar_produk'] = $path;
        }

        $produk = Produk::create($validated);
        return response()->json($produk->load(['kategori', 'merek']), 201);
    }


    public function update(Request $request, Produk $produk)
    {
        $validated = $request->validate([
            'nama_produk' => 'required|string|max:255',
            'id' => 'required|exists:kategoris,id',
            'id' => 'required|exists:mereks,id',
            'harga_modal' => 'required|numeric|min:0',
            'harga_jual' => 'required|numeric|min:0',
            'jumlah_stok' => 'required|integer|min:0',
            'gambar_produk' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if ($request->hasFile('gambar_produk')) {
            $path = $request->file('gambar_produk')->store('produk', 'public');
            $validated['gambar_produk'] = $path;
        }

        $produk->update($validated);
        return response()->json($produk->load(['kategori', 'merek']));
    }
    public function destroy($id)
    {
        $produk = Produk::find($id);
        if (!$produk) {
            return response()->json(['message' => 'Produk tidak ditemukan'], 404);
        }

        $produk->delete();

        return response()->json(['message' => 'Produk berhasil dihapus']);
    }
}
