<?php

declare(strict_types=1);

namespace Ventas\Stock\Application;

use Ventas\Stock\Domain\Entidades\Stock;
use Ventas\Stock\Domain\Repositorios\StockRepository;

final class BuscarStockPorId
{
    public function __construct(private readonly StockRepository $stockRepository)
    {
    }

    public function ejecutar(int $id): ?Stock
    {
        return $this->stockRepository->buscarPorId($id);
    }
}
