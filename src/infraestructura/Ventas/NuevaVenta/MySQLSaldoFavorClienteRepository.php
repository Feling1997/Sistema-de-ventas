<?php

declare(strict_types=1);

namespace Ventas\Infraestructura\Ventas\NuevaVenta;

use PDO;
use Ventas\Dominio\Ventas\NuevaVenta\Repositorios\SaldoFavorClienteRepository;

final class MySQLSaldoFavorClienteRepository implements SaldoFavorClienteRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function obtenerSaldos(): array
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
