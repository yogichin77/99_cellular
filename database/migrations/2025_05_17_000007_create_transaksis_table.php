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
        Schema::create('transaksis', function (Blueprint $table) {
            $table->id();
            $table->integer('sub_total_harga');
            $table->integer('total_bayar');
            $table->integer('total_kurang');
            $table->enum('status_pembayaran', ['cash', 'kredit']);
            $table->date('jatuh_tempo')->nullable();
            $table->integer('diskon')->default(0);
            $table->foreignId('id_pelanggan')->constrained('pelanggans')->nullable()->onDelete('cascade');
            $table->foreignId('id_user')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaksis');
    }
};
