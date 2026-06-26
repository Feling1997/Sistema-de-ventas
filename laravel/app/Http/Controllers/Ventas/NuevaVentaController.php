<?php

declare(strict_types=1);

namespace App\Http\Controllers\Ventas;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Ventas\Configuracion\Application\ObtenerConfiguracionVenta;
use Ventas\Core\Infrastructure\Container\Container;
use Ventas\ListasPrecios\Application\ObtenerListaPrecioPredeterminada;
use Ventas\Productos\Application\BuscarProductosParaVenta;
use Ventas\Productos\Application\ObtenerProductoParaVenta;
use Ventas\Stock\Application\ObtenerStockPorProducto;
use Ventas\Ventas\Application\ConfirmarVenta;
use Ventas\Ventas\Application\NuevaVenta\ActualizarItemCarritoVenta;
use Ventas\Ventas\Application\NuevaVenta\AgregarItemCarritoVenta;
use Ventas\Ventas\Application\NuevaVenta\CalcularTotalCarritoVenta;
use Ventas\Ventas\Application\NuevaVenta\ListarClientesVenta;
use Ventas\Ventas\Application\NuevaVenta\ObtenerCarritoVenta;
use Ventas\Ventas\Application\NuevaVenta\ObtenerInicioVentas;
use Ventas\Ventas\Application\NuevaVenta\QuitarItemCarritoVenta;
use Ventas\Ventas\Application\NuevaVenta\VaciarCarritoVenta;
use Ventas\Stock\Domain\Entidades\Stock;

final class NuevaVentaController extends Controller
{
    private const LIMITE_BUSQUEDA = 20;

    public function index(Container $container): View
    {
        /** @var ObtenerInicioVentas $obtenerInicioVentas */
        $obtenerInicioVentas = $container->get(ObtenerInicioVentas::class);
        /** @var ObtenerConfiguracionVenta $obtenerConfiguracionVenta */
        $obtenerConfiguracionVenta = $container->get(ObtenerConfiguracionVenta::class);
        $vista = view('ventas.nueva', [
            'inicio' => $obtenerInicioVentas->ejecutar(),
            'configuracionVenta' => $obtenerConfiguracionVenta->ejecutar(),
            'carrito' => $this->carritoPayload($container),
        ]);

        return $vista;
    }

    public function buscarProductos(Request $request, Container $container): JsonResponse
    {
        $productos = $this->productosParaVenta($container, trim((string) $request->query('q', '')));
        $respuesta = response()->json($productos);

        return $respuesta;
    }

    public function buscarClientes(Request $request, Container $container): JsonResponse
    {
        $clientes = $this->clientesParaVenta($container, trim((string) $request->query('q', '')));
        $respuesta = response()->json($clientes);

        return $respuesta;
    }

    public function carrito(Container $container): JsonResponse
    {
        $respuesta = response()->json($this->carritoPayload($container));

        return $respuesta;
    }

    public function agregarItem(Request $request, Container $container): JsonResponse
    {
        /** @var AgregarItemCarritoVenta $agregarItemCarritoVenta */
        $agregarItemCarritoVenta = $container->get(AgregarItemCarritoVenta::class);
        $resultado = $agregarItemCarritoVenta->ejecutar(
            (int) $request->input('id_producto', 0),
            (float) $request->input('cantidad', 1),
            (float) $request->input('descuento', 0),
            trim((string) $request->input('precio_manual_raw', '')),
            (float) $request->input('precio_manual', 0),
            (bool) $request->boolean('aplicar_lista_existente', false),
            trim((string) $request->input('buscar_producto', '')),
            $this->idListaPrecio($container, (int) $request->input('id_lista_precio', 0))
        );
        $respuesta = response()->json($this->resultadoCarrito($container, $resultado), ($resultado['ok'] ?? false) === true ? 200 : 422);

        return $respuesta;
    }

    public function actualizarItem(Request $request, Container $container): JsonResponse
    {
        /** @var ActualizarItemCarritoVenta $actualizarItemCarritoVenta */
        $actualizarItemCarritoVenta = $container->get(ActualizarItemCarritoVenta::class);
        $resultado = $actualizarItemCarritoVenta->ejecutar(
            (int) $request->input('idx', 0),
            (float) $request->input('cantidad', 1),
            (float) $request->input('precio_unit', 0),
            (float) $request->input('descuento', 0)
        );
        $respuesta = response()->json($this->resultadoCarrito($container, $resultado), ($resultado['ok'] ?? false) === true ? 200 : 422);

        return $respuesta;
    }

    public function quitarItem(int $id, Request $request, Container $container): JsonResponse
    {
        /** @var QuitarItemCarritoVenta $quitarItemCarritoVenta */
        $quitarItemCarritoVenta = $container->get(QuitarItemCarritoVenta::class);
        $carrito = $quitarItemCarritoVenta->ejecutar($id, (int) $request->input('id_producto', 0));
        $respuesta = response()->json($this->carritoPayloadDesdeCarrito($container, $carrito));

        return $respuesta;
    }

    public function vaciar(Container $container): JsonResponse
    {
        /** @var VaciarCarritoVenta $vaciarCarritoVenta */
        $vaciarCarritoVenta = $container->get(VaciarCarritoVenta::class);
        $carrito = $vaciarCarritoVenta->ejecutar();
        $respuesta = response()->json($this->carritoPayloadDesdeCarrito($container, $carrito));

        return $respuesta;
    }

    public function confirmar(Request $request, Container $container): JsonResponse
    {
        $formaPago = $this->formaPago((string) $request->input('forma_pago', 'CONTADO'));
        $anticipo = max(0.0, (float) $request->input('anticipo', 0));
        /** @var ConfirmarVenta $confirmarVenta */
        $confirmarVenta = $container->get(ConfirmarVenta::class);
        $resultado = $confirmarVenta->ejecutar([
            'id_cliente' => (int) $request->input('id_cliente', 1),
            'id_usuario' => (int) $request->input('id_usuario', 0),
            'tipo_comprobante' => (int) $request->input('tipo_comprobante', 98),
            'forma_pago' => $formaPago,
            'imprimir_ticket' => false,
            'cc_cuotas' => max(1, (int) $request->input('cc_cuotas', 1)),
            'cc_vencimientos' => (array) $request->input('cc_vencimientos', []),
        ]);
        $resultado['anticipo'] = $anticipo;
        $resultado['forma_pago'] = $formaPago;
        $respuesta = response()->json($resultado, ($resultado['ok'] ?? false) === true ? 200 : 422);

        return $respuesta;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function productosParaVenta(Container $container, string $texto): array
    {
        $datos = [];

        if ($texto !== '') {
            /** @var BuscarProductosParaVenta $buscarProductosParaVenta */
            $buscarProductosParaVenta = $container->get(BuscarProductosParaVenta::class);
            $productos = $buscarProductosParaVenta->ejecutar(
                $texto,
                $this->modoBusqueda($texto),
                $this->idListaPrecio($container, 0),
                self::LIMITE_BUSQUEDA
            );
            $datos = $this->productosJson($container, $productos);
        }

        return $datos;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function clientesParaVenta(Container $container, string $texto): array
    {
        /** @var ListarClientesVenta $listarClientesVenta */
        $listarClientesVenta = $container->get(ListarClientesVenta::class);
        $textoNormalizado = mb_strtolower($texto);
        $clientes = [];

        if ($textoNormalizado !== '') {
            foreach ($listarClientesVenta->ejecutar() as $cliente) {
                $nombre = mb_strtolower((string) ($cliente['nombre'] ?? ''));
                $documento = mb_strtolower((string) ($cliente['dni'] ?? ''));
                $coincide = str_contains($nombre, $textoNormalizado) || str_contains($documento, $textoNormalizado);

                if ($coincide && count($clientes) < self::LIMITE_BUSQUEDA) {
                    $clientes[] = [
                        'id' => (int) ($cliente['id'] ?? 0),
                        'nombre' => (string) ($cliente['nombre'] ?? ''),
                        'documento' => (string) ($cliente['dni'] ?? ''),
                        'telefono' => (string) ($cliente['telefono'] ?? ''),
                    ];
                }
            }
        }

        return $clientes;
    }

    private function modoBusqueda(string $texto): string
    {
        $modo = preg_match('/^\d+$/', $texto) === 1 ? 'codigo' : 'texto';

        return $modo;
    }

    private function formaPago(string $formaPago): string
    {
        $normalizada = strtolower(trim($formaPago));
        $valor = $normalizada === 'cuenta_corriente' ? 'cuenta_corriente' : 'contado';

        return $valor;
    }

    private function idListaPrecio(Container $container, int $id): int
    {
        $idLista = $id;

        if ($idLista <= 0) {
            /** @var ObtenerListaPrecioPredeterminada $obtenerListaPrecioPredeterminada */
            $obtenerListaPrecioPredeterminada = $container->get(ObtenerListaPrecioPredeterminada::class);
            $idLista = $obtenerListaPrecioPredeterminada->ejecutar();
        }

        return $idLista;
    }

    /**
     * @param array<int, array<string, mixed>> $productos
     * @return array<int, array<string, mixed>>
     */
    private function productosJson(Container $container, array $productos): array
    {
        $datos = [];

        foreach ($productos as $producto) {
            $productoVenta = $this->productoParaVenta($container, (int) ($producto['id'] ?? 0));
            $stock = $this->stockProducto($container, (int) ($producto['id'] ?? 0));
            $precio = (float) ($producto['precio'] ?? 0);
            $precio = $precio > 0 ? $precio : (float) ($productoVenta['precio_final'] ?? ($producto['precio_final'] ?? 0));
            $datos[] = [
                'id' => (int) ($producto['id'] ?? 0),
                'codigo_barras' => (string) ($producto['cod_barras'] ?? ''),
                'nombre' => (string) ($producto['nombre'] ?? ''),
                'precio' => $precio,
                'stock' => (float) ($producto['stock_cantidad'] ?? ($stock['cantidad'] ?? 0)),
                'stock_minimo' => (float) ($stock['minimo'] ?? 0),
                'stock_bajo' => (bool) ($stock['bajo_minimo'] ?? false),
                'activo' => (bool) ($producto['activo'] ?? true),
            ];
        }

        return $datos;
    }

    /**
     * @return array<string, mixed>
     */
    private function productoParaVenta(Container $container, int $idProducto): array
    {
        /** @var ObtenerProductoParaVenta $obtenerProductoParaVenta */
        $obtenerProductoParaVenta = $container->get(ObtenerProductoParaVenta::class);
        $producto = $idProducto > 0 ? $obtenerProductoParaVenta->ejecutar($idProducto) : null;
        $datos = is_array($producto) ? $producto : [];

        return $datos;
    }

    /**
     * @return array<string, mixed>
     */
    private function stockProducto(Container $container, int $idProducto): array
    {
        /** @var ObtenerStockPorProducto $obtenerStockPorProducto */
        $obtenerStockPorProducto = $container->get(ObtenerStockPorProducto::class);
        $stock = $idProducto > 0 ? $obtenerStockPorProducto->ejecutar($idProducto) : null;
        $datos = [
            'cantidad' => 0.0,
            'minimo' => 0.0,
            'bajo_minimo' => false,
        ];

        if ($stock instanceof Stock) {
            $datos = [
                'cantidad' => $stock->cantidad(),
                'minimo' => $stock->stockMinimo(),
                'bajo_minimo' => $stock->estaBajoMinimo(),
            ];
        }

        return $datos;
    }

    /**
     * @return array<string, mixed>
     */
    private function resultadoCarrito(Container $container, array $resultado): array
    {
        $payload = $this->carritoPayloadDesdeCarrito($container, (array) ($resultado['carrito'] ?? []));
        $payload['ok'] = (bool) ($resultado['ok'] ?? false);
        $payload['error'] = (string) ($resultado['error'] ?? '');

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function carritoPayload(Container $container): array
    {
        /** @var ObtenerCarritoVenta $obtenerCarritoVenta */
        $obtenerCarritoVenta = $container->get(ObtenerCarritoVenta::class);
        $payload = $this->carritoPayloadDesdeCarrito($container, $obtenerCarritoVenta->ejecutar());

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function carritoPayloadDesdeCarrito(Container $container, array $carrito): array
    {
        $items = [];
        $subtotal = 0.0;
        $descuento = 0.0;

        foreach ($carrito as $idx => $item) {
            $cantidad = (float) ($item['cantidad'] ?? 0);
            $precioUnit = (float) ($item['precio_unit'] ?? 0);
            $descuentoItem = (float) ($item['descuento'] ?? 0);
            $bruto = max(0.0, $cantidad * $precioUnit);
            $descuentoMonto = max(0.0, ($bruto * min(100.0, max(0.0, $descuentoItem))) / 100);
            $subtotal += $bruto;
            $descuento += $descuentoMonto;
            $items[] = [
                'idx' => (int) $idx,
                'id_producto' => (int) ($item['id_producto'] ?? 0),
                'nombre' => (string) ($item['nombre'] ?? ''),
                'cantidad' => $cantidad,
                'precio_unit' => $precioUnit,
                'descuento' => $descuentoItem,
                'subtotal' => max(0.0, $bruto - $descuentoMonto),
            ];
        }

        /** @var CalcularTotalCarritoVenta $calcularTotalCarritoVenta */
        $calcularTotalCarritoVenta = $container->get(CalcularTotalCarritoVenta::class);
        $payload = [
            'items' => $items,
            'subtotal' => $subtotal,
            'descuento' => $descuento,
            'total' => $calcularTotalCarritoVenta->ejecutar($carrito),
            'cantidad_items' => count($items),
        ];

        return $payload;
    }
}
