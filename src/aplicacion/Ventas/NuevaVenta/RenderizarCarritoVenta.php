<?php

declare(strict_types=1);

namespace Ventas\Aplicacion\Ventas\NuevaVenta;

final class RenderizarCarritoVenta
{
    public function ejecutar(array $carrito): string
    {
        $html = '';

        if (count($carrito) > 0) {
            foreach ($carrito as $idx => $item) {
                $subtotal = $this->calcularSubtotal((float) ($item['cantidad'] ?? 0), (float) ($item['precio_unit'] ?? 0), (float) ($item['descuento'] ?? 0));
                $html .= '<tr>';
                $html .= '<td>' . htmlspecialchars((string) ($item['nombre'] ?? '')) . '</td>';
                $html .= '<td style="text-align:right;"><input form="formActualizarItem' . (int) $idx . '" type="number" step="0.001" min="0.001" class="form-control form-control-sm text-end" name="cantidad" value="' . htmlspecialchars(numero_para_input($item['cantidad'] ?? 1, 3)) . '"></td>';
                $html .= '<td style="text-align:right;"><input form="formActualizarItem' . (int) $idx . '" type="number" step="0.01" min="0" class="form-control form-control-sm text-end" name="precio_unit" value="' . htmlspecialchars(numero_para_input($item['precio_unit'] ?? 0, 2)) . '"></td>';
                $html .= '<td style="text-align:right;"><input form="formActualizarItem' . (int) $idx . '" type="number" step="0.01" min="0" max="100" class="form-control form-control-sm text-end" name="descuento" value="' . htmlspecialchars(numero_para_input($item['descuento'] ?? 0, 2)) . '"></td>';
                $html .= '<td style="text-align:right;">' . htmlspecialchars(moneda_para_mostrar($subtotal)) . '</td>';
                $html .= '<td style="text-align:right;"><div class="sales-line-actions">';
                $html .= '<form id="formActualizarItem' . (int) $idx . '" method="POST" action="index.php?c=ventas&a=actualizar_item" class="m-0">';
                $html .= '<input type="hidden" name="csrf" value="' . htmlspecialchars(csrf_token()) . '">';
                $html .= '<input type="hidden" name="idx" value="' . (int) $idx . '">';
                $html .= '<button class="btn btn-sm btn-outline-primary">Guardar</button>';
                $html .= '</form>';
                $html .= '<a class="btn btn-sm btn-outline-secondary" href="index.php?c=ventas&a=editar_item&idx=' . (int) $idx . '">Editar</a>';
                $html .= '<a class="btn btn-sm btn-outline-danger" href="index.php?c=ventas&a=quitar&idx=' . (int) $idx . '" onclick="return confirm(\'&iquest;Quitar item?\');">Quitar</a>';
                $html .= '</div></td>';
                $html .= '</tr>';
            }
        } else {
            $html = '<tr><td colspan="6" class="text-center text-muted py-4">Todav&iacute;a no hay productos cargados.</td></tr>';
        }

        return $html;
    }

    private function calcularSubtotal(float $cantidad, float $precioUnitario, float $descuento): float
    {
        $cantidadNormalizada = $this->normalizarMinimoCero($cantidad);
        $precioNormalizado = $this->normalizarMinimoCero($precioUnitario);
        $descuentoNormalizado = $this->normalizarDescuento($descuento);
        $bruto = $cantidadNormalizada * $precioNormalizado;
        $subtotal = $this->normalizarMinimoCero($bruto - (($bruto * $descuentoNormalizado) / 100));

        return $subtotal;
    }

    private function normalizarDescuento(float $descuento): float
    {
        $normalizado = $this->normalizarMinimoCero($descuento);

        if ($normalizado > 100) {
            $normalizado = 100;
        }

        return $normalizado;
    }

    private function normalizarMinimoCero(float $valor): float
    {
        $normalizado = $valor;

        if ($normalizado < 0) {
            $normalizado = 0;
        }

        return $normalizado;
    }
}
