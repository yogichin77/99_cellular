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
            $table->decimal('sub_total', 12, 2);
            $table->decimal('total_bayar', 12, 2);
            $table->decimal('total_kurang', 12, 2);
            $table->enum('status_pembayaran', ['cash', 'kredit']);
            $table->date('jatuh_tempo')->nullable();
            $table->decimal('diskon', 12, 2);
            $table->foreignId('id_pelanggan')->nullable()->constrained('pelanggans')->onDelete('cascade')->change();
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
