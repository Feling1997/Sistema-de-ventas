<?php

declare(strict_types=1);

namespace App\Http\Controllers\Stock;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Ventas\Stock\Application\ActualizarStock;
use Ventas\Core\Infrastructure\Container\Container;
use Ventas\Stock\Application\AlertasStockBajo;
use Ventas\Stock\Application\BuscarStockPorId;
use Ventas\Stock\Application\CrearStock;
use Ventas\Stock\Application\EliminarStock;
use Ventas\Stock\Application\ListarFaltantes;
use Ventas\Stock\Application\ListarStock;
use Ventas\Stock\Application\ResumenAlertasStockBajo;
use Ventas\Stock\Application\SumarCantidadStock;
use Ventas\Stock\Domain\Entidades\Stock;

final class StockController extends Controller
{
    private const LIMITE_BUSQUEDA = 20;

    public function index(Request $request, Container $container): View
    {
        $q = trim((string) $request->query('q', ''));
        $stocks = $q !== '' ? $this->buscarStock($container, $q, self::LIMITE_BUSQUEDA) : array_slice($this->listarStock($container), 0, self::LIMITE_BUSQUEDA);
        $stocks = $this->filtrarActivo($stocks, (string) $request->query('activo', ''));
        $paginador = $this->paginar($stocks, $request, $q);
        $vista = view('stock.index', [
            'q' => $q,
            'stocks' => $paginador,
            'resumenAlertas' => $this->resumenAlertas($container),
            'activo' => (string) $request->query('activo', ''),
        ]);

        return $vista;
    }

    public function buscar(Request $request, Container $container): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $stocks = $q !== '' ? $this->buscarStock($container, $q, self::LIMITE_BUSQUEDA) : array_slice($this->listarStock($container), 0, self::LIMITE_BUSQUEDA);
        $stocks = $this->filtrarActivo($stocks, (string) $request->query('activo', ''));
        $respuesta = response()->json([
            'data' => $this->stocksJson($stocks),
            'limite' => self::LIMITE_BUSQUEDA,
        ]);

        return $respuesta;
    }

    public function store(Request $request, Container $container): JsonResponse
    {
        /** @var CrearStock $crearStock */
        $crearStock = $container->get(CrearStock::class);
        $ok = $crearStock->ejecutar(
            trim((string) $request->input('nombre', '')),
            trim((string) $request->input('unidad', 'unidad')),
            (float) $request->input('cantidad', 0),
            (float) $request->input('precio_costo', 0),
            (int) $request->input('activo', 1),
            (float) $request->input('stock_minimo', 0),
            (float) $request->input('stock_maximo', 0),
            trim((string) $request->input('tipo_stock', 'general')),
            trim((string) $request->input('moneda_costo', 'ARS')),
            (float) $request->input('costo_origen', 0)
        );
        $respuesta = response()->json([
            'ok' => $ok,
            'mensaje' => $ok ? 'Stock guardado.' : 'No se pudo guardar el stock.',
        ], $ok ? 201 : 422);

        return $respuesta;
    }

    public function update(int $id, Request $request, Container $container): JsonResponse
    {
        /** @var ActualizarStock $actualizarStock */
        $actualizarStock = $container->get(ActualizarStock::class);
        $ok = $actualizarStock->ejecutar(
            $id,
            trim((string) $request->input('nombre', '')),
            trim((string) $request->input('unidad', 'unidad')),
            (float) $request->input('cantidad', 0),
            (float) $request->input('precio_costo', 0),
            (int) $request->input('activo', 1),
            (float) $request->input('stock_minimo', 0),
            (float) $request->input('stock_maximo', 0),
            trim((string) $request->input('tipo_stock', 'general')),
            trim((string) $request->input('moneda_costo', 'ARS')),
            (float) $request->input('costo_origen', 0)
        );
        $respuesta = response()->json([
            'ok' => $ok,
            'mensaje' => $ok ? 'Stock actualizado.' : 'No se pudo actualizar el stock.',
        ], $ok ? 200 : 422);

        return $respuesta;
    }

    public function sumar(int $id, Request $request, Container $container): JsonResponse
    {
        /** @var SumarCantidadStock $sumarCantidadStock */
        $sumarCantidadStock = $container->get(SumarCantidadStock::class);
        $ok = $sumarCantidadStock->ejecutar($id, (float) $request->input('cantidad', 0));
        $respuesta = response()->json([
            'ok' => $ok,
            'mensaje' => $ok ? 'Movimiento aplicado.' : 'No se pudo aplicar el movimiento.',
        ], $ok ? 200 : 422);

        return $respuesta;
    }

    public function destroy(int $id, Container $container): JsonResponse
    {
        /** @var EliminarStock $eliminarStock */
        $eliminarStock = $container->get(EliminarStock::class);
        $ok = $eliminarStock->ejecutar($id);
        $respuesta = response()->json([
            'ok' => $ok,
            'mensaje' => $ok ? 'Stock eliminado.' : 'No se pudo eliminar el stock.',
        ], $ok ? 200 : 422);

        return $respuesta;
    }

    public function autocompletar(Request $request, Container $container): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $stocks = $q !== '' ? $this->buscarStock($container, $q, self::LIMITE_BUSQUEDA) : array_slice($this->listarStock($container), 0, self::LIMITE_BUSQUEDA);
        $respuesta = response()->json($this->stocksJson($stocks));

        return $respuesta;
    }

    public function faltantes(Container $container): JsonResponse
    {
        /** @var ListarFaltantes $listarFaltantes */
        $listarFaltantes = $container->get(ListarFaltantes::class);
        $faltantes = $listarFaltantes->ejecutar(true);
        $respuesta = response()->json([
            'data' => array_slice($this->filasStockJson($faltantes), 0, self::LIMITE_BUSQUEDA),
            'limite' => self::LIMITE_BUSQUEDA,
        ]);

        return $respuesta;
    }

    public function alertas(Container $container): JsonResponse
    {
        /** @var AlertasStockBajo $alertasStockBajo */
        $alertasStockBajo = $container->get(AlertasStockBajo::class);
        $alertas = $alertasStockBajo->ejecutar(0, true, 'bajo');
        $respuesta = response()->json([
            'resumen' => $this->resumenAlertas($container),
            'data' => array_slice($this->filasAlertaJson($alertas), 0, self::LIMITE_BUSQUEDA),
            'limite' => self::LIMITE_BUSQUEDA,
        ]);

        return $respuesta;
    }

    public function mostrar(int $id, Container $container): JsonResponse
    {
        /** @var BuscarStockPorId $buscarStockPorId */
        $buscarStockPorId = $container->get(BuscarStockPorId::class);
        $stock = $buscarStockPorId->ejecutar($id);
        $datos = $stock instanceof Stock ? $this->stockJson($stock) : null;
        $respuesta = response()->json(['stock' => $datos], $datos !== null ? 200 : 404);

        return $respuesta;
    }

    /**
     * @return array<int, Stock>
     */
    private function buscarStock(Container $container, string $texto, int $limite): array
    {
        $stocks = [];

        if ($texto !== '') {
            $stocks = $this->buscarPorTexto($container, mb_strtolower($texto), $limite);
        }

        return $stocks;
    }

    /**
     * @return array<int, Stock>
     */
    private function listarStock(Container $container): array
    {
        /** @var ListarStock $listarStock */
        $listarStock = $container->get(ListarStock::class);
        $stocks = $listarStock->ejecutar();

        return $stocks;
    }

    /**
     * @return array<int, Stock>
     */
    private function buscarPorTexto(Container $container, string $texto, int $limite): array
    {
        /** @var ListarStock $listarStock */
        $listarStock = $container->get(ListarStock::class);
        $stocks = [];

        foreach ($listarStock->ejecutar() as $stock) {
            $coincide = $stock instanceof Stock
                && (str_contains(mb_strtolower($stock->nombre()), $texto) || (string) $stock->id() === $texto);

            if ($coincide && count($stocks) < $limite) {
                $stocks[] = $stock;
            }
        }

        return $stocks;
    }

    /**
     * @param array<int, Stock> $stocks
     * @return array<int, Stock>
     */
    private function filtrarActivo(array $stocks, string $activo): array
    {
        $filtrados = $stocks;

        if ($activo !== '') {
            $esperado = $activo === '1';
            $filtrados = array_values(array_filter(
                $stocks,
                static fn (Stock $stock): bool => $stock->activo() === $esperado
            ));
        }

        return $filtrados;
    }

    /**
     * @param array<int, Stock> $stocks
     * @return array<int, array<string, mixed>>
     */
    private function stocksJson(array $stocks): array
    {
        $datos = [];

        foreach ($stocks as $stock) {
            if ($stock instanceof Stock) {
                $datos[] = $this->stockJson($stock);
            }
        }

        return $datos;
    }

    /**
     * @return array<string, mixed>
     */
    private function stockJson(Stock $stock): array
    {
        $datos = [
            'id' => $stock->id(),
            'nombre' => $stock->nombre(),
            'cantidad' => $stock->cantidad(),
            'minimo' => $stock->stockMinimo(),
            'maximo' => $stock->stockMaximo(),
            'precio_costo' => $stock->precioCosto(),
            'moneda_costo' => $stock->monedaCosto(),
            'costo_origen' => $stock->costoOrigen(),
            'activo' => $stock->activo(),
            'unidad' => $stock->unidad(),
            'tipo_stock' => $stock->tipoStock(),
        ];

        return $datos;
    }

    /**
     * @param array<int, array<string, mixed>> $filas
     * @return array<int, array<string, mixed>>
     */
    private function filasStockJson(array $filas): array
    {
        $datos = [];

        foreach ($filas as $fila) {
            $datos[] = [
                'id' => (int) ($fila['id'] ?? 0),
                'nombre' => (string) ($fila['nombre'] ?? ''),
                'cantidad' => (float) ($fila['cantidad'] ?? 0),
                'minimo' => (float) ($fila['stock_minimo'] ?? 0),
                'activo' => true,
                'unidad' => (string) ($fila['unidad'] ?? ''),
                'tipo_stock' => (string) ($fila['tipo_stock'] ?? ''),
                'cantidad_sugerida' => (float) ($fila['cantidad_sugerida'] ?? 0),
            ];
        }

        return $datos;
    }

    /**
     * @param array<int, array<string, mixed>> $filas
     * @return array<int, array<string, mixed>>
     */
    private function filasAlertaJson(array $filas): array
    {
        $datos = [];

        foreach ($filas as $fila) {
            $datos[] = [
                'id_producto' => (int) ($fila['id_producto'] ?? 0),
                'producto' => (string) ($fila['producto'] ?? ''),
                'id_stock' => (int) ($fila['id_stock'] ?? 0),
                'nombre' => (string) ($fila['stock_nombre'] ?? ''),
                'cantidad' => (float) ($fila['cantidad'] ?? 0),
                'minimo' => (float) ($fila['stock_minimo'] ?? 0),
                'pendiente' => (bool) ((int) ($fila['pendiente'] ?? 0) === 1),
                'unidad' => (string) ($fila['unidad'] ?? ''),
            ];
        }

        return $datos;
    }

    /**
     * @return array<string, int>
     */
    private function resumenAlertas(Container $container): array
    {
        /** @var ResumenAlertasStockBajo $resumenAlertasStockBajo */
        $resumenAlertasStockBajo = $container->get(ResumenAlertasStockBajo::class);
        $resumen = $resumenAlertasStockBajo->ejecutar(0);

        return [
            'total' => (int) ($resumen['total'] ?? 0),
            'pendientes' => (int) ($resumen['pendientes'] ?? 0),
            'leidas' => (int) ($resumen['leidas'] ?? 0),
        ];
    }

    /**
     * @param array<int, Stock> $stocks
     * @return LengthAwarePaginator<int, Stock>
     */
    private function paginar(array $stocks, Request $request, string $q): LengthAwarePaginator
    {
        $pagina = max(1, (int) $request->query('page', 1));
        $porPagina = 10;
        $items = array_slice($stocks, ($pagina - 1) * $porPagina, $porPagina);
        $paginador = new LengthAwarePaginator(
            $items,
            count($stocks),
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
