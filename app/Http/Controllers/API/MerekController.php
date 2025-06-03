<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Merek;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MerekController extends Controller
{
    public function index()
    {
        $mereks = Merek::all();
        return response()->json([
            'success' => true,
            'message' => 'Data Merek berhasil diambil',
            'data' => $mereks
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_merek' => 'required|string|max:255|unique:mereks,nama_merek',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $merek = Merek::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Merek berhasil ditambahkan',
            'data' => $merek
        ], 201);
    }

    public function show(string $id)
    {
        $merek = Merek::find($id);

        if (!$merek) {
            return response()->json([
                'success' => false,
                'message' => 'Merek tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail merek berhasil diambil',
            'data' => $merek
        ]);
    }

    public function update(Request $request, string $id)
    {
        $merek = Merek::find($id);

        if (!$merek) {
            return response()->json([
                'success' => false,
                'message' => 'Merek tidak ditemukan'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nama_merek' => 'required|string|max:255|unique:mereks,nama_merek,' . $id,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $merek->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Merek berhasil diperbarui',
            'data' => $merek
        ]);
    }

    public function destroy(string $id)
    {
        $merek = Merek::find($id);

        if (!$merek) {
            return response()->json([
                'success' => false,
                'message' => 'Merek tidak ditemukan'
            ], 404);
        }

        if ($merek->produks()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat menghapus merek karena memiliki produk terkait'
            ], 400);
        }

        $merek->delete();

        return response()->json([
            'success' => true,
            'message' => 'Merek berhasil dihapus'
        ]);
    }
}
