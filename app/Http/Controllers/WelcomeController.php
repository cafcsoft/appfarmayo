<?php

namespace App\Http\Controllers;

use App\Models\Visita;
use Illuminate\Http\Request;

class WelcomeController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $totalVisitas = 0;

        try {
            $visita = Visita::firstOrCreate(
                ['pagina' => 'welcome'],
                ['contador' => 0]
            );

            $visita->increment('contador');
            $totalVisitas = $visita->contador;
        } catch (\Exception $e) {
            // Silently fail to keep the site running if DB is not ready
            logger()->error('Error en contador de visitas: ' . $e->getMessage());
        }

        return view('welcome', [
            'totalVisitas' => $totalVisitas
        ]);
    }
}
