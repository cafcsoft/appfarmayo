<?php

use App\Livewire\ReporteFacturas;
use Illuminate\Support\Facades\Route;

// All routes here are automatically prefixed with /admin and protected by auth and access-admin-reports gate
Route::get('/reporte-facturas', ReporteFacturas::class)->name('reporte.facturas');
Route::get('/usuarios', \App\Livewire\Admin\Users::class)->name('usuarios');
