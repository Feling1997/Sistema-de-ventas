<?php

declare(strict_types=1);

namespace Ventas\Stock\Application;

use Ventas\Stock\Domain\Repositorios\StockRepository;

final class MarcarAlertaLeida
{
    public function __construct(private readonly StockRepository $stockRepository)
    {
    }

    public function ejecutar(int $idProducto, int $idUsuario): bool
    {
        return $this->stockRepository->marcarAlertaLeida($idProducto, $idUsuario);
    }
}
