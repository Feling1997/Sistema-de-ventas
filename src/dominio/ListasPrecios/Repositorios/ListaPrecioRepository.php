<?php

declare(strict_types=1);

namespace Ventas\Dominio\ListasPrecios\Repositorios;

use Ventas\Dominio\ListasPrecios\Entidades\ListaPrecio;

interface ListaPrecioRepository
{
    /**
     * @return array<int, ListaPrecio>
     */
    public function listar(): array;

    public function buscarPorId(int $id): ?ListaPrecio;

    public function idPredeterminada(): int;
}
