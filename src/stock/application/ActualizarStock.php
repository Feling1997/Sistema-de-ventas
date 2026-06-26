<?php

declare(strict_types=1);

namespace Ventas\Stock\Application;

use Ventas\Stock\Domain\Repositorios\StockRepository;

final class ActualizarStock
{
    public function __construct(private readonly StockRepository $stockRepository)
    {
    }

    public function ejecutar(
        int $id,
        string $nombre,
        string $unidad,
        float $cantidad,
        float $precioCosto,
        int $activo,
        float $stockMinimo = 0,
        float $stockMaximo = 0,
        string $tipoStock = '',
        string $monedaCosto = '',
        float $costoOrigen = 0
    ): bool {
        return $this->stockRepository->actualizar(
            $id,
            $nombre,
            $unidad,
            $cantidad,
            $precioCosto,
            $activo,
            $stockMinimo,
            $stockMaximo,
            $tipoStock,
            $monedaCosto,
            $costoOrigen
        );
    }
}
