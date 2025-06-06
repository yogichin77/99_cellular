<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;

use Illuminate\Database\Eloquent\Model;

class Transaksi extends Model
{
    use HasFactory;



    protected $fillable = [
        'sub_total',
        'total_bayar',
        'total_kurang',
        'status_pembayaran',
        'jatuh_tempo',
        'diskon',
        'id_pelanggan',
        'id_user',
    ];


        protected $casts = [
        'sub_total' => 'decimal:2', // Menggunakan 'decimal:2' untuk 2 angka di belakang koma
        'diskon' => 'decimal:2',
        'total_bayar' => 'decimal:2',
        'total_kurang' => 'decimal:2',
        'created_at' => 'datetime', // Untuk memastikan ini adalah objek DateTime
        'updated_at' => 'datetime',
        'jatuh_tempo' => 'date', // Jika ini hanya tanggal
    ];


    public function detailtransaksis()
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
