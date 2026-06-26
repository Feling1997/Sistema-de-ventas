<?php

declare(strict_types=1);

namespace Ventas\CuentasCorrientes\Infrastructure;

use PDO;
use Ventas\CuentasCorrientes\Domain\Repositorios\SaldoFavorClienteRepository;

final class MySQLSaldoFavorClienteRepository implements SaldoFavorClienteRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function obtenerSaldoFavorCliente(int $idCliente): float
    {
        $saldo = 0.0;

        if ($idCliente > 0) {
            $statement = $this->pdo->prepare(
                "SELECT COALESCE(SUM(CASE WHEN tipo = 'ANTICIPO' THEN monto WHEN tipo = 'APLICACION' THEN -monto ELSE 0 END), 0) AS saldo
                 FROM cuentas_corrientes_recibos
                 WHERE id_cliente = ?"
            );
            $statement->execute([$idCliente]);
            $fila = $statement->fetch();
            $saldo = max(0.0, round((float) ($fila['saldo'] ?? 0), 2));
        }

        return $saldo;
    }

    public function obtenerSaldosFavorClientes(): array
    {
        $saldos = [];
        $statement = $this->pdo->prepare(
            "SELECT id_cliente,
                    COALESCE(SUM(CASE WHEN tipo = 'ANTICIPO' THEN monto WHEN tipo = 'APLICACION' THEN -monto ELSE 0 END), 0) AS saldo
             FROM cuentas_corrientes_recibos
             WHERE id_cliente IS NOT NULL AND tipo IN ('ANTICIPO', 'APLICACION')
             GROUP BY id_cliente
             HAVING saldo > 0.00001"
        );
        $statement->execute();
        $filas = $statement->fetchAll(PDO::FETCH_ASSOC);

        foreach ($filas as $fila) {
            $saldos[(int) $fila['id_cliente']] = round((float) $fila['saldo'], 2);
        }

        return $saldos;
    }
}
