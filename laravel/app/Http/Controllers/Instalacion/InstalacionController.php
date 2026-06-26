<?php

declare(strict_types=1);

namespace App\Http\Controllers\Instalacion;

use App\Http\Controllers\Controller;
use Ventas\Instalacion\Application\GuardarModoReparaciones;
use Ventas\Instalacion\Application\VerificarInstalacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class InstalacionController extends Controller
{
    public function index(VerificarInstalacion $verificar): View
    {
        $estado = $verificar->ejecutar();

        return view('instalacion.index', ['estado' => $estado]);
    }

    public function preparar(VerificarInstalacion $verificar): JsonResponse
    {
        $resultado = $verificar->ejecutar(true);

        return response()->json($resultado);
    }

    public function modo(Request $request, GuardarModoReparaciones $guardarModo): JsonResponse
    {
        $resultado = $guardarModo->ejecutar((string) $request->input('modo', 'laravel'));

        return response()->json($resultado);
    }
}
