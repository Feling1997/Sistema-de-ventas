<?php

declare(strict_types=1);

namespace Ventas\Ventas\Application;

use Ventas\Ventas\Domain\Repositorios\VentaRepository;

final class ObtenerDetalleVenta
{
    public function __construct(private readonly VentaRepository $ventaRepository)
    {
    }

    public function ejecutar(int $idVenta): array
    {
        return $this->ventaRepository->obtenerDetalle($idVenta);
    }
}
