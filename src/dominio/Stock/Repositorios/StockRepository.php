<?php

declare(strict_types=1);

namespace Ventas\Dominio\Stock\Repositorios;

use Ventas\Dominio\Stock\Entidades\Stock;

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
