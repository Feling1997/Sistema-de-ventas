<?php

declare(strict_types=1);

namespace Ventas\Stock\Application;

use Ventas\Stock\Domain\Repositorios\StockRepository;

final class ListarStockGeneral
{
    public function __construct(private readonly StockRepository $stockRepository)
    {
    }

    public function ejecutar(): array
    {
        return $this->stockRepository->listarGeneralesActivos();
    }
}
