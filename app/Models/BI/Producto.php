<?php

namespace App\Models\BI;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para productos del depósito/inventario.
 *
 * Conecta a deposito.productos.
 * Se vincula a las líneas de venta por el campo `codigo`.
 */
class Producto extends Model
{
    protected $connection = 'deposito';

    protected $table = 'productos';

    protected $primaryKey = 'codigo';

    public $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $casts = [
        'precio'    => 'float',
        'costo'     => 'float',
        'stock'     => 'float',
    ];

    // -----------------------------------------------------------------------
    // Relaciones
    // -----------------------------------------------------------------------

    /** Categoría/tipo del producto. */
    public function tipo(): BelongsTo
    {
        return $this->belongsTo(TipoProducto::class, 'tipo', 'tipo');
    }
}
