<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;

use Illuminate\Database\Eloquent\Model;

class Transaksi extends Model
{
    use HasFactory;

    protected $casts = [
        'sub_total_harga' => 'integer',
        'total_bayar' => 'integer',
        'total_kurang' => 'integer',
        'diskon' => 'integer',
        'jatuh_tempo' => 'date',
    ];

    protected $fillable = [
        'sub_total_harga',
        'total_bayar',
        'total_kurang',
        'status_pembayaran',
        'jatuh_tempo',
        'diskon',
        'id_pelanggan',
        'id_user',
    ];

    public function detailTransaksis()
    {
        return $this->hasMany(DetailTransaksi::class, 'id_transaksi');
    }

    public function pelanggan()
    {
        return $this->belongsTo(Pelanggan::class, 'id_pelanggan');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}
