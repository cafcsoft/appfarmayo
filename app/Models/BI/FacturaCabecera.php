<?php

namespace App\Models\BI;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para la cabecera de facturas.
 *
 * Conecta a villamorra.fact_cab (conexión por defecto).
 * Solo se consideran facturas no anuladas de los últimos 5 años.
 */
class FacturaCabecera extends Model
{
    protected $connection = 'mysql';

    protected $table = 'fact_cab';

    protected $primaryKey = 'n_compro';

    public $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $casts = [
        'fecha'      => 'date',
        'fechayhora' => 'datetime',
        'anulado'    => 'boolean',
    ];

    // -----------------------------------------------------------------------
    // Relaciones
    // -----------------------------------------------------------------------

    /** Líneas de detalle de la factura. */
    public function lineas(): HasMany
    {
        return $this->hasMany(FacturaLinea::class, 'n_compro', 'n_compro');
    }

    /** Datos del cliente desde la base farmacia. */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(ClienteFactura::class, 'codi_clie', 'codi_clie');
    }

    /** Datos del vendedor desde la base farmacia. */
    public function vendedor(): BelongsTo
    {
        return $this->belongsTo(Vendedor::class, 'codi_vend', 'codigo');
    }

    // -----------------------------------------------------------------------
    // Scopes globales de negocio
    // -----------------------------------------------------------------------

    /** Excluye cabeceras anuladas. */
    public function scopeSinAnular(Builder $query): Builder
    {
        return $query->where('anulado', 0);
    }

    /** Limita a los últimos 5 años completos. */
    public function scopeUltimosCincoAnios(Builder $query): Builder
    {
        return $query->where('fecha', '>=', now()->subYears(5)->startOfYear());
    }

    /** Filtra por rango de fechas. */
    public function scopeEntreFechas(Builder $query, string $desde, string $hasta): Builder
    {
        return $query->whereBetween('fecha', [$desde, $hasta]);
    }

    /** Excluye clientes genéricos (sin nombre / código 0). */
    public function scopeConCliente(Builder $query): Builder
    {
        return $query->whereNotNull('codi_clie')
            ->where('codi_clie', '!=', 0)
            ->where('codi_clie', '!=', '')
            ->where('codi_clie', '!=', '13350')
            ->where('codi_clie', '!=', '3714389-1')
            ->where('codi_clie', '!=', '80051943-4');
    }
}
