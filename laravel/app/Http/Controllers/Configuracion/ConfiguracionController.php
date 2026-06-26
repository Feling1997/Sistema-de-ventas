<?php

declare(strict_types=1);

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Ventas\Configuracion\Application\GuardarConfiguracion;
use Ventas\Configuracion\Application\ObtenerConfiguracionGeneral;
use Ventas\Core\Infrastructure\Container\Container;

final class ConfiguracionController extends Controller
{
    public function index(Container $container): View
    {
        /** @var ObtenerConfiguracionGeneral $obtenerConfiguracionGeneral */
        $obtenerConfiguracionGeneral = $container->get(ObtenerConfiguracionGeneral::class);
        $vista = view('configuracion.index', [
            'configuracion' => $obtenerConfiguracionGeneral->ejecutar(),
        ]);

        return $vista;
    }

    public function guardar(Request $request, Container $container): JsonResponse
    {
        /** @var GuardarConfiguracion $guardarConfiguracion */
        $guardarConfiguracion = $container->get(GuardarConfiguracion::class);
        $datos = [
            'moneda_principal' => (string) $request->input('moneda_principal', 'ARS'),
            'dolar_compra' => (string) $request->input('dolar_compra', '0'),
            'dolar_venta' => (string) $request->input('dolar_venta', '0'),
            'dolar_fecha_actualizacion' => (string) $request->input('dolar_fecha_actualizacion', date('Y-m-d')),
            'productos_cotizacion_dolar' => (string) $request->input('dolar_venta', '0'),
        ];
        $ok = $this->datosValidos($datos) && $guardarConfiguracion->ejecutar($datos);
        $respuesta = response()->json([
            'ok' => $ok,
            'mensaje' => $ok ? 'Configuracion de monedas guardada.' : 'No se pudo guardar la configuracion de monedas.',
            'configuracion' => $datos,
        ], $ok ? 200 : 422);

        return $respuesta;
    }

    /**
     * @param array<string, string> $datos
     */
    private function datosValidos(array $datos): bool
    {
        $valido = in_array($datos['moneda_principal'], ['ARS', 'USD'], true)
            && (float) str_replace(',', '.', $datos['dolar_compra']) > 0
            && (float) str_replace(',', '.', $datos['dolar_venta']) > 0;

        return $valido;
    }
}
