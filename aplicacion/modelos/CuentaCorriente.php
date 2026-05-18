<?php
require_once __DIR__ . "/../../configuraciones/base_datos.php";
require_once __DIR__ . "/../../configuraciones/ayudas.php";

class CuentaCorriente {
    public static function asegurar_tablas(): void {
        $pdo = obtener_pdo();
        if ($pdo === null)
            return;
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS cuentas_corrientes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                id_cliente INT NOT NULL,
                id_venta INT NULL,
                concepto VARCHAR(180) NOT NULL,
                total DECIMAL(14,2) NOT NULL DEFAULT 0,
                saldo DECIMAL(14,2) NOT NULL DEFAULT 0,
                estado VARCHAR(20) NOT NULL DEFAULT 'ABIERTA',
                creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_cc_cliente (id_cliente)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $st = $pdo->prepare("SHOW COLUMNS FROM cuentas_corrientes LIKE ?");
            $st->execute(["id_venta"]);
            if (!$st->fetch())
                $pdo->exec("ALTER TABLE cuentas_corrientes ADD COLUMN id_venta INT NULL AFTER id_cliente");
            $pdo->exec("CREATE TABLE IF NOT EXISTS cuentas_corrientes_cuotas (
                id INT AUTO_INCREMENT PRIMARY KEY,
                id_cuenta INT NOT NULL,
                numero INT NOT NULL,
                vencimiento DATE NOT NULL,
                monto DECIMAL(14,2) NOT NULL DEFAULT 0,
                pagado DECIMAL(14,2) NOT NULL DEFAULT 0,
                estado VARCHAR(20) NOT NULL DEFAULT 'PENDIENTE',
                pagado_en DATETIME NULL,
                INDEX idx_cc_cuota_vto (vencimiento),
                INDEX idx_cc_cuota_cuenta (id_cuenta)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $pdo->exec("CREATE TABLE IF NOT EXISTS cuentas_corrientes_recibos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                id_cuenta INT NOT NULL,
                fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                monto DECIMAL(14,2) NOT NULL DEFAULT 0,
                forma_pago VARCHAR(40) NOT NULL DEFAULT 'contado',
                observacion VARCHAR(220) NOT NULL DEFAULT '',
                INDEX idx_cc_recibo_cuenta (id_cuenta)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (Throwable $e) {
            registrar_log("CuentaCorriente::asegurar_tablas", $e->getMessage());
        }
    }

    public static function listar_resumen(bool $solo_deudores = true): array {
        self::asegurar_tablas();
        $pdo = obtener_pdo();
        if ($pdo === null)
            return [];
        try {
            $sql = "SELECT cc.id, cc.concepto, cc.total, cc.saldo, cc.estado, cc.creado_en, c.nombre AS cliente_nombre,
                           MIN(CASE WHEN q.estado <> 'PAGADA' THEN q.vencimiento END) AS proximo_vencimiento,
                           SUM(CASE WHEN q.estado <> 'PAGADA' AND q.vencimiento < CURDATE() THEN 1 ELSE 0 END) AS vencidas
                    FROM cuentas_corrientes cc
                    INNER JOIN clientes c ON c.id = cc.id_cliente
                    LEFT JOIN cuentas_corrientes_cuotas q ON q.id_cuenta = cc.id
                    " . ($solo_deudores ? "WHERE cc.saldo > 0.00001" : "") . "
                    GROUP BY cc.id
                    ORDER BY cc.id DESC";
            $st = $pdo->prepare($sql);
            $st->execute();
            return $st->fetchAll();
        } catch (Throwable $e) {
            registrar_log("CuentaCorriente::listar_resumen", $e->getMessage());
            return [];
        }
    }

    public static function cuotas_alerta(int $dias = 7): array {
        self::asegurar_tablas();
        $pdo = obtener_pdo();
        if ($pdo === null)
            return [];
        try {
            $sql = "SELECT q.*, cc.concepto, c.nombre AS cliente_nombre
                    FROM cuentas_corrientes_cuotas q
                    INNER JOIN cuentas_corrientes cc ON cc.id = q.id_cuenta
                    INNER JOIN clientes c ON c.id = cc.id_cliente
                    WHERE q.estado <> 'PAGADA' AND q.vencimiento <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
                    ORDER BY q.vencimiento ASC";
            $st = $pdo->prepare($sql);
            $st->execute([$dias]);
            return $st->fetchAll();
        } catch (Throwable $e) {
            registrar_log("CuentaCorriente::cuotas_alerta", $e->getMessage());
            return [];
        }
    }

    public static function crear(int $id_cliente, string $concepto, float $total, int $cuotas, string $primer_vencimiento, ?int $id_venta = null, array $vencimientos = []): bool {
        self::asegurar_tablas();
        $pdo = obtener_pdo();
        if ($pdo === null || $id_cliente <= 0 || $total <= 0 || $cuotas <= 0)
            return false;
        try {
            $pdo->beginTransaction();
            $st = $pdo->prepare("INSERT INTO cuentas_corrientes (id_cliente, id_venta, concepto, total, saldo) VALUES (?, ?, ?, ?, ?)");
            $st->execute([$id_cliente, $id_venta, $concepto, $total, $total]);
            $id_cuenta = (int)$pdo->lastInsertId();
            $monto = round($total / $cuotas, 2);
            $fecha = new DateTime($primer_vencimiento);
            $stCuota = $pdo->prepare("INSERT INTO cuentas_corrientes_cuotas (id_cuenta, numero, vencimiento, monto) VALUES (?, ?, ?, ?)");
            for ($i = 1; $i <= $cuotas; $i++) {
                $monto_cuota = $i === $cuotas ? round($total - ($monto * ($cuotas - 1)), 2) : $monto;
                $vto = trim((string)($vencimientos[$i - 1] ?? ""));
                $stCuota->execute([$id_cuenta, $i, $vto !== "" ? $vto : $fecha->format("Y-m-d"), $monto_cuota]);
                $fecha->modify("+1 month");
            }
            $pdo->commit();
            return true;
        } catch (Throwable $e) {
            if ($pdo->inTransaction())
                $pdo->rollBack();
            registrar_log("CuentaCorriente::crear", $e->getMessage());
            return false;
        }
    }

    public static function marcar_cuota_pagada(int $id_cuota): bool {
        self::asegurar_tablas();
        $pdo = obtener_pdo();
        if ($pdo === null || $id_cuota <= 0)
            return false;
        try {
            $pdo->beginTransaction();
            $st = $pdo->prepare("SELECT id_cuenta, monto, pagado FROM cuentas_corrientes_cuotas WHERE id = ? LIMIT 1");
            $st->execute([$id_cuota]);
            $cuota = $st->fetch();
            if (!$cuota) {
                $pdo->rollBack();
                return false;
            }
            $pendiente = max(0, (float)$cuota["monto"] - (float)$cuota["pagado"]);
            $pdo->prepare("UPDATE cuentas_corrientes_cuotas SET pagado = monto, estado = 'PAGADA', pagado_en = NOW() WHERE id = ?")->execute([$id_cuota]);
            $pdo->prepare("UPDATE cuentas_corrientes SET saldo = GREATEST(0, saldo - ?) WHERE id = ?")->execute([$pendiente, (int)$cuota["id_cuenta"]]);
            $pdo->prepare("UPDATE cuentas_corrientes SET estado = 'CERRADA' WHERE id = ? AND saldo <= 0.00001")->execute([(int)$cuota["id_cuenta"]]);
            $pdo->commit();
            return true;
        } catch (Throwable $e) {
            if ($pdo->inTransaction())
                $pdo->rollBack();
            registrar_log("CuentaCorriente::marcar_cuota_pagada", $e->getMessage());
            return false;
        }
    }

    public static function buscar_cuenta(int $id): ?array {
        self::asegurar_tablas();
        $pdo = obtener_pdo();
        if ($pdo === null || $id <= 0)
            return null;
        try {
            $st = $pdo->prepare("SELECT cc.*, c.nombre AS cliente_nombre, c.dni AS cliente_documento FROM cuentas_corrientes cc INNER JOIN clientes c ON c.id = cc.id_cliente WHERE cc.id = ? LIMIT 1");
            $st->execute([$id]);
            $r = $st->fetch();
            return $r ?: null;
        } catch (Throwable $e) {
            registrar_log("CuentaCorriente::buscar_cuenta", $e->getMessage());
            return null;
        }
    }

    public static function cuotas_pendientes(int $id_cuenta): array {
        self::asegurar_tablas();
        $pdo = obtener_pdo();
        if ($pdo === null || $id_cuenta <= 0)
            return [];
        try {
            $st = $pdo->prepare("SELECT *, GREATEST(0, monto - pagado) AS pendiente FROM cuentas_corrientes_cuotas WHERE id_cuenta = ? AND estado <> 'PAGADA' ORDER BY vencimiento ASC, numero ASC");
            $st->execute([$id_cuenta]);
            return $st->fetchAll();
        } catch (Throwable $e) {
            registrar_log("CuentaCorriente::cuotas_pendientes", $e->getMessage());
            return [];
        }
    }

    public static function registrar_pago(int $id_cuenta, float $monto, array $ids_cuotas, string $forma_pago, string $observacion = ""): int {
        self::asegurar_tablas();
        $pdo = obtener_pdo();
        if ($pdo === null || $id_cuenta <= 0 || $monto <= 0)
            return 0;
        try {
            $pdo->beginTransaction();
            $st = $pdo->prepare("SELECT id, monto, pagado FROM cuentas_corrientes_cuotas WHERE id_cuenta = ? AND estado <> 'PAGADA' " . (count($ids_cuotas) > 0 ? "AND id IN (" . implode(",", array_fill(0, count($ids_cuotas), "?")) . ")" : "") . " ORDER BY vencimiento ASC, numero ASC");
            $params = [$id_cuenta];
            foreach ($ids_cuotas as $id)
                $params[] = (int)$id;
            $st->execute($params);
            $cuotas = $st->fetchAll();
            $restante = $monto;
            $aplicado = 0.0;
            foreach ($cuotas as $cuota) {
                if ($restante <= 0)
                    break;
                $pendiente = max(0, (float)$cuota["monto"] - (float)$cuota["pagado"]);
                $pago = min($pendiente, $restante);
                if ($pago <= 0)
                    continue;
                $nuevo_pagado = (float)$cuota["pagado"] + $pago;
                $estado = $nuevo_pagado + 0.00001 >= (float)$cuota["monto"] ? "PAGADA" : "PARCIAL";
                $pdo->prepare("UPDATE cuentas_corrientes_cuotas SET pagado = ?, estado = ?, pagado_en = CASE WHEN ? = 'PAGADA' THEN NOW() ELSE pagado_en END WHERE id = ?")
                    ->execute([$nuevo_pagado, $estado, $estado, (int)$cuota["id"]]);
                $restante -= $pago;
                $aplicado += $pago;
            }
            if ($aplicado <= 0) {
                $pdo->rollBack();
                return 0;
            }
            $pdo->prepare("UPDATE cuentas_corrientes SET saldo = GREATEST(0, saldo - ?) WHERE id = ?")->execute([$aplicado, $id_cuenta]);
            $pdo->prepare("UPDATE cuentas_corrientes SET estado = 'CERRADA' WHERE id = ? AND saldo <= 0.00001")->execute([$id_cuenta]);
            $stRec = $pdo->prepare("INSERT INTO cuentas_corrientes_recibos (id_cuenta, monto, forma_pago, observacion) VALUES (?, ?, ?, ?)");
            $stRec->execute([$id_cuenta, $aplicado, $forma_pago, $observacion]);
            $id_recibo = (int)$pdo->lastInsertId();
            $pdo->commit();
            return $id_recibo;
        } catch (Throwable $e) {
            if ($pdo->inTransaction())
                $pdo->rollBack();
            registrar_log("CuentaCorriente::registrar_pago", $e->getMessage());
            return 0;
        }
    }

    public static function buscar_recibo(int $id): ?array {
        self::asegurar_tablas();
        $pdo = obtener_pdo();
        if ($pdo === null || $id <= 0)
            return null;
        try {
            $st = $pdo->prepare("SELECT r.*, cc.concepto, c.nombre AS cliente_nombre, c.dni AS cliente_documento FROM cuentas_corrientes_recibos r INNER JOIN cuentas_corrientes cc ON cc.id = r.id_cuenta INNER JOIN clientes c ON c.id = cc.id_cliente WHERE r.id = ? LIMIT 1");
            $st->execute([$id]);
            $r = $st->fetch();
            return $r ?: null;
        } catch (Throwable $e) {
            registrar_log("CuentaCorriente::buscar_recibo", $e->getMessage());
            return null;
        }
    }

    public static function cancelar(int $id_cuenta): bool {
        self::asegurar_tablas();
        $pdo = obtener_pdo();
        if ($pdo === null || $id_cuenta <= 0)
            return false;
        try {
            $pdo->beginTransaction();
            $pdo->prepare("UPDATE cuentas_corrientes SET saldo = 0, estado = 'CANCELADA' WHERE id = ?")->execute([$id_cuenta]);
            $pdo->prepare("UPDATE cuentas_corrientes_cuotas SET estado = 'CANCELADA' WHERE id_cuenta = ? AND estado <> 'PAGADA'")->execute([$id_cuenta]);
            $pdo->commit();
            return true;
        } catch (Throwable $e) {
            if ($pdo->inTransaction())
                $pdo->rollBack();
            registrar_log("CuentaCorriente::cancelar", $e->getMessage());
            return false;
        }
    }
}
