<?php

declare(strict_types=1);

namespace Ventas\CuentasCorrientes\Infrastructure;

use PDO;
use Ventas\CuentasCorrientes\Domain\Repositorios\CrearCuentaCorrienteVentaRepository;

final class MySQLCuentaCorrienteVentaRepository implements CrearCuentaCorrienteVentaRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function crearCuentaDesdeVenta(
        int $idVenta,
        int $idCliente,
        string $concepto,
        float $saldo,
        int $cantidadCuotas,
        string $primerVencimiento,
        array $vencimientos
    ): void {
        if ($idVenta > 0 && $idCliente > 0 && $saldo > 0 && $cantidadCuotas > 0) {
            try {
                $this->pdo->beginTransaction();
                $statementCuenta = $this->pdo->prepare('INSERT INTO cuentas_corrientes (id_cliente, id_venta, concepto, total, saldo) VALUES (?, ?, ?, ?, ?)');
                $statementCuenta->execute([$idCliente, $idVenta, $concepto, $saldo, $saldo]);
                $idCuenta = (int) $this->pdo->lastInsertId();
                $monto = round($saldo / $cantidadCuotas, 2);
                $fecha = new \DateTime($primerVencimiento);
                $statementCuota = $this->pdo->prepare('INSERT INTO cuentas_corrientes_cuotas (id_cuenta, numero, vencimiento, monto) VALUES (?, ?, ?, ?)');

                for ($i = 1; $i <= $cantidadCuotas; $i++) {
                    $montoCuota = $i === $cantidadCuotas ? round($saldo - ($monto * ($cantidadCuotas - 1)), 2) : $monto;
                    $vencimiento = trim((string) ($vencimientos[$i - 1] ?? ''));
                    $statementCuota->execute([$idCuenta, $i, $vencimiento !== '' ? $vencimiento : $fecha->format('Y-m-d'), $montoCuota]);
                    $fecha->modify('+1 month');
                }

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
