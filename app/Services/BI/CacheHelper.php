<?php

namespace App\Services\BI;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;

trait CacheHelper
{
    protected function cacheJson(string $key, int $ttl, \Closure $callback)
    {
        $payload = Cache::store('file')->remember($key, $ttl, function() use ($callback) {
            $result = $callback();
            
            // Si ya habíamos forzado toJson() o toArray(), evitamos doble codificación
            if (is_string($result)) {
                $decoded = json_decode($result, false);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $result = collect($decoded);
                }
            }
            
            $type = $result instanceof Collection ? 'collection' : 'array';
            $data = $result instanceof Collection ? $result->toArray() : $result;
            
            return json_encode(['type' => $type, 'data' => $data]);
        });

        if (!is_string($payload)) {
            return $payload; // Fallback por si la cache ya tenía un array/objeto directo
        }

        // IMPORTANTE: json_decode con false (segundo arg) para obtener stdClass,
        // lo que preserva el acceso ->propiedad en colecciones de filas de BD.
        $decoded = json_decode($payload, false);
        
        if (isset($decoded->type) && $decoded->type === 'collection') {
            // Rehydrate como Collection de stdClass
            return collect($decoded->data);
        }

        // Para retornos tipo array (kpis, etc.) convertir el objeto a array asociativo
        // preservando las claves de la data
        if (isset($decoded->data)) {
            return (array) $decoded->data;
        }

        return (array) $decoded;
    }
}
