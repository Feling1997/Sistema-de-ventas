<?php

declare(strict_types=1);

namespace Ventas\Stock\Application;

use Ventas\Stock\Domain\Repositorios\StockRepository;

final class ResumenAlertasStockBajo
{
    public function __construct(private readonly StockRepository $stockRepository)
    {
    }

    public function ejecutar(int $idUsuario = 0): array
    {
        return $this->stockRepository->resumenAlertasStockBajo($idUsuario);
    }
}
