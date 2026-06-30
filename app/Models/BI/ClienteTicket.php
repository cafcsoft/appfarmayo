<?php

namespace App\Models\BI;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para clientes de tickets (caja/mostrador).
 *
 * Conecta a farmacia.client.
 * Relación: villamorra.ticket_cab.idcliente → farmacia.client.IDCLIENTE
 */
class ClienteTicket extends Model
{
    protected $connection = 'farmacia';

    protected $table = 'client';

    protected $primaryKey = 'IDCLIENTE';

    public $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $casts = [
        'fecha_ingreso' => 'date',
    ];

    // -----------------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------------

    /**
     * Excluye clientes genéricos o sin identificar.
     */
    public function scopeReales(Builder $query): Builder
    {
        return $query
            ->whereNotNull('IDCLIENTE')
            ->where('IDCLIENTE', '!=', 0)
            ->where('IDCLIENTE', '!=', '')
            ->whereNotNull('NOMBRE')
            ->where('NOMBRE', '!=', '')
            ->whereRaw("UPPER(TRIM(NOMBRE)) NOT IN ('SIN NOMBRE','XXX','SIN_NOMBRE','N/A')");
    }

    // -----------------------------------------------------------------------
    // Accessors
    // -----------------------------------------------------------------------

    /** Nombre normalizado (usa NOMBRE o RAZSOC si está disponible). */
    public function getNombreCompletoAttribute(): string
    {
        return trim($this->NOMBRE ?? $this->RAZSOC ?? '');
    }
}
