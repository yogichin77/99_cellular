<!DOCTYPE html>
<html>
<head>
    <title>Laporan Penjualan</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            margin: 1.5cm;
        }
        .header {
            text-align: center;
            margin-bottom: 25px;
            border-bottom: 1.5px solid #000;
            padding-bottom: 15px;
        }
        .header h1 {
            margin: 0;
            font-size: 22px;
            letter-spacing: 1px;
        }
        .header p {
            margin: 3px 0;
            font-size: 12px;
        }
        .report-info {
            margin-bottom: 20px;
            font-size: 11px;
            border: 1px solid #333;
            padding: 12px;
            background-color: #f9f9f9;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
            table-layout: fixed;
        }
        th, td {
            border: 1px solid #333;
            padding: 8px;
            text-align: left;
            word-wrap: break-word;
        }
        th {
            background-color: #eaeaea;
            font-weight: 600;
        }
        .text-right {
            text-align: right;
        }
        .summary-section {
            margin-top: 30px;
            font-size: 12px;
            text-align: right;
            border-top: double 3px #333;
            padding-top: 15px;
        }
        .summary-section div {
            margin-bottom: 7px;
        }
        .signature-area {
            margin-top: 50px;
            float: right;
            text-align: center;
            width: 60%;
        }
        .official-stamp {
            margin-top: 25px;
            text-align: left;
            font-style: italic;
            color: #555;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>LAPORAN PENJUALAN RESMI</h1>
        <p><strong>[Nama Perusahaan]</strong></p>
        <p>[Alamat Lengkap Perusahaan]</p>
        <p>Telepon: [Nomor Telepon] | Email: [Alamat Email]</p>
    </div>

    <div class="report-info">
        <h3>PARAMETER LAPORAN:</h3>
        <p><strong>Periode:</strong> {{ $filters['start_date'] ? \Carbon\Carbon::parse($filters['start_date'])->isoFormat('D MMMM YYYY') : 'Awal' }} s.d. {{ $filters['end_date'] ? \Carbon\Carbon::parse($filters['end_date'])->isoFormat('D MMMM YYYY') : 'Sekarang' }}</p>
        <p><strong>Metode Pembayaran:</strong> {{ $filters['status'] == 'all' ? 'Semua Metode' : ucfirst($filters['status']) }}</p>
        <p><strong>Tanggal Cetak:</strong> {{ \Carbon\Carbon::now()->isoFormat('D MMMM YYYY, HH:mm') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:7%">ID Transaksi</th>
                <th style="width:12%">Tanggal/Waktu</th>
                <th style="width:15%">Pelanggan</th>
                <th style="width:10%">Kasir</th>
                <th style="width:8%">Subtotal</th>
                <th style="width:7%">Diskon</th>
                <th style="width:9%">Total Dibayar</th>
                <th style="width:9%">Saldo Piutang</th>
                <th style="width:10%">Metode Pembayaran</th>
                <th style="width:13%">Jatuh Tempo</th>
            </tr>
        </thead>
        <tbody>
            @php
                $grandTotalSales = 0;
                $grandTotalDiscount = 0;
                $grandTotalPaid = 0;
                $grandTotalOutstanding = 0;
            @endphp
            @foreach($transactions as $transaksi)
                <tr>
                    <td>{{ $transaksi->id }}</td>
                    <td>{{ \Carbon\Carbon::parse($transaksi->created_at)->isoFormat('D MMM YYYY, HH:mm') }}</td>
                    <td>
                        {{ $transaksi->pelanggan->nama_pelanggan ?? 'Pelanggan Umum' }}
                        @if($transaksi->pelanggan->nama_toko ?? false)
                            <br>({{ $transaksi->pelanggan->nama_toko }})
                        @endif
                    </td>
                    <td>{{ $transaksi->user->name ?? '-' }}</td>
                    <td class="text-right">{{ number_format($transaksi->sub_total, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($transaksi->diskon, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($transaksi->total_bayar, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($transaksi->total_kurang, 0, ',', '.') }}</td>
                    <td>{{ ucfirst($transaksi->metode_pembayaran) }}</td>
                    <td>{{ $transaksi->jatuh_tempo ? \Carbon\Carbon::parse($transaksi->jatuh_tempo)->isoFormat('D MMMM YYYY') : '-' }}</td>
                </tr>
                @php
                    $grandTotalSales += $transaksi->sub_total;
                    $grandTotalDiscount += $transaksi->diskon;
                    $grandTotalPaid += $transaksi->total_bayar;
                    $grandTotalOutstanding += $transaksi->total_kurang;
                @endphp
            @endforeach
        </tbody>
    </table>

    <div class="summary-section">
        <div>Total Subtransaksi: <strong>Rp {{ number_format($grandTotalSales, 0, ',', '.') }}</strong></div>
        <div>Total Potongan Harga: <strong>Rp {{ number_format($grandTotalDiscount, 0, ',', '.') }}</strong></div>
        <div>Total Pembayaran Diterima: <strong>Rp {{ number_format($grandTotalPaid, 0, ',', '.') }}</strong></div>
        <div>Total Piutang Usaha: <strong>Rp {{ number_format($grandTotalOutstanding, 0, ',', '.') }}</strong></div>
        <div>Jumlah Transaksi: <strong>{{ count($transactions) }}</strong></div>
    </div>

    <div class="signature-area">
        <p>Pontianak, {{ \Carbon\Carbon::now()->isoFormat('D MMMM YYYY') }}</p>
        <p>Mengetahui,</p>
        <p>Manajer Penjualan</p>
        <br><br><br>
        <p>(__________________________)</p>
        <p style="margin-top:3px">[Nama Lengkap & Cap Stempel]</p>
    </div>

    <div class="official-stamp">
        <p>Dokumen ini sah sebagai laporan keuangan resmi</p>
        <p>Dicetak pada: {{ \Carbon\Carbon::now()->isoFormat('D MMMM YYYY HH:mm') }}</p>
    </div>
</body>
</html>