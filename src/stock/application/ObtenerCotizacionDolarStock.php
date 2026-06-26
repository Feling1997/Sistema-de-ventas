<?php

declare(strict_types=1);

namespace Ventas\Stock\Application;

use Ventas\Stock\Domain\Repositorios\StockRepository;

final class ObtenerCotizacionDolarStock
{
    public function __construct(private readonly StockRepository $stockRepository)
    {
    }

    public function ejecutar(): float
    {
        return $this->stockRepository->obtenerCotizacionDolar();
    }
}
