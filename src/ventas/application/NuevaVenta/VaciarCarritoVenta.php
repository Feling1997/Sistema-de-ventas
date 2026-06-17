<?php

declare(strict_types=1);

namespace Ventas\Ventas\Application\NuevaVenta;

use Ventas\Ventas\Domain\NuevaVenta\Repositorios\CarritoVentaRepository;

final class VaciarCarritoVenta
{
    public function __construct(private readonly CarritoVentaRepository $carritoVentaRepository)
    {
    }

    public function ejecutar(): array
    {
        $carrito = [];

        $this->carritoVentaRepository->guardar($carrito);

        return $carrito;
    }
}
