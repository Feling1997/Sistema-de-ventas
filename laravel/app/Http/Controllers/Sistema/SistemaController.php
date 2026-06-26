<?php

declare(strict_types=1);

namespace App\Http\Controllers\Sistema;

use Ventas\Sistema\Application\GenerarDiagnosticoSistema;
use Illuminate\Contracts\View\View;

final class SistemaController
{
    public function diagnostico(GenerarDiagnosticoSistema $diagnostico): View
    {
        $datos = $diagnostico->ejecutar();

        return view('sistema.diagnostico', ['diagnostico' => $datos]);
    }
}
