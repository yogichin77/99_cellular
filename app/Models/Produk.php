<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Produk extends Model
{
    use HasFactory;

    // Tulis primary key sesuai migrasi kamu
    protected $primaryKey = 'id_produk';
    protected $table = 'produks';

    // Jika primary key bukan auto increment integer biasa, set juga ini:
    // protected $keyType = 'int'; // Default sudah int, cukup kalau kamu pakai string ganti ini

    // Kalau kamu pakai auto increment, pastikan:
    public $incrementing = true;

    protected $fillable = [
        'nama_produk',
        'id_kategori',
        'id_merek',
        'harga_modal',
        'harga_jual',
        'jumlah_stok',
        'gambar_produk',
    ];

    // Relasi ke kategori
    public function kategori()
    {
        return $this->belongsTo(Kategori::class, 'id_kategori');
    }

    // Relasi ke merek
    public function merek()
    {
        return $this->belongsTo(Merek::class, 'id_merek');
    }

    // app/Models/Produk.php

    public function detailTransaksis()
    {
        return $this->hasMany(DetailTransaksi::class, 'id_produk');
    }
}
