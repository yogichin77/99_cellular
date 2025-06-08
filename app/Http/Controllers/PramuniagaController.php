<?php

namespace App\Http\Controllers;

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
}
