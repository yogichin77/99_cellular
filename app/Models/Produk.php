<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Produk extends Model
{
    use HasFactory;

    protected $table = 'produks';

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

    // Relasi ke detail transaksi
    public function detailtransaksis()
    {
        return $this->hasMany(DetailTransaksi::class, 'id_produk');
    }
}
