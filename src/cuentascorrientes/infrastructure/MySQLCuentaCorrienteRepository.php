<?php

declare(strict_types=1);

namespace Ventas\CuentasCorrientes\Infrastructure;

use PDO;
use Ventas\CuentasCorrientes\Domain\Repositorios\CuentaCorrienteRepository;

final class MySQLCuentaCorrienteRepository implements CuentaCorrienteRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function cuotasPendientesDetalle(string $buscar = '', string $estado = 'todos', string $orden = 'vencimiento', string $direccion = 'ASC'): array
    {
        $cuotas = [];
        $params = [];
        $where = ['cc.saldo > 0.00001', "q.estado <> 'PAGADA'"];
        $buscarLimpio = trim($buscar);
        $estadoSeguro = in_array($estado, ['todos', 'vencidos', 'proximos'], true) ? $estado : 'todos';
        $direccionSegura = strtoupper($direccion) === 'DESC' ? 'DESC' : 'ASC';
        $ordenes = [
            'vencimiento' => 'q.vencimiento',
            'fecha' => 'q.vencimiento',
            'cliente' => 'c.nombre',
            'nombre' => 'c.nombre',
            'monto' => 'q.monto',
            'saldo' => 'pendiente',
            'stock' => 'q.numero',
            'estado' => 'vencida',
            'precio' => 'pendiente',
        ];
        $ordenSeguro = array_key_exists($orden, $ordenes) ? $orden : 'vencimiento';

        if ($buscarLimpio !== '') {
            $where[] = '(c.nombre LIKE ? OR cc.concepto LIKE ? OR q.vencimiento LIKE ?)';
            $like = '%' . $buscarLimpio . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        if ($estadoSeguro === 'vencidos') {
            $where[] = 'q.vencimiento < CURDATE()';
        } elseif ($estadoSeguro === 'proximos') {
            $where[] = 'q.vencimiento >= CURDATE()';
        }

        $orderSql = $ordenes[$ordenSeguro] . ' ' . $direccionSegura . ', c.nombre ASC, q.numero ASC';
        $sql = "SELECT q.id, q.id_cuenta, q.numero, q.vencimiento, q.monto, q.pagado,
                       GREATEST(0, q.monto - q.pagado) AS pendiente,
                       CASE WHEN q.vencimiento < CURDATE() THEN 1 ELSE 0 END AS vencida,
                       cc.concepto, cc.total, cc.saldo, cc.estado AS cuenta_estado,
                       c.nombre AS cliente_nombre
                FROM cuentas_corrientes_cuotas q
                INNER JOIN cuentas_corrientes cc ON cc.id = q.id_cuenta
                INNER JOIN clientes c ON c.id = cc.id_cliente
                WHERE " . implode(' AND ', $where) . '
                ORDER BY ' . $orderSql;
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
        $filas = $statement->fetchAll();

        if (is_array($filas)) {
            $cuotas = $filas;
        }

        return $cuotas;
    }

    public function resumenGeneral(): array
    {
        $resumen = ['cuentas' => 0, 'saldo' => 0, 'vencidas' => 0, 'proximas' => 0, 'recibos' => 0, 'cobrado' => 0];
        $statementCuentas = $this->pdo->query('SELECT COUNT(*) AS cuentas, COALESCE(SUM(saldo), 0) AS saldo FROM cuentas_corrientes WHERE saldo > 0.00001');
        $filaCuentas = $statementCuentas ? ($statementCuentas->fetch() ?: []) : [];
        $statementCuotas = $this->pdo->query("SELECT
                SUM(CASE WHEN q.estado <> 'PAGADA' AND q.vencimiento < CURDATE() THEN 1 ELSE 0 END) AS vencidas,
                SUM(CASE WHEN q.estado <> 'PAGADA' AND q.vencimiento >= CURDATE() THEN 1 ELSE 0 END) AS proximas
                FROM cuentas_corrientes_cuotas q INNER JOIN cuentas_corrientes cc ON cc.id = q.id_cuenta WHERE cc.saldo > 0.00001");
        $filaCuotas = $statementCuotas ? ($statementCuotas->fetch() ?: []) : [];
        $statementRecibos = $this->pdo->query('SELECT COUNT(*) AS recibos, COALESCE(SUM(monto), 0) AS cobrado FROM cuentas_corrientes_recibos');
        $filaRecibos = $statementRecibos ? ($statementRecibos->fetch() ?: []) : [];

        $resumen['cuentas'] = (int) ($filaCuentas['cuentas'] ?? 0);
        $resumen['saldo'] = (float) ($filaCuentas['saldo'] ?? 0);
        $resumen['vencidas'] = (int) ($filaCuotas['vencidas'] ?? 0);
        $resumen['proximas'] = (int) ($filaCuotas['proximas'] ?? 0);
        $resumen['recibos'] = (int) ($filaRecibos['recibos'] ?? 0);
        $resumen['cobrado'] = (float) ($filaRecibos['cobrado'] ?? 0);

        return $resumen;
    }

    public function listarRecibos(int $limite = 50, string $ordenSql = 'r.fecha DESC'): array
    {
        $recibos = [];
        $limiteSeguro = max(1, min(200, $limite));
        $sql = "SELECT r.*, COALESCE(cc.concepto, CASE WHEN r.tipo = 'ANTICIPO' THEN 'Anticipo a favor' WHEN r.tipo = 'APLICACION' THEN r.observacion ELSE 'Movimiento' END) AS concepto, c.nombre AS cliente_nombre
                FROM cuentas_corrientes_recibos r
                LEFT JOIN cuentas_corrientes cc ON cc.id = r.id_cuenta
                INNER JOIN clientes c ON c.id = COALESCE(r.id_cliente, cc.id_cliente)
                ORDER BY " . $ordenSql . ', r.id DESC
                LIMIT ' . $limiteSeguro;
        $statement = $this->pdo->query($sql);
        $filas = $statement ? ($statement->fetchAll() ?: []) : [];

        if (is_array($filas)) {
            $recibos = $filas;
        }

        return $recibos;
    }

    public function saldosFavorClientes(): array
    {
        $saldos = [];
        $statement = $this->pdo->query("SELECT id_cliente, COALESCE(SUM(CASE WHEN tipo = 'ANTICIPO' THEN monto WHEN tipo = 'APLICACION' THEN -monto ELSE 0 END), 0) AS saldo
                FROM cuentas_corrientes_recibos
                WHERE id_cliente IS NOT NULL AND tipo IN ('ANTICIPO', 'APLICACION')
                GROUP BY id_cliente
                HAVING saldo > 0.00001");
        $filas = $statement ? ($statement->fetchAll() ?: []) : [];

        foreach ($filas as $fila) {
            $saldos[(int) $fila['id_cliente']] = round((float) $fila['saldo'], 2);
        }

        return $saldos;
    }

    public function buscarCuenta(int $id): ?array
    {
        $cuenta = null;

        if ($id > 0) {
            $statement = $this->pdo->prepare('SELECT cc.*, c.nombre AS cliente_nombre, c.dni AS cliente_documento FROM cuentas_corrientes cc INNER JOIN clientes c ON c.id = cc.id_cliente WHERE cc.id = ? LIMIT 1');
            $statement->execute([$id]);
            $fila = $statement->fetch();

            if (is_array($fila)) {
                $cuenta = $fila;
            }
        }

        return $cuenta;
    }

    public function cuotasPendientes(int $idCuenta): array
    {
        $cuotas = [];

        if ($idCuenta > 0) {
            $statement = $this->pdo->prepare("SELECT *, GREATEST(0, monto - pagado) AS pendiente FROM cuentas_corrientes_cuotas WHERE id_cuenta = ? AND estado <> 'PAGADA' ORDER BY vencimiento ASC, numero ASC");
            $statement->execute([$idCuenta]);
            $filas = $statement->fetchAll();

            if (is_array($filas)) {
                $cuotas = $filas;
            }
        }

        return $cuotas;
    }

    public function buscarRecibo(int $id): ?array
    {
        $recibo = null;

        if ($id > 0) {
            $statement = $this->pdo->prepare("SELECT r.*, COALESCE(cc.concepto, CASE WHEN r.tipo = 'ANTICIPO' THEN 'Anticipo a favor' WHEN r.tipo = 'APLICACION' THEN r.observacion ELSE 'Movimiento' END) AS concepto, c.nombre AS cliente_nombre, c.dni AS cliente_documento FROM cuentas_corrientes_recibos r LEFT JOIN cuentas_corrientes cc ON cc.id = r.id_cuenta INNER JOIN clientes c ON c.id = COALESCE(r.id_cliente, cc.id_cliente) WHERE r.id = ? LIMIT 1");
            $statement->execute([$id]);
            $fila = $statement->fetch();

            if (is_array($fila)) {
                $recibo = $fila;
            }
        }

        return $recibo;
    }

    public function cantidadVencidasNoLeidas(int $idUsuario): int
    {
        $cantidad = 0;

        if ($idUsuario > 0) {
            $statementLectura = $this->pdo->prepare('SELECT leido_hasta FROM cuentas_corrientes_alertas_lecturas WHERE id_usuario = ? LIMIT 1');
            $statementLectura->execute([$idUsuario]);
            $lectura = $statementLectura->fetch();
            $leidoHasta = is_array($lectura) ? (string) ($lectura['leido_hasta'] ?? '') : '';
            $params = [];
            $sql = "SELECT COUNT(*) AS total
                    FROM cuentas_corrientes_cuotas q
                    INNER JOIN cuentas_corrientes cc ON cc.id = q.id_cuenta
                    WHERE cc.saldo > 0.00001
                      AND q.estado <> 'PAGADA'
                      AND q.vencimiento < CURDATE()";

            if ($leidoHasta !== '') {
                $sql .= ' AND q.vencimiento >= ?';
                $params[] = $leidoHasta;
            }

            $statement = $this->pdo->prepare($sql);
            $statement->execute($params);
            $fila = $statement->fetch();
            $cantidad = (int) ($fila['total'] ?? 0);
        }

        return $cantidad;
    }

    public function marcarCuotaPagada(int $idCuota): bool
    {
        $ok = false;

        if ($idCuota > 0) {
            try {
                $this->pdo->beginTransaction();
                $statement = $this->pdo->prepare('SELECT id_cuenta, monto, pagado FROM cuentas_corrientes_cuotas WHERE id = ? LIMIT 1');
                $statement->execute([$idCuota]);
                $cuota = $statement->fetch();

                if (is_array($cuota)) {
                    $pendiente = max(0, (float) $cuota['monto'] - (float) $cuota['pagado']);
                    $this->pdo->prepare("UPDATE cuentas_corrientes_cuotas SET pagado = monto, estado = 'PAGADA', pagado_en = NOW() WHERE id = ?")->execute([$idCuota]);
                    $this->pdo->prepare('UPDATE cuentas_corrientes SET saldo = GREATEST(0, saldo - ?) WHERE id = ?')->execute([$pendiente, (int) $cuota['id_cuenta']]);
                    $this->pdo->prepare("UPDATE cuentas_corrientes SET estado = 'CERRADA' WHERE id = ? AND saldo <= 0.00001")->execute([(int) $cuota['id_cuenta']]);
                    $this->pdo->commit();
                    $ok = true;
                } else {
                    $this->pdo->rollBack();
                }
            } catch (\Throwable $throwable) {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }
            }
        }

        return $ok;
    }

    public function cancelarCuenta(int $idCuenta): bool
    {
        $ok = false;

        if ($idCuenta > 0) {
            try {
                $this->pdo->beginTransaction();
                $this->pdo->prepare("UPDATE cuentas_corrientes SET saldo = 0, estado = 'CANCELADA' WHERE id = ?")->execute([$idCuenta]);
                $this->pdo->prepare("UPDATE cuentas_corrientes_cuotas SET estado = 'CANCELADA' WHERE id_cuenta = ? AND estado <> 'PAGADA'")->execute([$idCuenta]);
                $this->pdo->commit();
                $ok = true;
            } catch (\Throwable $throwable) {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }
            }
        }

        return $ok;
    }

    public function marcarAlertasLeidas(int $idUsuario): void
    {
        if ($idUsuario > 0) {
            $statement = $this->pdo->prepare('INSERT INTO cuentas_corrientes_alertas_lecturas (id_usuario, leido_hasta) VALUES (?, CURDATE()) ON DUPLICATE KEY UPDATE leido_hasta = VALUES(leido_hasta), actualizado_en = CURRENT_TIMESTAMP');
            $statement->execute([$idUsuario]);
        }
    }

    public function registrarAnticipo(int $idCliente, float $importe, string $observacion, int $idUsuario, string $formaPago = 'contado'): array
    {
        $resultado = ['ok' => false, 'id_recibo' => 0, 'error' => ''];

        if ($idCliente <= 0 || $importe <= 0) {
            $resultado['error'] = 'Datos invalidos.';
        } else {
            try {
                $statementCliente = $this->pdo->prepare('SELECT id FROM clientes WHERE id = ? LIMIT 1');
                $statementCliente->execute([$idCliente]);

                if (is_array($statementCliente->fetch())) {
                    $statement = $this->pdo->prepare("INSERT INTO cuentas_corrientes_recibos (id_cuenta, id_cliente, tipo, monto, forma_pago, observacion) VALUES (NULL, ?, 'ANTICIPO', ?, ?, ?)");
                    $statement->execute([$idCliente, $importe, $formaPago, $observacion]);
                    $resultado = ['ok' => true, 'id_recibo' => (int) $this->pdo->lastInsertId(), 'error' => ''];
                } else {
                    $resultado['error'] = 'Cliente invalido.';
                }
            } catch (\Throwable $throwable) {
                $resultado = ['ok' => false, 'id_recibo' => 0, 'error' => $throwable->getMessage()];
            }
        }

        return $resultado;
    }

    public function registrarPago(int $idCuenta, array $cuotas, float $importe, string $observacion, int $idUsuario, string $formaPago = 'contado'): array
    {
        $resultado = ['ok' => false, 'id_recibo' => 0, 'error' => ''];

        if ($idCuenta <= 0 || $importe <= 0) {
            $resultado['error'] = 'Datos invalidos.';
        } else {
            try {
                $this->pdo->beginTransaction();
                $statementCuenta = $this->pdo->prepare('SELECT id_cliente, saldo FROM cuentas_corrientes WHERE id = ? LIMIT 1 FOR UPDATE');
                $statementCuenta->execute([$idCuenta]);
                $cuenta = $statementCuenta->fetch();

                if (!is_array($cuenta) || $importe > round((float) $cuenta['saldo'], 2) + 0.00001) {
                    $this->pdo->rollBack();
                    $resultado['error'] = 'Cuenta sin deuda pendiente.';
                } else {
                    $idsCuotas = array_values(array_filter(array_map('intval', $cuotas), fn (int $id): bool => $id > 0));
                    $placeholders = count($idsCuotas) > 0 ? 'AND id IN (' . implode(',', array_fill(0, count($idsCuotas), '?')) . ')' : '';
                    $statementCuotas = $this->pdo->prepare("SELECT id, monto, pagado FROM cuentas_corrientes_cuotas WHERE id_cuenta = ? AND estado <> 'PAGADA' " . $placeholders . ' ORDER BY vencimiento ASC, numero ASC');
                    $params = [$idCuenta];

                    foreach ($idsCuotas as $idCuota) {
                        $params[] = $idCuota;
                    }

                    $statementCuotas->execute($params);
                    $cuotasPendientes = $statementCuotas->fetchAll();
                    $pendienteCuotas = 0.0;

                    foreach ($cuotasPendientes as $cuota) {
                        $pendienteCuotas += max(0, (float) $cuota['monto'] - (float) $cuota['pagado']);
                    }

                    if ($importe > round($pendienteCuotas, 2) + 0.00001) {
                        $this->pdo->rollBack();
                        $resultado['error'] = 'El monto supera el pendiente de las cuotas.';
                    } else {
                        $restante = $importe;
                        $aplicado = 0.0;

                        foreach ($cuotasPendientes as $cuota) {
                            if ($restante > 0) {
                                $pendiente = max(0, (float) $cuota['monto'] - (float) $cuota['pagado']);
                                $pago = min($pendiente, $restante);

                                if ($pago > 0) {
                                    $nuevoPagado = (float) $cuota['pagado'] + $pago;
                                    $estado = $nuevoPagado + 0.00001 >= (float) $cuota['monto'] ? 'PAGADA' : 'PARCIAL';
                                    $this->pdo->prepare("UPDATE cuentas_corrientes_cuotas SET pagado = ?, estado = ?, pagado_en = CASE WHEN ? = 'PAGADA' THEN NOW() ELSE pagado_en END WHERE id = ?")
                                        ->execute([$nuevoPagado, $estado, $estado, (int) $cuota['id']]);
                                    $restante -= $pago;
                                    $aplicado += $pago;
                                }
                            }
                        }

                        if ($aplicado <= 0) {
                            $this->pdo->rollBack();
                            $resultado['error'] = 'No se pudo aplicar el pago.';
                        } else {
                            $this->pdo->prepare('UPDATE cuentas_corrientes SET saldo = GREATEST(0, saldo - ?) WHERE id = ?')->execute([$aplicado, $idCuenta]);
                            $this->pdo->prepare("UPDATE cuentas_corrientes SET estado = 'CERRADA' WHERE id = ? AND saldo <= 0.00001")->execute([$idCuenta]);
                            $statementRecibo = $this->pdo->prepare("INSERT INTO cuentas_corrientes_recibos (id_cuenta, id_cliente, tipo, monto, forma_pago, observacion) VALUES (?, ?, 'PAGO_CUENTA', ?, ?, ?)");
                            $statementRecibo->execute([$idCuenta, (int) $cuenta['id_cliente'], $aplicado, $formaPago, $observacion]);
                            $resultado = ['ok' => true, 'id_recibo' => (int) $this->pdo->lastInsertId(), 'error' => ''];
                            $this->pdo->commit();
                        }
                    }
                }
            } catch (\Throwable $throwable) {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }

                $resultado = ['ok' => false, 'id_recibo' => 0, 'error' => $throwable->getMessage()];
            }
        }

        return $resultado;
    }
}
