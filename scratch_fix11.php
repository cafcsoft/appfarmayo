<?php
$files = [
    'c:/xampp/htdocs/appfarmayo/app/Services/BI/VentasService.php',
    'c:/xampp/htdocs/appfarmayo/app/Services/BI/ClientesService.php',
    'c:/xampp/htdocs/appfarmayo/app/Services/BI/ProductosService.php',
    'c:/xampp/htdocs/appfarmayo/app/Services/BI/ForecastingService.php'
];

foreach ($files as $f) {
    $c = file_get_contents($f);
    
    // Inject use CacheHelper inside class if not there
    if (!str_contains($c, 'use CacheHelper;')) {
        $c = preg_replace('/class\s+([A-Za-z0-9_]+)\s*\{/', "class $1\n{\n    use CacheHelper;\n", $c);
    }
    
    // Replace Cache::store('file')->remember
    $c = str_replace("Cache::store('file')->remember", '$this->cacheJson', $c);
    // Replace Cache::remember
    $c = preg_replace('/(?<!\$)Cache::remember\(/', '$this->cacheJson(', $c);
    
    // Clean up our previous ->toJson() and ->toArray() so they don't break CacheHelper
    // We replace get()->toJson(); with get();
    $c = str_replace("get()->toJson();", "get();", $c);
    $c = str_replace("get()->toArray();", "get();", $c);
    
    // Also remove the collect(json_decode(...)) wrapper from ventasPorMes and resumenPorCliente
    // Wait, since we are doing this globally, the outer wrapper might break.
    // Let's just strip out any line that says "return collect(json_decode($json));"
    // and replace "$json = $this->cacheJson" with "return $this->cacheJson"
    $c = str_replace('$data = $this->cacheJson', 'return $this->cacheJson', $c);
    $c = str_replace('$json = $this->cacheJson', 'return $this->cacheJson', $c);
    $c = preg_replace('/return collect\(\$data\);\s*/s', '', $c);
    $c = preg_replace('/return collect\(json_decode\(\$json\)\);\s*/s', '', $c);
    $c = preg_replace('/\}\)->toArray\(\);\s*\n\s*\}\)/s', "});\n        })", $c);
    $c = preg_replace('/\}\)->toJson\(\);\s*\n\s*\}\)/s', "});\n        })", $c);
    
    file_put_contents($f, $c);
    echo "Processed " . basename($f) . "\n";
}
