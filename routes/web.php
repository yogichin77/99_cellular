<?php
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;


Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('view_produk', function () {
    return Inertia::render('Public_View/View_Produk');
})->name('lihatproduk');


Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');


    Route::get('point_of_sales', function () {
        return Inertia::render('Pos_View/Point_Of_Sales');
    })->name('Kasir')->middleware('role:kasir');


    Route::middleware('role:admin')->group(function () {
        Route::get('produk', function () {
            return Inertia::render('Manajemen_Data/Produk');
        })->name('Produk');

        Route::get('kategori', function () {
            return Inertia::render('Manajemen_Data/Kategori');
        })->name('Kategori');

        Route::get('merek', function () {
            return Inertia::render('Manajemen_Data/Merek');
        })->name('Merek');

        Route::get('pelanggan', function () {
            return Inertia::render('Manajemen_Data/Pelanggan');
        })->name('Pelanggan');
        Route::get('data_transaksi', function () {
            return Inertia::render('Manajemen_Data/Data_Transaksi');
        })->name('DataTransaksi');
        Route::get('user', function () {
            return Inertia::render('Manajemen_Data/User');
        })->name('DataUser');
    });
});




require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
require __DIR__ . '/api.php';




