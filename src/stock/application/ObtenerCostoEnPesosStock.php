<?php

declare(strict_types=1);

namespace Ventas\Stock\Application;

use Ventas\Stock\Domain\Repositorios\StockRepository;

final class ObtenerCostoEnPesosStock
{
    public function __construct(private readonly StockRepository $stockRepository)
    {
    }

    public function ejecutar(float $costoOrigen, string $moneda): float
    {
        $moneda = strtoupper(trim($moneda)) === 'USD' ? 'USD' : 'ARS';
        $resultado = max(0, $costoOrigen);

        if ($moneda === 'USD') {
            $resultado *= $this->stockRepository->obtenerCotizacionDolar();
        }

        return $resultado;
    }
}
