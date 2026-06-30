<?php

namespace App\Models\BI;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para clientes de facturas.
 *
 * Conecta a farmacia.clientes.
 * Relación: villamorra.fact_cab.codi_clie → farmacia.clientes.codi_clie
 */
class ClienteFactura extends Model
{
    protected $connection = 'farmacia';

    protected $table = 'clientes';

    protected $primaryKey = 'codi_clie';

    public $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $casts = [
        'fecha_nac'   => 'date',
        'bloqueado'   => 'boolean',
        'condescue'   => 'boolean',
        'limitecred'  => 'float',
        'saldo'       => 'float',
    ];

    // -----------------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------------

    /**
     * Excluye clientes genéricos sin nombre real.
     * Filtra: SIN NOMBRE, XXX, vacíos, código 0.
     */
    public function scopeReales(Builder $query): Builder
    {
        return $query
            ->whereNotNull('codi_clie')
            ->where('codi_clie', '!=', 0)
            ->where('codi_clie', '!=', '')
            ->where('codi_clie', '!=', '13350')
            ->where('codi_clie', '!=', '3714389-1')
            ->where('codi_clie', '!=', '80051943-4')
            ->whereNotNull('nomb_clie')
            ->where('nomb_clie', '!=', '')
            ->whereRaw("UPPER(TRIM(nomb_clie)) NOT IN ('SIN NOMBRE','XXX','SIN_NOMBRE','N/A')");
    }

    // -----------------------------------------------------------------------
    // Accessors
    // -----------------------------------------------------------------------

    /** Nombre completo: nombre + apellido. */
    public function getNombreCompletoAttribute(): string
    {
        return trim("{$this->nomb_clie} {$this->apel_clie}");
    }
}
