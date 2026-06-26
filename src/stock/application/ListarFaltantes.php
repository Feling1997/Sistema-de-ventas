<?php

declare(strict_types=1);

namespace Ventas\Stock\Application;

use Ventas\Stock\Domain\Repositorios\StockRepository;

final class ListarFaltantes
{
    public function __construct(private readonly StockRepository $stockRepository)
    {
    }

    public function ejecutar(bool $soloMinimo = true): array
    {
        return $this->stockRepository->listarFaltantes($soloMinimo);
    }
}
