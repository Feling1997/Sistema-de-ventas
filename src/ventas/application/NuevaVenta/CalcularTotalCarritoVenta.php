<?php

declare(strict_types=1);

namespace Ventas\Ventas\Application\NuevaVenta;

final class CalcularTotalCarritoVenta
{
    public function ejecutar(array $carrito): float
    {
        $total = 0.0;

        foreach ($carrito as $item) {
            $cantidad = (float) ($item['cantidad'] ?? 0);
            $precioUnit = (float) ($item['precio_unit'] ?? 0);
            $descuento = (float) ($item['descuento'] ?? 0);
            $total += $this->calcularSubtotal($cantidad, $precioUnit, $descuento);
        }

        return $total;
    }

    private function calcularSubtotal(float $cantidad, float $precioUnit, float $descuento): float
    {
        $cantidadNormalizada = max(0.0, $cantidad);
        $precioNormalizado = max(0.0, $precioUnit);
        $descuentoNormalizado = max(0.0, min(100.0, $descuento));
        $bruto = $cantidadNormalizada * $precioNormalizado;
        $montoDescuento = ($bruto * $descuentoNormalizado) / 100;
        $subtotal = max(0.0, $bruto - $montoDescuento);

        return $subtotal;
    }
}
