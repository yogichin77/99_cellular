    public function getRetur($id)
    {
        try {
            $retur = ReturPenjualan::with(['detailRetur.detailTransaksi.produk'])
                ->where('id_transaksi', $id)
                ->firstOrFail();

            return response()->json([
                'message' => 'Detail retur berhasil dimuat',
                'data' => $retur
            ], 200);
        } catch (\Exception $e) {
            return $this->errorResponse('Data retur tidak ditemukan', $e, 404);
        }
    }

        public function returPenjualan store (Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id_transaksi' => 'required|exists:transaksis,id',
                'alasan' => 'required|string|max:255',
                'items' => 'required|array|min:1',
                'items.*.id_detail_transaksi' => 'required|exists:detail_transaksis,id',
                'items.*.jumlah' => 'required|integer|min:1',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            return DB::transaction(function () use ($request) {
                $transaksi = Transaksi::findOrFail($request->id_transaksi);

                // Validasi status transaksi
                if ($transaksi->status_pembayaran !== 'cash') {
                    return response()->json([
                        'message' => 'Hanya transaksi cash yang bisa diretur'
                    ], 400);
                }

                // Buat retur penjualan
                $retur = ReturPenjualan::create([
                    'id_transaksi' => $transaksi->id,
                    'tanggal_retur' => now(),
                    'alasan' => $request->alasan,
                    'total_retur' => 0, // Akan diupdate setelah perhitungan
                ]);

                $totalRetur = 0;

                // Proses setiap item retur
                foreach ($request->items as $item) {
                    $detailTransaksi = DetailTransaksi::findOrFail($item['id_detail_transaksi']);

                    // Validasi jumlah retur tidak melebihi jumlah yang dibeli
                    if ($item['jumlah'] > $detailTransaksi->jumlah) {
                        throw new \Exception("Jumlah retur untuk produk {$detailTransaksi->produk->nama_produk} melebihi jumlah pembelian");
                    }

                    // Kembalikan stok produk
                    $produk = $detailTransaksi->produk;
                    $produk->increment('jumlah_stok', $item['jumlah']);

                    // Hitung nilai retur
                    $subtotalRetur = $item['jumlah'] * $detailTransaksi->harga_satuan;
                    $totalRetur += $subtotalRetur;

                    // Catat detail retur
                    DetailReturPenjualan::create([
                        'id_retur_penjualan' => $retur->id,
                        'id_detail_transaksi' => $detailTransaksi->id,
                        'jumlah' => $item['jumlah'],
                        'harga_satuan' => $detailTransaksi->harga_satuan,
                        'subtotal' => $subtotalRetur,
                    ]);
                }

                // Update total retur
                $retur->update(['total_retur' => $totalRetur]);

                return response()->json([
                    'message' => 'Retur berhasil diproses',
                    'data' => $retur->load('detailRetur.detailTransaksi.produk')
                ], 201);
            });
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal memproses retur', $e);
        }
    }