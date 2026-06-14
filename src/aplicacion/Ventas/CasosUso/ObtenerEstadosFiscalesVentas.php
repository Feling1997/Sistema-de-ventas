<?php

declare(strict_types=1);

namespace Ventas\Aplicacion\Ventas\CasosUso;

use Ventas\Dominio\Ventas\Repositorios\VentaRepository;

final class ObtenerEstadosFiscalesVentas
{
    public function __construct(private readonly VentaRepository $ventaRepository)
    {
    }

    public function ejecutar(array $ids): array
    {
        $estados = $this->ventaRepository->obtenerEstadosFiscales($ids);

        return $estados;
    }
}
