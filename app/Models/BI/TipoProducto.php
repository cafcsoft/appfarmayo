<?php

namespace App\Models\BI;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo para categorías/tipos de producto.
 *
 * Conecta a deposito.tbltipos.
 * Se une con deposito.productos por la columna `tipo`.
 */
class TipoProducto extends Model
{
    protected $connection = 'deposito';

    protected $table = 'tbltipos';

    protected $primaryKey = 'tipo';

    public $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    // -----------------------------------------------------------------------
    // Relaciones
    // -----------------------------------------------------------------------

    /** Todos los productos de este tipo/categoría. */
    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class, 'tipo', 'tipo');
    }
}
