<?php

declare(strict_types=1);

namespace Ventas\Ventas\Application;

use Ventas\Ventas\Domain\Repositorios\VentaRepository;

final class ListarVentasPeriodo
{
    public function __construct(private readonly VentaRepository $ventaRepository)
    {
    }

    public function ejecutar(string $fechaDesde, string $fechaHasta, string $ordenCampo, string $ordenDireccion): array
    {
        $ventas = $this->ventaRepository->listarPeriodo($fechaDesde, $fechaHasta, $ordenCampo, $ordenDireccion);

        return $ventas;
    }
}
