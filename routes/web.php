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

        if (auth()->user()->roles()->where('name', 'contador')->exists()) {
            return redirect()->route('contabilidad.dashboard');
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

    // Módulo Contabilidad — solo contadores (gate: access-accounting)
    Route::middleware(['can:access-accounting'])->prefix('contabilidad')->name('contabilidad.')->group(function () {
        Route::get('/dashboard', \App\Livewire\Contabilidad\Dashboard::class)->name('dashboard');
        Route::get('/reporte-facturas', \App\Livewire\ReporteFacturas::class)->name('reporte.facturas');
        Route::get('/libro-ventas-iva', \App\Livewire\Contabilidad\LibroVentasIva::class)->name('libro-ventas-iva');
        Route::get('/libro-ventas-iva/print', \App\Http\Controllers\Contabilidad\LibroIvaPrintController::class)->name('libro-iva.print');
    });
});

Route::get('forgot-password', \App\Livewire\Auth\ForgotPassword::class)
    ->name('password.request')
    ->middleware('guest');

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';

