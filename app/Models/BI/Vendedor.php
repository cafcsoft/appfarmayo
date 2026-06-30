<?php

namespace App\Models\BI;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para vendedores.
 *
 * Conecta a farmacia.vendedor.
 * Relación: fact_cab.codi_vend / ticket_cab.codi_vend → vendedor.codigo
 */
class Vendedor extends Model
{
    protected $connection = 'farmacia';

    protected $table = 'vendedor';

    protected $primaryKey = 'codigo';

    public $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    // -----------------------------------------------------------------------
    // Accessors
    // -----------------------------------------------------------------------

    /** Nombre completo del vendedor (normalizado). */
    public function getNombreAttribute(): string
    {
        // Intentamos los campos más comunes; ajustar según esquema real
        return trim(
            $this->attributes['nombre']
            ?? $this->attributes['nomb_vend']
            ?? $this->attributes['descripcion']
            ?? $this->codigo
        );
    }
}
