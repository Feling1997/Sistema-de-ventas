<?php

declare(strict_types=1);

namespace Ventas\Aplicacion\Stock\CasosUso;

use Ventas\Dominio\Stock\Entidades\Stock;
use Ventas\Dominio\Stock\Repositorios\StockRepository;

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
