<?php

declare(strict_types=1);

namespace Ventas\Aplicacion\Ventas\NuevaVenta;

use Ventas\Dominio\Ventas\NuevaVenta\Repositorios\CarritoVentaRepository;

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
