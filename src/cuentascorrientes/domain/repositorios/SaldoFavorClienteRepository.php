<?php

declare(strict_types=1);

namespace Ventas\CuentasCorrientes\Domain\Repositorios;

interface SaldoFavorClienteRepository
{
    public function obtenerSaldoFavorCliente(int $idCliente): float;

    public function obtenerSaldosFavorClientes(): array;
}
