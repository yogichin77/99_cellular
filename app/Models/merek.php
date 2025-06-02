<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Merek extends Model
{
    use HasFactory;

    protected $table = 'mereks';
    protected $primaryKey = 'id_merek';
    public $timestamps = true;

    protected $fillable = [
        'nama_merek',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function produks(): HasMany
    {
        return $this->hasMany(Produk::class, 'id_merek');
    }
}