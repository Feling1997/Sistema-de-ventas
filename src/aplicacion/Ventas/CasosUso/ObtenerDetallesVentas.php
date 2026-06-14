<?php

declare(strict_types=1);

namespace Ventas\Aplicacion\Ventas\CasosUso;

use Ventas\Dominio\Ventas\Repositorios\VentaRepository;

final class ObtenerDetallesVentas
{
    public function __construct(private readonly VentaRepository $ventaRepository)
    {
    }

    public function ejecutar(array $ids): array
    {
        $detalles = $this->ventaRepository->obtenerDetallesVentas($ids);

        return $detalles;
    }
}
