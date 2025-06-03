<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detail_transaksis', function (Blueprint $table) {
            $table->id();

            $table->foreignId('id_transaksi')->constrained('transaksis');
            $table->foreignId('id_produk')->constrained('produks')->onDelete('cascade');
            $table->integer('jumlah');
            $table->decimal('harga_satuan',15,2);
            $table->decimal('total_harga',15,2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_transaksis');
    }
};
