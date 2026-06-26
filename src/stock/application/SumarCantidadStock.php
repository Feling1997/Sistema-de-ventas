<?php

declare(strict_types=1);

namespace Ventas\Stock\Application;

use Ventas\Stock\Domain\Repositorios\StockRepository;

final class SumarCantidadStock
{
    public function __construct(private readonly StockRepository $stockRepository)
    {
    }

    public function ejecutar(int $id, float $cantidad): bool
    {
        return $this->stockRepository->sumarCantidad($id, $cantidad);
    }
}
