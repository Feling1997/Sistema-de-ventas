<?php

declare(strict_types=1);

namespace Ventas\Ventas\Application\NuevaVenta;

use Ventas\Ventas\Domain\NuevaVenta\Repositorios\CarritoVentaRepository;

final class ObtenerCarritoVenta
{
    public function __construct(private readonly CarritoVentaRepository $carritoVentaRepository)
    {
    }

    public function ejecutar(): array
    {
        return $this->carritoVentaRepository->obtener();
    }
}
