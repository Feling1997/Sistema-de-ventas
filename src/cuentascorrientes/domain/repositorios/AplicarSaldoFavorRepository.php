<?php

declare(strict_types=1);

namespace Ventas\CuentasCorrientes\Domain\Repositorios;

interface AplicarSaldoFavorRepository
{
    public function aplicarSaldoFavorVenta(int $idCliente, int $idVenta, float $importe): void;
}
