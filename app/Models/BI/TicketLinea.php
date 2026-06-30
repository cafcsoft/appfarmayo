<?php

namespace App\Models\BI;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para las líneas de detalle de tickets.
 *
 * Conecta a villamorra.ticket_lin.
 */
class TicketLinea extends Model
{
    protected $connection = 'mysql';

    protected $table = 'ticket_lin';

    public $timestamps = false;

    protected $casts = [
        'anulado'   => 'boolean',
        'cantidad'  => 'float',
        'precio'    => 'float',
        'sub_total' => 'float',
        'descuento' => 'float',
        'total'     => 'float',
        'costo1'    => 'float',
    ];

    // -----------------------------------------------------------------------
    // Relaciones
    // -----------------------------------------------------------------------

    /** Cabecera del ticket al que pertenece la línea. */
    public function cabecera(): BelongsTo
    {
        return $this->belongsTo(TicketCabecera::class, 'n_compro', 'n_compro');
    }

    /** Datos del producto desde la base deposito. */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'codigo', 'codigo');
    }

    // -----------------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------------

    /** Excluye líneas anuladas. */
    public function scopeSinAnular(Builder $query): Builder
    {
        return $query->where('anulado', 0);
    }

    // -----------------------------------------------------------------------
    // Accessors calculados
    // -----------------------------------------------------------------------

    /** Costo total de la línea (costo unitario × cantidad). */
    public function getCostoTotalAttribute(): float
    {
        return $this->costo1 * $this->cantidad;
    }

    /** Margen bruto de la línea. */
    public function getMargenAttribute(): float
    {
        return $this->total - $this->getCostoTotalAttribute();
    }

    /** Porcentaje de margen sobre la venta. */
    public function getPorcentajeMargenAttribute(): float
    {
        if ($this->total == 0) {
            return 0;
        }

        return ($this->getMargenAttribute() / $this->total) * 100;
    }
}
