<?php

declare(strict_types=1);

namespace App\Http\Controllers\Presupuestos;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Ventas\Clientes\Application\ListarClientes;
use Ventas\Clientes\Domain\Entidades\Cliente;
use Ventas\Core\Infrastructure\Container\Container;
use Ventas\ListasPrecios\Application\ObtenerListaPrecioPredeterminada;
use Ventas\Presupuestos\Application\BuscarPresupuestoPorId;
use Ventas\Presupuestos\Application\GenerarPdfPresupuesto;
use Ventas\Presupuestos\Application\ObtenerArchivoPdfPresupuesto;
use Ventas\Presupuestos\Application\ObtenerDetallePresupuesto;
use Ventas\Presupuestos\Application\RenderizarTicketPresupuesto;
use Ventas\Presupuestos\Domain\Entidades\DetallePresupuesto;
use Ventas\Presupuestos\Domain\Entidades\Presupuesto;
use Ventas\Productos\Application\BuscarProductosParaVenta;

final class PresupuestosController extends Controller
{
    private const LIMITE = 20;

    public function index(Request $request, Container $container): View
    {
        $q = trim((string) $request->query('q', ''));
        $presupuestos = $this->presupuestosParaIndice($container, $q);
        $vista = view('presupuestos.index', [
            'q' => $q,
            'presupuestos' => $this->paginar($presupuestos, $request, $q),
        ]);

        return $vista;
    }

    public function buscar(Request $request, Container $container): JsonResponse
    {
        $presupuestos = $this->presupuestosParaIndice($container, trim((string) $request->query('q', '')));
        $respuesta = response()->json($presupuestos);

        return $respuesta;
    }

    public function clientes(Request $request, Container $container): JsonResponse
    {
        $respuesta = response()->json($this->clientesJson($container, trim((string) $request->query('q', ''))));

        return $respuesta;
    }

    public function productos(Request $request, Container $container): JsonResponse
    {
        $respuesta = response()->json($this->productosJson($container, trim((string) $request->query('q', ''))));

        return $respuesta;
    }

    public function show(int $id, Container $container): View
    {
        $presupuesto = $this->presupuestoJson($container, $id);
        $vista = view('presupuestos.show', [
            'presupuesto' => $presupuesto,
            'existe' => $presupuesto !== null,
        ]);

        return $vista;
    }

    public function store(): JsonResponse
    {
        $respuesta = response()->json([
            'ok' => false,
            'error' => 'Crear presupuesto requiere caso de uso modular en src/presupuestos.',
        ], 422);

        return $respuesta;
    }

    public function update(): JsonResponse
    {
        $respuesta = response()->json([
            'ok' => false,
            'error' => 'Actualizar presupuesto requiere caso de uso modular en src/presupuestos.',
        ], 422);

        return $respuesta;
    }

    public function destroy(): JsonResponse
    {
        $respuesta = response()->json([
            'ok' => false,
            'error' => 'Eliminar presupuesto requiere caso de uso modular en src/presupuestos.',
        ], 422);

        return $respuesta;
    }

    public function pdf(int $id, Container $container): Response
    {
        /** @var GenerarPdfPresupuesto $generarPdfPresupuesto */
        $generarPdfPresupuesto = $container->get(GenerarPdfPresupuesto::class);
        /** @var ObtenerArchivoPdfPresupuesto $obtenerArchivoPdfPresupuesto */
        $obtenerArchivoPdfPresupuesto = $container->get(ObtenerArchivoPdfPresupuesto::class);
        $generado = $generarPdfPresupuesto->ejecutar($id);
        $archivo = ($generado['ok'] ?? false) === true ? $obtenerArchivoPdfPresupuesto->ejecutar($id) : ['ok' => false];
        $respuesta = ($archivo['ok'] ?? false) === true
            ? response((string) $archivo['contenido'], 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . (string) ($archivo['nombre'] ?? 'presupuesto.pdf') . '"',
            ])
            : response()->json([
                'ok' => false,
                'error' => (string) ($generado['error'] ?? 'No se pudo obtener el PDF.'),
            ], 404);

        return $respuesta;
    }

    public function ticket(int $id, Request $request, Container $container): Response
    {
        /** @var RenderizarTicketPresupuesto $renderizarTicketPresupuesto */
        $renderizarTicketPresupuesto = $container->get(RenderizarTicketPresupuesto::class);
        $resultado = $renderizarTicketPresupuesto->ejecutar($id, (bool) $request->boolean('auto_print', false));
        $respuesta = ($resultado['ok'] ?? false) === true
            ? response((string) ($resultado['html'] ?? ''), 200, ['Content-Type' => 'text/html; charset=UTF-8'])
            : response()->json($resultado, 404);

        return $respuesta;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function presupuestosParaIndice(Container $container, string $q): array
    {
        $presupuestos = [];
        $id = (int) $q;

        if ($id > 0) {
            $presupuesto = $this->presupuestoJson($container, $id);

            if ($presupuesto !== null) {
                $presupuestos[] = $presupuesto;
            }
        }

        return $presupuestos;
    }

    private function presupuestoJson(Container $container, int $id): ?array
    {
        /** @var BuscarPresupuestoPorId $buscarPresupuestoPorId */
        $buscarPresupuestoPorId = $container->get(BuscarPresupuestoPorId::class);
        /** @var ObtenerDetallePresupuesto $obtenerDetallePresupuesto */
        $obtenerDetallePresupuesto = $container->get(ObtenerDetallePresupuesto::class);
        $presupuesto = $buscarPresupuestoPorId->ejecutar($id);
        $datos = null;

        if ($presupuesto instanceof Presupuesto) {
            $detalles = $this->detallesJson($obtenerDetallePresupuesto->ejecutar($id));
            $datos = [
                'id' => $presupuesto->id(),
                'fecha' => $presupuesto->fecha(),
                'cliente' => $presupuesto->clienteNombre(),
                'usuario' => $presupuesto->usuarioNombre(),
                'subtotal' => $this->subtotal($detalles),
                'descuento' => $this->descuento($detalles),
                'total' => $presupuesto->total(),
                'detalles' => $detalles,
            ];
        }

        return $datos;
    }

    /**
     * @param array<int, DetallePresupuesto> $detalles
     * @return array<int, array<string, mixed>>
     */
    private function detallesJson(array $detalles): array
    {
        $datos = [];

        foreach ($detalles as $detalle) {
            if ($detalle instanceof DetallePresupuesto) {
                $bruto = $detalle->cantidad() * $detalle->precioUnit();
                $datos[] = [
                    'id' => $detalle->id(),
                    'id_producto' => $detalle->idProducto(),
                    'producto' => $detalle->productoNombre(),
                    'cantidad' => $detalle->cantidad(),
                    'precio_unit' => $detalle->precioUnit(),
                    'descuento' => $detalle->descuento(),
                    'subtotal_bruto' => $bruto,
                    'subtotal' => $detalle->subtotal(),
                ];
            }
        }

        return $datos;
    }

    /**
     * @param array<int, array<string, mixed>> $detalles
     */
    private function subtotal(array $detalles): float
    {
        $subtotal = 0.0;

        foreach ($detalles as $detalle) {
            $subtotal += (float) ($detalle['subtotal_bruto'] ?? 0);
        }

        return $subtotal;
    }

    /**
     * @param array<int, array<string, mixed>> $detalles
     */
    private function descuento(array $detalles): float
    {
        $descuento = 0.0;

        foreach ($detalles as $detalle) {
            $descuento += max(0.0, (float) ($detalle['subtotal_bruto'] ?? 0) - (float) ($detalle['subtotal'] ?? 0));
        }

        return $descuento;
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
     * @return array<int, array<string, mixed>>
     */
    private function productosJson(Container $container, string $texto): array
    {
        $productos = [];

        if ($texto !== '') {
            /** @var BuscarProductosParaVenta $buscarProductosParaVenta */
            $buscarProductosParaVenta = $container->get(BuscarProductosParaVenta::class);
            /** @var ObtenerListaPrecioPredeterminada $obtenerListaPrecioPredeterminada */
            $obtenerListaPrecioPredeterminada = $container->get(ObtenerListaPrecioPredeterminada::class);
            $productos = $buscarProductosParaVenta->ejecutar(
                $texto,
                preg_match('/^\d+$/', $texto) === 1 ? 'codigo' : 'texto',
                $obtenerListaPrecioPredeterminada->ejecutar(),
                self::LIMITE
            );
        }

        return $productos;
    }

    /**
     * @param array<int, array<string, mixed>> $presupuestos
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    private function paginar(array $presupuestos, Request $request, string $q): LengthAwarePaginator
    {
        $pagina = max(1, (int) $request->query('page', 1));
        $porPagina = 10;
        $items = array_slice($presupuestos, ($pagina - 1) * $porPagina, $porPagina);
        $paginador = new LengthAwarePaginator(
            $items,
            count($presupuestos),
            $porPagina,
            $pagina,
            [
                'path' => $request->url(),
                'query' => ['q' => $q],
            ]
        );

        return $paginador;
    }
}
