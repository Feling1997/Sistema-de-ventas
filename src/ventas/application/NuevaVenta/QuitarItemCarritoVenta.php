<?php

declare(strict_types=1);

namespace Ventas\Ventas\Application\NuevaVenta;

use Ventas\Ventas\Domain\NuevaVenta\Repositorios\CarritoVentaRepository;

final class QuitarItemCarritoVenta
{
    public function __construct(private readonly CarritoVentaRepository $carritoVentaRepository)
    {
    }

    public function ejecutar(int $idx, int $idProducto): array
    {
        $carrito = $this->carritoVentaRepository->obtener();
        $nuevo = [];

        foreach ($carrito as $i => $item) {
            if (($idx >= 0 && $i !== $idx) || ($idx < 0 && (int) ($item['id_producto'] ?? 0) !== $idProducto)) {
                $nuevo[] = $item;
            }
        }

        $this->carritoVentaRepository->guardar($nuevo);

        return $nuevo;
    }
}
