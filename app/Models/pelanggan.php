<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pelanggan extends Model
{

    protected $fillable = ['nama_pelanggan', 'no_handphone', 'alamat', 'nama_toko'];
    public $timestamps = true;


public function transaksis()
{
    return $this->hasMany(Transaksi::class, 'id_pelanggan', );
}

}