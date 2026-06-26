<?php

declare(strict_types=1);

namespace Ventas\Stock\Application;

use Ventas\Stock\Domain\Repositorios\StockRepository;

final class AlertasStockBajo
{
    public function __construct(private readonly StockRepository $stockRepository)
    {
    }

    public function ejecutar(int $idUsuario = 0, bool $mostrarLeidas = true, string $filtro = 'bajo'): array
    {
        return $this->stockRepository->alertasStockBajo($idUsuario, $mostrarLeidas, $filtro);
    }
}
