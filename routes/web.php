<?php

use App\Http\Controllers\FacturaPdfController;
use App\Livewire\MisFacturas;
use App\Livewire\BI\Dashboard as BIDashboard;
use App\Livewire\BI\AnalisisVentas;
use App\Livewire\BI\AnalisisProductos;
use App\Livewire\BI\AnalisisClientes;
use App\Livewire\BI\Forecasting;
use App\Http\Controllers\WelcomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', WelcomeController::class)->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard & Redirection
    Route::get('dashboard', function () {
        if (auth()->user()->is_customer) {
            return redirect()->route('mis.facturas');
        }

        return redirect()->route('bi.dashboard');
    })->name('dashboard');

    // --- PUBLIC / CUSTOMER AREA ---
    Route::get('/mis-facturas', MisFacturas::class)->name('mis.facturas');

    // Shared Document Routes
    Route::get('/reporte-facturas/pdf/{tipo}/{n_compro}', [FacturaPdfController::class, 'show'])->name('reporte.facturas.pdf');
    Route::get('/reporte-facturas/kude', [FacturaPdfController::class, 'downloadKude'])->name('reporte.facturas.kude');

    // -----------------------------------------------------------------------
    // Módulo BI — solo administradores (gate: access-admin-reports)
    // -----------------------------------------------------------------------
    Route::middleware(['can:access-admin-reports'])->prefix('bi')->name('bi.')->group(function () {
        Route::get('/dashboard',    BIDashboard::class)->name('dashboard');
        Route::get('/ventas',       AnalisisVentas::class)->name('ventas');
        Route::get('/productos',    AnalisisProductos::class)->name('productos');
        Route::get('/clientes',     AnalisisClientes::class)->name('clientes');
        Route::get('/forecasting',  Forecasting::class)->name('forecasting');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';

