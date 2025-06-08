<!DOCTYPE html>
<html>
<head>
    <title>Laporan Stok Produk</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            margin: 20mm; /* Menambah margin untuk tampilan yang lebih baik */
        }
        h1 {
            text-align: center;
            font-size: 18px;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
            vertical-align: top; /* Agar teks tidak terlalu rapat */
        }
        th {
            background-color: #f2f2f2;
            text-align: center;
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .footer {
            position: fixed;
            bottom: -15mm;
            left: 0;
            right: 0;
            height: 10mm;
            text-align: center;
            font-size: 8px;
            color: #555;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <h1>Laporan Stok Produk</h1>
    <p>Tanggal Cetak: {{ \Carbon\Carbon::now()->format('d F Y H:i:s') }}</p>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Barcode</th>
                <th>Nama Produk</th>
                <th>Kategori</th>
                <th>Merek</th>
                <th class="text-right">Harga Jual</th>
                <th class="text-right">Stok</th>
                <th>Deskripsi Produk</th>
            </tr>
        </thead>
        <tbody>
            @forelse($produk as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $item->barcode ?? '-' }}</td>
                <td>{{ $item->nama_produk }}</td>
                <td>{{ $item->kategori->nama_kategori ?? '-' }}</td>
                <td>{{ $item->merek->nama_merek ?? '-' }}</td>
                <td class="text-right">Rp{{ number_format($item->harga_jual, 0, ',', '.') }}</td>
                <td class="text-right">{{ $item->jumlah_stok }}</td>
                <td>{{ \Illuminate\Support\Str::limit($item->deskripsi_produk, 100) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="9" class="text-center">Tidak ada produk yang ditemukan.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Laporan Stok Produk - Dihasilkan oleh Sistem Anda
    </div>
</body>
</html>