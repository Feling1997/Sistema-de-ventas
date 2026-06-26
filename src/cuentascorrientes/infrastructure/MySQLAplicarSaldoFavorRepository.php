<?php

declare(strict_types=1);

namespace Ventas\CuentasCorrientes\Infrastructure;

use PDO;
use RuntimeException;
use Ventas\CuentasCorrientes\Domain\Repositorios\AplicarSaldoFavorRepository;
use Ventas\CuentasCorrientes\Domain\Repositorios\SaldoFavorClienteRepository;

final class MySQLAplicarSaldoFavorRepository implements AplicarSaldoFavorRepository
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly SaldoFavorClienteRepository $saldoFavorClienteRepository
    ) {
    }

    public function aplicarSaldoFavorVenta(int $idCliente, int $idVenta, float $importe): void
    {
        if ($idCliente > 0 && $idVenta > 0 && $importe > 0) {
            try {
                $this->pdo->beginTransaction();
                $saldo = $this->saldoFavorClienteRepository->obtenerSaldoFavorCliente($idCliente);

                if ($saldo + 0.00001 < $importe) {
                    throw new RuntimeException('Saldo a favor insuficiente.');
                }

                $observacion = 'Aplicado a venta #' . $idVenta;
                $statement = $this->pdo->prepare(
                    "INSERT INTO cuentas_corrientes_recibos (id_cuenta, id_cliente, tipo, monto, forma_pago, observacion)
                     VALUES (NULL, ?, 'APLICACION', ?, 'saldo_favor', ?)"
                );
                $statement->execute([$idCliente, $importe, $observacion]);
                $this->pdo->commit();
            } catch (\Throwable $throwable) {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }

                throw $throwable;
            }
        }
    }
}
