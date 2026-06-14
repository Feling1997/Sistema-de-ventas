<?php

declare(strict_types=1);

namespace Ventas\Aplicacion\Ventas\CasosUso;

use Ventas\Dominio\Ventas\Repositorios\VentaRepository;

final class ObtenerResumenVentasPeriodo
{
    public function __construct(private readonly VentaRepository $ventaRepository)
    {
    }

    public function ejecutar(array $ventas): array
    {
        $ids = [];
        $totalVendido = 0.0;

        foreach ($ventas as $venta) {
            $ids[] = (int) ($venta['id'] ?? 0);
            $totalVendido += (float) ($venta['total'] ?? 0);
        }

        $ganancias = $this->ventaRepository->obtenerGananciasPorIds($ids);
        $resumen = [
            'cantidad_ventas' => count($ventas),
            'total_vendido' => $totalVendido,
            'ganancia' => (float) ($ganancias['ganancia'] ?? 0),
        ];

        return $resumen;
    }
}
