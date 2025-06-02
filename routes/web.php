<?php

use App\Http\Controllers\API\ProdukController;
use App\Http\Controllers\API\KategoriController;
use App\Http\Controllers\Api\MerekController;
use App\Http\Controllers\Api\TransaksiController;
use App\Http\Controllers\API\PelangganController;
use App\Http\Controllers\API\UserController;
use Illuminate\Support\Facades\Route;


use Inertia\Inertia;



Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');
Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->name('dashboard');
Route::get('kasir', function () {
    return Inertia::render('Kasir');
})->name('Kasir');
Route::get('produk', function () {
    return Inertia::render('Produk');
})->name('Produk');
Route::get('kategori', function () {
    return Inertia::render('Kategori');
})->name('Kategori');
Route::get('merek', function () {
    return Inertia::render('Merek');
})->name('Merek');
Route::get('pelanggan', function () {
    return Inertia::render('Pelanggan');
})->name('Pelanggan');
Route::get('datatransaksi', function () {
    return Inertia::render('DataTransaksi');
})->name('DataTransaksi');
Route::get('user', function () {
    return Inertia::render('User');
})->name('DataUser');





require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';


Route::get('api/produk', [ProdukController::class, 'index']);
Route::post('api/produk', [ProdukController::class, 'store']);
Route::put('api/produk/{produk}', [ProdukController::class, 'update']);
Route::delete('api/produk/{produk}', [ProdukController::class, 'destroy']);

Route::get('api/kategori', [KategoriController::class, 'index']);
Route::post('api/kategori', [KategoriController::class, 'store']);
Route::get('api/kategori/{id}', [KategoriController::class, 'show']);
Route::put('api/kategori/{kategori}', [KategoriController::class, 'update']);
Route::delete('api/kategori/{kategori}', [KategoriController::class, 'destroy']);

Route::get('api/merek', [MerekController::class, 'index']);
Route::post('api/merek', [MerekController::class, 'store']);
Route::get('api/merek/{id}', [MerekController::class, 'show']);
Route::put('api/merek/{merek}', [MerekController::class, 'update']);
Route::delete('api/merek/{merek}', [MerekController::class, 'destroy']);

Route::get('api/transaksi', [TransaksiController::class, 'index']);
Route::get('api/transaksi/{id}', [TransaksiController::class, 'show']);
Route::post('api/transaksi', [TransaksiController::class, 'store']);
Route::put('api/transaksi/{id}', [TransaksiController::class, 'update']);
Route::delete('api/transaksi/{id}', [TransaksiController::class, 'destroy']);

Route::get('api/pelanggan', [PelangganController::class, 'index']);
Route::post('api/pelanggan', [PelangganController::class, 'store']);
Route::get('api/pelanggan/{id}', [PelangganController::class, 'show']);
Route::put('api/pelanggan/{pelanggan}', [PelangganController::class, 'update']);
Route::delete('api/pelanggan/{pelanggan}', [PelangganController::class, 'destroy']);

Route::get('api/user', [UserController::class, 'index']);
Route::post('api/user', [UserController::class, 'store']);
Route::get('api/user/{id}', [UserController::class, 'show']);
Route::put('api/user/{id}', [UserController::class, 'update']);
Route::delete('api/user/{id}', [UserController::class, 'destroy']);
