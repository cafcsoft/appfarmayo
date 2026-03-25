<?php

use App\Livewire\ReporteFacturas;
use Illuminate\Support\Facades\Route;

// All routes here are automatically prefixed with /admin and protected by auth and access-admin-reports gate
Route::get('/reporte-facturas', ReporteFacturas::class)->name('reporte.facturas');

// If you want to keep PDF/KUDE in the admin namespace or keep them shared in web.php
// For now, let's keep the main report here.
