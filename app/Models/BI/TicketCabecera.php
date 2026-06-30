<?php

namespace App\Models\BI;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para la cabecera de tickets (ventas en caja/mostrador).
 *
 * Conecta a villamorra.ticket_cab.
 * Los tickets son la contraparte de las facturas para clientes sin RUC.
 */
class TicketCabecera extends Model
{
    protected $connection = 'mysql';

    protected $table = 'ticket_cab';

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

    /** Líneas de detalle del ticket. */
    public function lineas(): HasMany
    {
        return $this->hasMany(TicketLinea::class, 'n_compro', 'n_compro');
    }

    /** Datos del cliente desde farmacia.client. */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(ClienteTicket::class, 'idcliente', 'IDCLIENTE');
    }

    /** Datos del vendedor desde farmacia.vendedor. */
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

    /** Limita a los últimos 5 años. */
    public function scopeUltimosCincoAnios(Builder $query): Builder
    {
        return $query->where('fecha', '>=', now()->subYears(5)->startOfYear());
    }

    /** Filtra por rango de fechas. */
    public function scopeEntreFechas(Builder $query, string $desde, string $hasta): Builder
    {
        return $query->whereBetween('fecha', [$desde, $hasta]);
    }

    /** Excluye tickets sin cliente identificado. */
    public function scopeConCliente(Builder $query): Builder
    {
        return $query->whereNotNull('idcliente')
            ->where('idcliente', '!=', 0)
            ->where('idcliente', '!=', '');
    }
}
