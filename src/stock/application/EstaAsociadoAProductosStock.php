<?php

declare(strict_types=1);

namespace Ventas\Stock\Application;

use Ventas\Stock\Domain\Repositorios\StockRepository;

final class EstaAsociadoAProductosStock
{
    public function __construct(private readonly StockRepository $stockRepository)
    {
    }

    public function ejecutar(int $idStock): bool
    {
        return $this->stockRepository->estaAsociadoAProductos($idStock);
    }
}
