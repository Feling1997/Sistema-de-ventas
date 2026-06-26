<?php

declare(strict_types=1);

namespace Ventas\Stock\Application;

use Ventas\Stock\Domain\Repositorios\StockRepository;

final class CrearStock
{
    public function __construct(private readonly StockRepository $stockRepository)
    {
    }

    public function ejecutar(
        string $nombre,
        string $unidad,
        float $cantidad,
        float $precioCosto,
        int $activo,
        float $stockMinimo = 0,
        float $stockMaximo = 0,
        string $tipoStock = 'general',
        string $monedaCosto = 'ARS',
        float $costoOrigen = 0
    ): bool {
        return $this->stockRepository->crear(
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
