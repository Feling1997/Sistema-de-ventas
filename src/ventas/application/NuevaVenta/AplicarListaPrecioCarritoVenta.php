<?php

declare(strict_types=1);

namespace Ventas\Ventas\Application\NuevaVenta;

use Ventas\Productos\Domain\Repositorios\ProductoRepository;
use Ventas\Ventas\Domain\NuevaVenta\Repositorios\CarritoVentaRepository;

final class AplicarListaPrecioCarritoVenta
{
    public function __construct(
        private readonly CarritoVentaRepository $carritoVentaRepository,
        private readonly ProductoRepository $productoRepository
    ) {
    }

    public function ejecutar(int $idListaPrecio): array
    {
        $carrito = $this->carritoVentaRepository->obtener();

        foreach ($carrito as $indice => $item) {
            $idProducto = (int) ($item['id_producto'] ?? 0);
            $precioLista = $this->productoRepository->obtenerPrecioPorLista($idProducto, $idListaPrecio);
            $precio = $precioLista !== null ? (float) $precioLista['precio'] : null;

            if ($precio !== null && $precio > 0) {
                $carrito[$indice]['precio_unit'] = $precio;
            }
        }

        $this->carritoVentaRepository->guardar($carrito);

        return $carrito;
    }
}
