<?php

declare(strict_types=1);

namespace App\Http\Controllers\Productos;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Ventas\Configuracion\Application\ObtenerConfiguracionGeneral;
use Ventas\Core\Infrastructure\Container\Container;
use Ventas\ListasPrecios\Application\ObtenerListaPrecioPredeterminada;
use Ventas\Productos\Application\ActualizarProducto;
use Ventas\Productos\Application\BuscarProductoFormularioPorId;
use Ventas\Productos\Application\BuscarProductoPorId;
use Ventas\Productos\Application\BuscarProductosParaVenta;
use Ventas\Productos\Application\CrearProducto;
use Ventas\Productos\Application\ListarProductosVista;
use Ventas\Stock\Application\ActualizarStock;
use Ventas\Stock\Application\BuscarStockPorId;
use Ventas\Stock\Application\CrearStockRetornandoId;
use Ventas\Stock\Domain\Entidades\Stock;

final class ProductosController extends Controller
{
    private const LIMITE_BUSQUEDA = 20;

    public function index(Request $request, Container $container): View
    {
        $q = trim((string) $request->query('q', ''));
        $productos = $q !== '' ? $this->buscarProductos($container, $q, self::LIMITE_BUSQUEDA) : $this->listarProductos($container);
        $paginador = $this->paginar($productos, $request, $q);
        $vista = view('productos.index', [
            'q' => $q,
            'productos' => $paginador,
            'configuracionMonedas' => $this->configuracionMonedas($container),
        ]);

        return $vista;
    }

    public function buscar(Request $request, Container $container): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $productos = $q !== '' ? $this->buscarProductos($container, $q, self::LIMITE_BUSQUEDA) : array_slice($this->listarProductos($container), 0, self::LIMITE_BUSQUEDA);
        $respuesta = response()->json([
            'data' => $this->productosJson($container, $productos),
            'limite' => self::LIMITE_BUSQUEDA,
        ]);

        return $respuesta;
    }

    public function autocompletar(Request $request, Container $container): JsonResponse
    {
        $productos = $this->buscarProductos(
            $container,
            trim((string) $request->query('q', '')),
            self::LIMITE_BUSQUEDA
        );
        $respuesta = response()->json($this->productosJson($container, $productos));

        return $respuesta;
    }

    public function mostrar(int $id, Container $container): JsonResponse
    {
        /** @var BuscarProductoFormularioPorId $buscarProductoFormularioPorId */
        $buscarProductoFormularioPorId = $container->get(BuscarProductoFormularioPorId::class);
        /** @var BuscarProductoPorId $buscarProductoPorId */
        $buscarProductoPorId = $container->get(BuscarProductoPorId::class);
        $producto = $buscarProductoPorId->ejecutar($id);
        $formulario = $buscarProductoFormularioPorId->ejecutar($id);
        $datos = $formulario;

        if ($producto !== null && $datos === null) {
            $datos = [
                'id' => $producto->id(),
                'codigo_barras' => $producto->codBarras(),
                'nombre' => $producto->nombre(),
                'precio' => $producto->precioFinal(),
                'stock' => null,
                'activo' => $producto->activo(),
            ];
        }
        $respuesta = response()->json(['producto' => $datos], $datos !== null ? 200 : 404);

        return $respuesta;
    }

    public function store(Request $request, Container $container): JsonResponse
    {
        /** @var CrearProducto $crearProducto */
        $crearProducto = $container->get(CrearProducto::class);
        $datos = $this->datosProducto($request, $container);
        $ok = $datos['valido'] && $crearProducto->ejecutar(
            (string) $datos['nombre'],
            (string) $datos['codigo_barras'],
            (int) $datos['id_stock'] > 0 ? (int) $datos['id_stock'] : null,
            (float) $datos['factor_conversion'],
            (float) $datos['ganancia'],
            (float) $datos['precio_final'],
            (int) $datos['activo']
        );
        $respuesta = response()->json([
            'ok' => $ok,
            'mensaje' => $ok ? 'Producto guardado.' : (string) $datos['mensaje'],
            'producto' => $datos,
        ], $ok ? 201 : 422);

        return $respuesta;
    }

    public function update(int $id, Request $request, Container $container): JsonResponse
    {
        /** @var ActualizarProducto $actualizarProducto */
        $actualizarProducto = $container->get(ActualizarProducto::class);
        $datos = $this->datosProducto($request, $container);
        $ok = $datos['valido'] && $actualizarProducto->ejecutar(
            $id,
            (string) $datos['nombre'],
            (string) $datos['codigo_barras'],
            (int) $datos['id_stock'] > 0 ? (int) $datos['id_stock'] : null,
            (float) $datos['factor_conversion'],
            (float) $datos['ganancia'],
            (float) $datos['precio_final'],
            (int) $datos['activo']
        );
        $respuesta = response()->json([
            'ok' => $ok,
            'mensaje' => $ok ? 'Producto actualizado.' : (string) $datos['mensaje'],
            'producto' => $datos,
        ], $ok ? 200 : 422);

        return $respuesta;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buscarProductos(Container $container, string $texto, int $limite): array
    {
        $productos = [];

        if ($texto !== '') {
            /** @var BuscarProductosParaVenta $buscarProductosParaVenta */
            $buscarProductosParaVenta = $container->get(BuscarProductosParaVenta::class);
            $productos = $buscarProductosParaVenta->ejecutar(
                $texto,
                $this->modoBusqueda($texto),
                $this->idListaPredeterminada($container),
                $limite
            );
        }

        return $productos;
    }

    private function idListaPredeterminada(Container $container): int
    {
        /** @var ObtenerListaPrecioPredeterminada $obtenerListaPrecioPredeterminada */
        $obtenerListaPrecioPredeterminada = $container->get(ObtenerListaPrecioPredeterminada::class);
        $id = $obtenerListaPrecioPredeterminada->ejecutar();

        return $id;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function listarProductos(Container $container): array
    {
        /** @var ListarProductosVista $listarProductosVista */
        $listarProductosVista = $container->get(ListarProductosVista::class);
        $productos = $listarProductosVista->ejecutar('nombre', 'ASC', $this->idListaPredeterminada($container));

        return $productos;
    }

    private function modoBusqueda(string $texto): string
    {
        $modo = preg_match('/^\d+$/', $texto) === 1 ? 'codigo' : 'texto';

        return $modo;
    }

    private function idStock(Request $request): ?int
    {
        $id = (int) $request->input('id_stock', 0);
        $idStock = $id > 0 ? $id : null;

        return $idStock;
    }

    /**
     * @return array<string, mixed>
     */
    private function datosProducto(Request $request, Container $container): array
    {
        $nombre = trim((string) $request->input('nombre', ''));
        $precioCosto = (float) $request->input('precio_costo', 0);
        $monedaCosto = strtoupper(trim((string) $request->input('moneda_costo', 'ARS')));
        $factorConversion = (float) $request->input('factor_conversion', 1);
        $ganancia = (float) $request->input('ganancia', 0);
        $dolarVenta = (float) $request->input('dolar_venta', $this->dolarVenta($container));
        $precioCostoArs = $this->precioCostoArs($precioCosto, $monedaCosto, $dolarVenta);
        $valido = $nombre !== ''
            && $precioCosto >= 0
            && in_array($monedaCosto, ['ARS', 'USD'], true)
            && $dolarVenta > 0
            && $ganancia >= 0
            && $factorConversion > 0;
        $idStock = $valido ? $this->guardarStockProducto($request, $container, $nombre, $precioCosto, $monedaCosto, $precioCostoArs) : 0;
        $valido = $valido && $idStock > 0;
        $datos = [
            'valido' => $valido,
            'mensaje' => $valido ? 'Producto validado.' : 'Revise nombre, costo, moneda, dolar, ganancia y factor.',
            'nombre' => $nombre,
            'codigo_barras' => trim((string) $request->input('codigo_barras', '')),
            'id_stock' => $idStock,
            'factor_conversion' => $factorConversion,
            'ganancia' => $ganancia,
            'precio_costo' => $precioCosto,
            'moneda_costo' => $monedaCosto,
            'dolar_venta' => $dolarVenta,
            'precio_costo_ars' => $precioCostoArs,
            'precio_final' => round(($precioCostoArs * $factorConversion) * (1 + ($ganancia / 100)), 2),
            'activo' => (int) $request->input('activo', 1),
        ];

        return $datos;
    }

    private function guardarStockProducto(
        Request $request,
        Container $container,
        string $nombre,
        float $precioCosto,
        string $monedaCosto,
        float $precioCostoArs
    ): int {
        $idStock = (int) $request->input('id_stock', 0);
        $stock = $idStock > 0 ? $this->stockPorId($container, $idStock) : null;

        if ($stock instanceof Stock) {
            /** @var ActualizarStock $actualizarStock */
            $actualizarStock = $container->get(ActualizarStock::class);
            $ok = $actualizarStock->ejecutar(
                $idStock,
                $nombre,
                $stock->unidad(),
                $stock->cantidad(),
                $precioCostoArs,
                (int) $request->input('activo', 1),
                $stock->stockMinimo(),
                $stock->stockMaximo(),
                $stock->tipoStock(),
                $monedaCosto,
                $precioCosto
            );
            $idStock = $ok ? $idStock : 0;
        } else {
            /** @var CrearStockRetornandoId $crearStockRetornandoId */
            $crearStockRetornandoId = $container->get(CrearStockRetornandoId::class);
            $idStock = $crearStockRetornandoId->ejecutar(
                $nombre,
                'unidad',
                0,
                $precioCostoArs,
                (int) $request->input('activo', 1),
                0,
                0,
                'propio',
                $monedaCosto,
                $precioCosto
            );
        }

        return $idStock;
    }

    private function precioCostoArs(float $precioCosto, string $monedaCosto, float $dolarVenta): float
    {
        $precioCostoArs = $monedaCosto === 'USD' ? $precioCosto * $dolarVenta : $precioCosto;

        return round($precioCostoArs, 2);
    }

    private function stockPorId(Container $container, int $idStock): ?Stock
    {
        /** @var BuscarStockPorId $buscarStockPorId */
        $buscarStockPorId = $container->get(BuscarStockPorId::class);
        $stock = $buscarStockPorId->ejecutar($idStock);
        $stockEncontrado = $stock instanceof Stock ? $stock : null;

        return $stockEncontrado;
    }

    private function dolarVenta(Container $container): float
    {
        $configuracion = $this->configuracionMonedas($container);
        $dolarVenta = (float) ($configuracion['dolar_venta'] ?? 1220);

        return $dolarVenta;
    }

    /**
     * @return array<string, mixed>
     */
    private function configuracionMonedas(Container $container): array
    {
        /** @var ObtenerConfiguracionGeneral $obtenerConfiguracionGeneral */
        $obtenerConfiguracionGeneral = $container->get(ObtenerConfiguracionGeneral::class);
        $configuracion = $obtenerConfiguracionGeneral->ejecutar();

        return [
            'moneda_principal' => (string) ($configuracion['moneda_principal'] ?? 'ARS'),
            'dolar_compra' => (float) ($configuracion['dolar_compra'] ?? 1220),
            'dolar_venta' => (float) ($configuracion['dolar_venta'] ?? ($configuracion['productos_cotizacion_dolar'] ?? 1220)),
            'dolar_fecha_actualizacion' => (string) ($configuracion['dolar_fecha_actualizacion'] ?? ''),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $productos
     * @return array<int, array<string, mixed>>
     */
    private function productosJson(Container $container, array $productos): array
    {
        $datos = [];

        foreach ($productos as $producto) {
            $formulario = $this->productoFormulario($container, (int) ($producto['id'] ?? 0));
            $precio = (float) ($producto['precio'] ?? 0);
            $precio = $precio > 0 ? $precio : (float) ($formulario['precio_final'] ?? ($producto['precio_final'] ?? 0));
            $datos[] = [
                'id' => (int) ($producto['id'] ?? 0),
                'codigo_barras' => (string) ($producto['cod_barras'] ?? ''),
                'nombre' => (string) ($producto['nombre'] ?? ''),
                'precio' => $precio,
                'precio_costo' => (float) ($formulario['stock_costo_origen'] ?? ($formulario['stock_precio_costo'] ?? 0)),
                'moneda_costo' => (string) ($formulario['stock_moneda_costo'] ?? 'ARS'),
                'precio_costo_ars' => (float) ($formulario['stock_precio_costo'] ?? 0),
                'precio_final' => $precio,
                'stock' => (float) ($formulario['stock_cantidad'] ?? ($producto['stock_cantidad'] ?? 0)),
                'activo' => (bool) ($formulario['activo'] ?? ($producto['activo'] ?? true)),
            ];
        }

        return $datos;
    }

    /**
     * @return array<string, mixed>
     */
    private function productoFormulario(Container $container, int $idProducto): array
    {
        /** @var BuscarProductoFormularioPorId $buscarProductoFormularioPorId */
        $buscarProductoFormularioPorId = $container->get(BuscarProductoFormularioPorId::class);
        $producto = $idProducto > 0 ? $buscarProductoFormularioPorId->ejecutar($idProducto) : null;
        $datos = is_array($producto) ? $producto : [];

        return $datos;
    }

    /**
     * @param array<int, array<string, mixed>> $productos
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    private function paginar(array $productos, Request $request, string $q): LengthAwarePaginator
    {
        $pagina = max(1, (int) $request->query('page', 1));
        $porPagina = 10;
        $items = array_slice($productos, ($pagina - 1) * $porPagina, $porPagina);
        $paginador = new LengthAwarePaginator(
            $items,
            count($productos),
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
