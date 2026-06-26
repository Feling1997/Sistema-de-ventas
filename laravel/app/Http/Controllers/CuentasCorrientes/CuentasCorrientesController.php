<?php

declare(strict_types=1);

namespace App\Http\Controllers\CuentasCorrientes;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Ventas\Clientes\Application\ListarClientes;
use Ventas\Clientes\Domain\Entidades\Cliente;
use Ventas\CuentasCorrientes\Application\BuscarCuentaCorrientePorId;
use Ventas\CuentasCorrientes\Application\ListarCuotasPendientes;
use Ventas\CuentasCorrientes\Application\ListarCuotasPendientesDetalle;
use Ventas\CuentasCorrientes\Application\ObtenerResumenGeneralCuentaCorriente;
use Ventas\CuentasCorrientes\Application\RegistrarPagoCuentaCorriente;
use Ventas\Core\Infrastructure\Container\Container;

final class CuentasCorrientesController extends Controller
{
    private const LIMITE = 20;

    public function index(Request $request, Container $container): View
    {
        $buscar = trim((string) $request->query('q', ''));
        $cuotas = $this->cuotasDetalle($container, $buscar);
        $vista = view('cuentas-corrientes.index', [
            'q' => $buscar,
            'cuotas' => $this->paginar($cuotas, $request, $buscar),
            'resumen' => $this->resumenDatos($container),
        ]);

        return $vista;
    }

    public function buscarCliente(Request $request, Container $container): JsonResponse
    {
        $respuesta = response()->json($this->clientesJson($container, trim((string) $request->query('q', ''))));

        return $respuesta;
    }

    public function buscarCuenta(Request $request, Container $container): JsonResponse
    {
        $cuenta = $this->cuenta($container, (int) $request->query('id', 0));
        $respuesta = response()->json(['cuenta' => $cuenta], $cuenta !== null ? 200 : 404);

        return $respuesta;
    }

    public function saldo(Request $request, Container $container): JsonResponse
    {
        $cuenta = $this->cuenta($container, (int) $request->query('id', 0));
        $respuesta = response()->json([
            'id' => (int) ($cuenta['id'] ?? 0),
            'cliente' => (string) ($cuenta['cliente_nombre'] ?? ''),
            'saldo' => (float) ($cuenta['saldo'] ?? 0),
            'estado' => (string) ($cuenta['estado'] ?? ''),
        ], $cuenta !== null ? 200 : 404);

        return $respuesta;
    }

    public function cuotas(Request $request, Container $container): JsonResponse
    {
        /** @var ListarCuotasPendientes $listarCuotasPendientes */
        $listarCuotasPendientes = $container->get(ListarCuotasPendientes::class);
        $cuotas = array_slice($listarCuotasPendientes->ejecutar((int) $request->query('id', 0)), 0, self::LIMITE);
        $respuesta = response()->json([
            'data' => $cuotas,
            'limite' => self::LIMITE,
        ]);

        return $respuesta;
    }

    public function registrarPago(Request $request, Container $container): JsonResponse
    {
        /** @var RegistrarPagoCuentaCorriente $registrarPagoCuentaCorriente */
        $registrarPagoCuentaCorriente = $container->get(RegistrarPagoCuentaCorriente::class);
        $resultado = $registrarPagoCuentaCorriente->ejecutar(
            (int) $request->input('id_cuenta', 0),
            (array) $request->input('cuotas', []),
            (float) $request->input('importe', 0),
            trim((string) $request->input('observacion', '')),
            (int) $request->input('id_usuario', 0),
            (string) $request->input('forma_pago', 'contado')
        );
        $respuesta = response()->json($resultado, ($resultado['ok'] ?? false) === true ? 200 : 422);

        return $respuesta;
    }

    public function resumen(Container $container): JsonResponse
    {
        $respuesta = response()->json($this->resumenDatos($container));

        return $respuesta;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function cuotasDetalle(Container $container, string $buscar): array
    {
        /** @var ListarCuotasPendientesDetalle $listarCuotasPendientesDetalle */
        $listarCuotasPendientesDetalle = $container->get(ListarCuotasPendientesDetalle::class);
        $cuotas = array_slice($listarCuotasPendientesDetalle->ejecutar($buscar, 'todos', 'vencimiento', 'ASC'), 0, self::LIMITE);

        return $cuotas;
    }

    private function cuenta(Container $container, int $id): ?array
    {
        /** @var BuscarCuentaCorrientePorId $buscarCuentaCorrientePorId */
        $buscarCuentaCorrientePorId = $container->get(BuscarCuentaCorrientePorId::class);
        $cuenta = $buscarCuentaCorrientePorId->ejecutar($id);

        return $cuenta;
    }

    /**
     * @return array<string, mixed>
     */
    private function resumenDatos(Container $container): array
    {
        /** @var ObtenerResumenGeneralCuentaCorriente $obtenerResumenGeneralCuentaCorriente */
        $obtenerResumenGeneralCuentaCorriente = $container->get(ObtenerResumenGeneralCuentaCorriente::class);
        $resumen = $obtenerResumenGeneralCuentaCorriente->ejecutar();

        return $resumen;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function clientesJson(Container $container, string $texto): array
    {
        /** @var ListarClientes $listarClientes */
        $listarClientes = $container->get(ListarClientes::class);
        $textoNormalizado = mb_strtolower($texto);
        $clientes = [];

        if ($textoNormalizado !== '') {
            foreach ($listarClientes->ejecutar() as $cliente) {
                $coincide = $cliente instanceof Cliente
                    && (str_contains(mb_strtolower($cliente->nombre()), $textoNormalizado)
                        || str_contains(mb_strtolower((string) $cliente->documento()), $textoNormalizado)
                        || str_contains(mb_strtolower((string) $cliente->telefono()), $textoNormalizado));

                if ($coincide && count($clientes) < self::LIMITE) {
                    $clientes[] = [
                        'id' => $cliente->id(),
                        'nombre' => $cliente->nombre(),
                        'documento' => $cliente->documento(),
                        'telefono' => $cliente->telefono(),
                    ];
                }
            }
        }

        return $clientes;
    }

    /**
     * @param array<int, array<string, mixed>> $cuotas
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    private function paginar(array $cuotas, Request $request, string $buscar): LengthAwarePaginator
    {
        $pagina = max(1, (int) $request->query('page', 1));
        $porPagina = 10;
        $items = array_slice($cuotas, ($pagina - 1) * $porPagina, $porPagina);
        $paginador = new LengthAwarePaginator(
            $items,
            count($cuotas),
            $porPagina,
            $pagina,
            [
                'path' => $request->url(),
                'query' => ['q' => $buscar],
            ]
        );

        return $paginador;
    }
}
