<?php

use App\Http\Controllers\FacturaPdfController;
use App\Livewire\MisFacturas;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard & Redirection
    Route::get('dashboard', function () {
        if (auth()->user()->is_customer) {
            return redirect()->route('mis.facturas');
        }

        return view('dashboard');
    })->name('dashboard');

    // --- PUBLIC / CUSTOMER AREA ---
    Route::get('/mis-facturas', MisFacturas::class)->name('mis.facturas');

    // Shared Document Routes
    Route::get('/reporte-facturas/pdf/{tipo}/{n_compro}', [FacturaPdfController::class, 'show'])->name('reporte.facturas.pdf');
    Route::get('/reporte-facturas/kude', [FacturaPdfController::class, 'downloadKude'])->name('reporte.facturas.kude');
});

require __DIR__.'/settings.php';
