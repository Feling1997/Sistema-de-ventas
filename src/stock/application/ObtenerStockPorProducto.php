<?php

declare(strict_types=1);

namespace Ventas\Stock\Application;

use Ventas\Stock\Domain\Entidades\Stock;
use Ventas\Stock\Domain\Repositorios\StockRepository;

final class ObtenerStockPorProducto
{
    public function __construct(private readonly StockRepository $stockRepository)
    {
    }

    public function ejecutar(int $idProducto): ?Stock
    {
        return $this->stockRepository->obtenerStockPorProducto($idProducto);
    }
}
