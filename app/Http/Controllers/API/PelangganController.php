<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Pelanggan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class PelangganController extends Controller
{
    public function index()
    {
        $pelanggans = Pelanggan::all();
        return response()->json([
            'success' => true,
            'message' => 'Data Pelanggan berhasil diambil',
            'data' => $pelanggans
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_pelanggan' => 'required|string|max:255|unique:transaksis,nama_pelanggan',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $merek = Pelanggan::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Pelanggan berhasil ditambahkan',
            'data' => $merek
        ], 201);
    }

    public function show(string $id)
    {
        $pelanggan = Pelanggan::where('id_pelanggan', $id)->first();

        if (!$pelanggan) {
            return response()->json([
                'success' => false,
                'message' => 'Pelanggan tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail pelanggan berhasil diambil',
            'data' => $pelanggan
        ]);
    }

    public function update(Request $request, string $id)
    {
        $pelanggan = Pelanggan::where('id_pelanggan', $id)->first();

        if (!$pelanggan) {
            return response()->json([
                'success' => false,
                'message' => 'Pelanggan tidak ditemukan'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nama_pelanggan' => 'required|string|max:255|unique:pelanggans,nama_pelanggan,' . $id . ',id_pelanggan',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $pelanggan->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Pelanggan berhasil diperbarui',
            'data' => $pelanggan
        ]);
    }

    public function destroy(string $id)
    {
        $pelanggan = Pelanggan::where('id_pelanggan', $id)->first();

        if (!$pelanggan) {
            return response()->json([
                'success' => false,
                'message' => 'Pelanggan tidak ditemukan'
            ], 404);
        }

        if ($pelanggan->transaksis()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat menghapus pelanggan karena memiliki transaksi terkait'
            ], 400);
        }

        $pelanggan->delete();

        return response()->json([
            'success' => true,
            'message' => 'pelanggan berhasil dihapus'
        ]);
    }
}
