<?php

declare(strict_types=1);

namespace Ventas\Stock\Domain\Repositorios;

use Ventas\Stock\Domain\Entidades\Stock;

interface StockRepository
{
    /**
     * @return Stock[]
     */
    public function listar(): array;

    public function buscarPorId(int $id): ?Stock;

    /**
     * @return Stock[]
     */
    public function listarGeneralesActivos(): array;
}
