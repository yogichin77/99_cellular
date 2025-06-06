<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('produks', function (Blueprint $table) {
            $table->id();
            $table->string('barcode')->unique()->nullable(); // Tambahkan kolom barcode di sini
            $table->string('nama_produk');
            $table->foreignId('id_kategori')->constrained('kategoris')->onDelete('cascade');
            $table->foreignId('id_merek')->constrained('mereks')->onDelete('cascade');
            $table->decimal('harga_modal', 15, 2);
            $table->decimal('harga_jual', 15, 2);
            $table->integer('jumlah_stok')->default(0);
            $table->string('gambar_produk')->nullable();
            $table->longText('deskripsi_produk')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produks');
    }
};