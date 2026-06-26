<?php

declare(strict_types=1);

namespace Ventas\Ventas\Infrastructure\NuevaVenta;

use PDO;
use Ventas\CuentasCorrientes\Domain\Repositorios\SaldoFavorClienteRepository as CuentaCorrienteSaldoFavorClienteRepository;
use Ventas\CuentasCorrientes\Infrastructure\MySQLSaldoFavorClienteRepository as MySQLCuentaCorrienteSaldoFavorClienteRepository;
use Ventas\Ventas\Domain\NuevaVenta\Repositorios\SaldoFavorClienteRepository;

final class MySQLSaldoFavorClienteRepository implements SaldoFavorClienteRepository
{
    public function __construct(
        private readonly PDO $pdo,
        private ?CuentaCorrienteSaldoFavorClienteRepository $saldoFavorClienteRepository = null
    ) {
        $this->saldoFavorClienteRepository ??= new MySQLCuentaCorrienteSaldoFavorClienteRepository($this->pdo);
    }

    public function obtenerSaldos(): array
    {
        $saldos = $this->saldoFavorClienteRepository->obtenerSaldosFavorClientes();

        return $saldos;
    }
}
