<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tabla de caché para respuestas de la API de facturación electrónica (CDC).
     * Evita llamadas repetidas a la API para el mismo CDC.
     */
    public function up(): void
    {
        Schema::create('cdc_cache', function (Blueprint $table) {
            $table->string('cdc', 44)->primary(); // CDC tiene siempre 44 caracteres
            $table->longText('payload');          // Respuesta completa de la API (almacenada como texto largo compatible)
            $table->boolean('success')->default(true); // Si la respuesta fue exitosa
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cdc_cache');
    }
};
