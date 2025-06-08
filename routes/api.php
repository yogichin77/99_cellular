<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProdukController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\MerekController;
use App\Http\Controllers\TransaksiController;
use App\Http\Controllers\PelangganController;
use App\Http\Controllers\UserController;

// Publicly accessible routes (no authentication required)
// Example: A route for fetching products for a public facing e-commerce page
Route::get('api/produk/publicindex', [ProdukController::class, 'publicIndex']);


// Routes that require authentication (verified users only)
Route::middleware('auth')->group(function () {

    // Produk Routes
    Route::get('api/produk', [ProdukController::class, 'index']);
    Route::post('api/produk', [ProdukController::class, 'store']);
    Route::put('api/produk/{produk}', [ProdukController::class, 'update']);
    Route::delete('api/produk/{produk}', [ProdukController::class, 'destroy']);

    // Kategori Routes
    Route::get('api/kategori', [KategoriController::class, 'index']);
    Route::post('api/kategori', [KategoriController::class, 'store']);
    Route::get('api/kategori/{id}', [KategoriController::class, 'show']);
    Route::put('api/kategori/{kategori}', [KategoriController::class, 'update']);
    Route::delete('api/kategori/{kategori}', [KategoriController::class, 'destroy']);

    // Merek Routes
    Route::get('api/merek', [MerekController::class, 'index']);
    Route::post('api/merek', [MerekController::class, 'store']);
    Route::get('api/merek/{id}', [MerekController::class, 'show']);
    Route::put('api/merek/{produk}', [MerekController::class, 'update']);
    Route::delete('api/merek/{produk}', [MerekController::class, 'destroy']);

    // Transaksi Routes
    Route::get('api/transaksi', [TransaksiController::class, 'index']);

    Route::get('api/transaksi/{id}', [TransaksiController::class, 'show']);
    Route::post('api/transaksi', [TransaksiController::class, 'store']);
    Route::put('api/transaksi/{transaksi}', [TransaksiController::class, 'update']);
    Route::delete('api/transaksi/{id}', [TransaksiController::class, 'destroy']);

    // Pelanggan Routes
    Route::get('api/pelanggan', [PelangganController::class, 'index']);
    Route::post('api/pelanggan', [PelangganController::class, 'store']);
    Route::get('api/pelanggan/{id}', [PelangganController::class, 'show']);
    Route::put('api/pelanggan/{id}', [PelangganController::class, 'update']);
    Route::delete('api/pelanggan/{id}', [PelangganController::class, 'destroy']);

    // User Routes
    Route::get('api/user', [UserController::class, 'index']);
    Route::post('api/user', [UserController::class, 'store']);
    Route::get('api/user/{id}', [UserController::class, 'show']);
    Route::put('api/user/{id}', [UserController::class, 'update']);
    Route::delete('api/user/{id}', [UserController::class, 'destroy']);

    Route::get('api/dashboard/status', [TransaksiController::class, 'getTransactionStatus']);
    Route::get('api/dashboard/monthly-revenue', [TransaksiController::class, 'getMonthlyRevenue']);
    Route::get('api/dashboard/summary', [TransaksiController::class, 'getSummaryStatistics']);
    Route::get('api/dashboard/annual-revenue', [TransaksiController::class, 'getAnnualRevenue']);
    Route::get('api/dashboard/sales-by-category', [TransaksiController::class, 'getSalesByCategory']);
    Route::get('api/dashboard/quick-summary', [TransaksiController::class, 'getQuickSummary']);
    Route::get('api/reports/sales', [TransaksiController::class, 'getSalesReport']);
    Route::get('api/reports/sales/export-pdf', [TransaksiController::class, 'exportSalesPdf'])->name('reports.sales.pdf');



    Route::get('api/reports/produk/export-stock-pdf', [ProdukController::class, 'exportStockPdf'])->name('reports.produk.stock.pdf');
});
