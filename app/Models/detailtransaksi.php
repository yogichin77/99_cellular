<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DetailTransaksi extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_transaksi',
        'id_produk',
        'jumlah',
        'harga_satuan',
        'total_harga',
    ];

    public function transaksi()
    {
        return $this->belongsTo(Transaksi::class, 'id_transaksi', 'id');
    }

    public function produk()
    {
        return $this->belongsTo(Produk::class, 'id_produk', 'id_produk');
    }

}
