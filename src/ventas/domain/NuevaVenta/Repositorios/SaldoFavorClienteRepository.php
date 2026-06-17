<?php

declare(strict_types=1);

namespace Ventas\Ventas\Domain\NuevaVenta\Repositorios;

interface SaldoFavorClienteRepository
{
    public function obtenerSaldos(): array;
}
