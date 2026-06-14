<?php

declare(strict_types=1);

namespace Ventas\Aplicacion\Ventas\NuevaVenta;

use Ventas\Dominio\Ventas\NuevaVenta\Repositorios\CarritoVentaRepository;

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
