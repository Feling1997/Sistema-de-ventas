<?php

declare(strict_types=1);

namespace Ventas\Aplicacion\Ventas\NuevaVenta;

use Ventas\Productos\Application\ObtenerProductoParaVenta;
use Ventas\Stock\Application\BuscarStockPorId;
use Ventas\Dominio\Ventas\NuevaVenta\Repositorios\CarritoVentaRepository;
use Ventas\Dominio\Ventas\NuevaVenta\Repositorios\ConfiguracionVentaRepository;

final class ActualizarItemCarritoVenta
{
    public function __construct(
        private readonly CarritoVentaRepository $carritoVentaRepository,
        private readonly ObtenerProductoParaVenta $obtenerProductoParaVenta,
        private readonly BuscarStockPorId $buscarStockPorId,
        private readonly ConfiguracionVentaRepository $configuracionVentaRepository
    ) {
    }

    public function ejecutar(
        int $idx,
        float $cantidad,
        float $precioUnit,
        float $descuento
    ): array {
        $carrito = $this->carritoVentaRepository->obtener();
        $resultado = [
            'ok' => false,
            'error' => '',
            'carrito' => $carrito,
        ];

        if ($idx < 0 || !isset($carrito[$idx])) {
            $resultado['error'] = 'Item invalido.';
        } elseif ($cantidad <= 0) {
            $resultado['error'] = 'La cantidad debe ser mayor a cero.';
        } else {
            $precioUnit = $this->normalizarMinimoCero($precioUnit);
            $descuento = $this->normalizarDescuento($descuento);
            $item = $carrito[$idx];
            $idProducto = (int) ($item['id_producto'] ?? 0);
            $producto = $this->obtenerProductoParaVenta->ejecutar($idProducto);

            if ($producto === null || (int) ($producto['activo'] ?? 0) !== 1) {
                $resultado['error'] = 'Producto no disponible.';
            } else {
                $factor = $this->normalizarMinimoCero((float) ($producto['factor_conversion'] ?? 0));
                $idStockConsumo = $this->obtenerIdStockConsumo($producto);
                $errorStock = $this->validarStock($this->configuracionVentaRepository->controlarStockVentas(), $idStockConsumo, $carrito, $idx, $idProducto, $cantidad, $factor);

                if ($errorStock !== '') {
                    $resultado['error'] = $errorStock;
                } else {
                    $carrito[$idx]['cantidad'] = $cantidad;
                    $carrito[$idx]['precio_unit'] = $precioUnit;
                    $carrito[$idx]['descuento'] = $descuento;
                    $this->carritoVentaRepository->guardar($carrito);
                    $resultado = [
                        'ok' => true,
                        'error' => '',
                        'carrito' => $carrito,
                    ];
                }
            }
        }

        return $resultado;
    }

    private function normalizarMinimoCero(float $valor): float
    {
        $normalizado = $valor;

        if ($normalizado < 0) {
            $normalizado = 0;
        }

        return $normalizado;
    }

    private function normalizarDescuento(float $descuento): float
    {
        $normalizado = $this->normalizarMinimoCero($descuento);

        if ($normalizado > 100) {
            $normalizado = 100;
        }

        return $normalizado;
    }

    private function obtenerIdStockConsumo(array $producto): ?int
    {
        $id = null;
        $idStock = $producto['id_stock'] ?? null;
        $idAsociado = $producto['id_asociado'] ?? null;

        if ($idStock !== null && (int) $idStock > 0) {
            $id = (int) $idStock;
        }

        if ($id === null && $idAsociado !== null && (int) $idAsociado > 0) {
            $id = (int) $idAsociado;
        }

        return $id;
    }

    private function validarStock(
        bool $controlarStock,
        ?int $idStockConsumo,
        array $carrito,
        int $idx,
        int $idProducto,
        float $cantidad,
        float $factor
    ): string {
        $error = '';

        if ($controlarStock && $idStockConsumo !== null) {
            $stock = $this->buscarStockPorId->ejecutar($idStockConsumo);

            if ($stock === null) {
                $error = 'Stock no encontrado para el producto.';
            } else {
                $consumo = $this->calcularConsumoStock($cantidad, $factor);

                foreach ($carrito as $i => $itemCargado) {
                    if ($i !== $idx && (int) ($itemCargado['id_producto'] ?? 0) === $idProducto) {
                        $consumo += $this->calcularConsumoStock((float) ($itemCargado['cantidad'] ?? 0), $factor);
                    }
                }

                $disponible = $stock->cantidad();

                if ($consumo > $disponible + 0.0000001) {
                    $error = 'Stock insuficiente. Disponible: ' . $disponible;
                }
            }
        }

        return $error;
    }

    private function calcularConsumoStock(float $cantidadProducto, float $factorConversion): float
    {
        $cantidad = $this->normalizarMinimoCero($cantidadProducto);
        $factor = $this->normalizarMinimoCero($factorConversion);
        $consumo = $cantidad * $factor;

        return $consumo;
    }
}
