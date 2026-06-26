<?php

declare(strict_types=1);

namespace Ventas\Stock\Application;

use Ventas\Stock\Domain\Repositorios\StockRepository;

final class InicializarAlertasStock
{
    public function __construct(private readonly StockRepository $stockRepository)
    {
    }

    public function ejecutar(): void
    {
        $this->stockRepository->inicializarAlertasStock();
    }
}
