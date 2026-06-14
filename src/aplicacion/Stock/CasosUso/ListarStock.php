<?php

declare(strict_types=1);

namespace Ventas\Aplicacion\Stock\CasosUso;

use Ventas\Dominio\Stock\Repositorios\StockRepository;

final class ListarStock
{
    public function __construct(private readonly StockRepository $stockRepository)
    {
    }

    public function ejecutar(): array
    {
        return $this->stockRepository->listar();
    }
}
