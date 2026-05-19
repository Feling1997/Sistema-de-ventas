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
                id_cliente INT NULL,
                tipo VARCHAR(20) NOT NULL DEFAULT 'PAGO_CUENTA',
                fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                monto DECIMAL(14,2) NOT NULL DEFAULT 0,
                forma_pago VARCHAR(40) NOT NULL DEFAULT 'contado',
                observacion VARCHAR(220) NOT NULL DEFAULT '',
                INDEX idx_cc_recibo_cuenta (id_cuenta)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            self::asegurar_columna($pdo, "cuentas_corrientes_recibos", "id_cliente", "ALTER TABLE cuentas_corrientes_recibos ADD COLUMN id_cliente INT NULL AFTER id_cuenta");
            self::asegurar_columna($pdo, "cuentas_corrientes_recibos", "tipo", "ALTER TABLE cuentas_corrientes_recibos ADD COLUMN tipo VARCHAR(20) NOT NULL DEFAULT 'PAGO_CUENTA' AFTER id_cliente");
            self::permitir_cuenta_nula_en_recibos($pdo);
            $pdo->exec("UPDATE cuentas_corrientes_recibos r INNER JOIN cuentas_corrientes cc ON cc.id = r.id_cuenta SET r.id_cliente = cc.id_cliente WHERE r.id_cliente IS NULL");
            $pdo->exec("CREATE TABLE IF NOT EXISTS cuentas_corrientes_alertas_lecturas (
                id_usuario INT PRIMARY KEY,
                leido_hasta DATE NOT NULL,
                actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (Throwable $e) {
            registrar_log("CuentaCorriente::asegurar_tablas", $e->getMessage());
        }
    }

    private static function asegurar_columna(PDO $pdo, string $tabla, string $columna, string $sql): void {
        $st = $pdo->prepare("SHOW COLUMNS FROM `$tabla` LIKE ?");
        $st->execute([$columna]);
        if (!$st->fetch())
            $pdo->exec($sql);
    }

    private static function permitir_cuenta_nula_en_recibos(PDO $pdo): void {
        $st = $pdo->prepare("SHOW COLUMNS FROM cuentas_corrientes_recibos LIKE ?");
        $st->execute(["id_cuenta"]);
        $col = $st->fetch();
        if ($col && strtoupper((string)($col["Null"] ?? "")) === "NO")
            $pdo->exec("ALTER TABLE cuentas_corrientes_recibos MODIFY id_cuenta INT NULL");
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

    public static function cuotas_pendientes_detalle(string $buscar = "", string $estado = "todos", string $orden = "vencimiento"): array {
        self::asegurar_tablas();
        $pdo = obtener_pdo();
        if ($pdo === null)
            return [];
        try {
            $params = [];
            $where = ["cc.saldo > 0.00001", "q.estado <> 'PAGADA'"];
            $buscar = trim($buscar);
            if ($buscar !== "") {
                $where[] = "(c.nombre LIKE ? OR cc.concepto LIKE ? OR q.vencimiento LIKE ?)";
                $like = "%" . $buscar . "%";
                $params[] = $like;
                $params[] = $like;
                $params[] = $like;
            }
            if ($estado === "vencidos")
                $where[] = "q.vencimiento < CURDATE()";
            else if ($estado === "proximos")
                $where[] = "q.vencimiento >= CURDATE()";

            $order_sql = "q.vencimiento ASC, c.nombre ASC, q.numero ASC";
            if ($orden === "cliente")
                $order_sql = "c.nombre ASC, q.vencimiento ASC";
            else if ($orden === "saldo")
                $order_sql = "pendiente DESC, q.vencimiento ASC";
            else if ($orden === "estado")
                $order_sql = "vencida DESC, q.vencimiento ASC";

            $sql = "SELECT q.id, q.id_cuenta, q.numero, q.vencimiento, q.monto, q.pagado,
                           GREATEST(0, q.monto - q.pagado) AS pendiente,
                           CASE WHEN q.vencimiento < CURDATE() THEN 1 ELSE 0 END AS vencida,
                           cc.concepto, cc.total, cc.saldo, cc.estado AS cuenta_estado,
                           c.nombre AS cliente_nombre
                    FROM cuentas_corrientes_cuotas q
                    INNER JOIN cuentas_corrientes cc ON cc.id = q.id_cuenta
                    INNER JOIN clientes c ON c.id = cc.id_cliente
                    WHERE " . implode(" AND ", $where) . "
                    ORDER BY " . $order_sql;
            $st = $pdo->prepare($sql);
            $st->execute($params);
            return $st->fetchAll();
        } catch (Throwable $e) {
            registrar_log("CuentaCorriente::cuotas_pendientes_detalle", $e->getMessage());
            return [];
        }
    }

    public static function resumen_general(): array {
        self::asegurar_tablas();
        $pdo = obtener_pdo();
        if ($pdo === null)
            return ["cuentas" => 0, "saldo" => 0, "vencidas" => 0, "proximas" => 0, "recibos" => 0, "cobrado" => 0];
        try {
            $res = ["cuentas" => 0, "saldo" => 0, "vencidas" => 0, "proximas" => 0, "recibos" => 0, "cobrado" => 0];
            $st = $pdo->query("SELECT COUNT(*) AS cuentas, COALESCE(SUM(saldo), 0) AS saldo FROM cuentas_corrientes WHERE saldo > 0.00001");
            $row = $st ? ($st->fetch() ?: []) : [];
            $res["cuentas"] = (int)($row["cuentas"] ?? 0);
            $res["saldo"] = (float)($row["saldo"] ?? 0);
            $st = $pdo->query("SELECT
                SUM(CASE WHEN q.estado <> 'PAGADA' AND q.vencimiento < CURDATE() THEN 1 ELSE 0 END) AS vencidas,
                SUM(CASE WHEN q.estado <> 'PAGADA' AND q.vencimiento >= CURDATE() THEN 1 ELSE 0 END) AS proximas
                FROM cuentas_corrientes_cuotas q INNER JOIN cuentas_corrientes cc ON cc.id = q.id_cuenta WHERE cc.saldo > 0.00001");
            $row = $st ? ($st->fetch() ?: []) : [];
            $res["vencidas"] = (int)($row["vencidas"] ?? 0);
            $res["proximas"] = (int)($row["proximas"] ?? 0);
            $st = $pdo->query("SELECT COUNT(*) AS recibos, COALESCE(SUM(monto), 0) AS cobrado FROM cuentas_corrientes_recibos");
            $row = $st ? ($st->fetch() ?: []) : [];
            $res["recibos"] = (int)($row["recibos"] ?? 0);
            $res["cobrado"] = (float)($row["cobrado"] ?? 0);
            return $res;
        } catch (Throwable $e) {
            registrar_log("CuentaCorriente::resumen_general", $e->getMessage());
            return ["cuentas" => 0, "saldo" => 0, "vencidas" => 0, "proximas" => 0, "recibos" => 0, "cobrado" => 0];
        }
    }

    public static function listar_recibos(int $limite = 50): array {
        self::asegurar_tablas();
        $pdo = obtener_pdo();
        if ($pdo === null)
            return [];
        try {
            $limite = max(1, min(200, $limite));
            $sql = "SELECT r.*, COALESCE(cc.concepto, CASE WHEN r.tipo = 'ANTICIPO' THEN 'Anticipo a favor' WHEN r.tipo = 'APLICACION' THEN r.observacion ELSE 'Movimiento' END) AS concepto, c.nombre AS cliente_nombre
                    FROM cuentas_corrientes_recibos r
                    LEFT JOIN cuentas_corrientes cc ON cc.id = r.id_cuenta
                    INNER JOIN clientes c ON c.id = COALESCE(r.id_cliente, cc.id_cliente)
                    ORDER BY r.fecha DESC, r.id DESC
                    LIMIT " . $limite;
            $st = $pdo->query($sql);
            return $st ? ($st->fetchAll() ?: []) : [];
        } catch (Throwable $e) {
            registrar_log("CuentaCorriente::listar_recibos", $e->getMessage());
            return [];
        }
    }

    public static function cantidad_vencidas_no_leidas(int $id_usuario): int {
        self::asegurar_tablas();
        $pdo = obtener_pdo();
        if ($pdo === null || $id_usuario <= 0)
            return 0;
        try {
            $stLectura = $pdo->prepare("SELECT leido_hasta FROM cuentas_corrientes_alertas_lecturas WHERE id_usuario = ? LIMIT 1");
            $stLectura->execute([$id_usuario]);
            $leido_hasta = (string)(($stLectura->fetch()["leido_hasta"] ?? ""));
            $params = [];
            $sql = "SELECT COUNT(*) AS total
                    FROM cuentas_corrientes_cuotas q
                    INNER JOIN cuentas_corrientes cc ON cc.id = q.id_cuenta
                    WHERE cc.saldo > 0.00001
                      AND q.estado <> 'PAGADA'
                      AND q.vencimiento < CURDATE()";
            if ($leido_hasta !== "") {
                $sql .= " AND q.vencimiento >= ?";
                $params[] = $leido_hasta;
            }
            $st = $pdo->prepare($sql);
            $st->execute($params);
            return (int)($st->fetch()["total"] ?? 0);
        } catch (Throwable $e) {
            registrar_log("CuentaCorriente::cantidad_vencidas_no_leidas", $e->getMessage());
            return 0;
        }
    }

    public static function marcar_alertas_leidas(int $id_usuario): bool {
        self::asegurar_tablas();
        $pdo = obtener_pdo();
        if ($pdo === null || $id_usuario <= 0)
            return false;
        try {
            $st = $pdo->prepare("INSERT INTO cuentas_corrientes_alertas_lecturas (id_usuario, leido_hasta) VALUES (?, CURDATE()) ON DUPLICATE KEY UPDATE leido_hasta = VALUES(leido_hasta), actualizado_en = CURRENT_TIMESTAMP");
            return $st->execute([$id_usuario]);
        } catch (Throwable $e) {
            registrar_log("CuentaCorriente::marcar_alertas_leidas", $e->getMessage());
            return false;
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
            $stCuenta = $pdo->prepare("SELECT id_cliente, saldo FROM cuentas_corrientes WHERE id = ? LIMIT 1 FOR UPDATE");
            $stCuenta->execute([$id_cuenta]);
            $cuenta = $stCuenta->fetch();
            if (!$cuenta || $monto > round((float)$cuenta["saldo"], 2) + 0.00001) {
                $pdo->rollBack();
                return 0;
            }
            $st = $pdo->prepare("SELECT id, monto, pagado FROM cuentas_corrientes_cuotas WHERE id_cuenta = ? AND estado <> 'PAGADA' " . (count($ids_cuotas) > 0 ? "AND id IN (" . implode(",", array_fill(0, count($ids_cuotas), "?")) . ")" : "") . " ORDER BY vencimiento ASC, numero ASC");
            $params = [$id_cuenta];
            foreach ($ids_cuotas as $id)
                $params[] = (int)$id;
            $st->execute($params);
            $cuotas = $st->fetchAll();
            $pendiente_cuotas = 0.0;
            foreach ($cuotas as $cuota)
                $pendiente_cuotas += max(0, (float)$cuota["monto"] - (float)$cuota["pagado"]);
            if ($monto > round($pendiente_cuotas, 2) + 0.00001) {
                $pdo->rollBack();
                return 0;
            }
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
            $stRec = $pdo->prepare("INSERT INTO cuentas_corrientes_recibos (id_cuenta, id_cliente, tipo, monto, forma_pago, observacion) VALUES (?, ?, 'PAGO_CUENTA', ?, ?, ?)");
            $stRec->execute([$id_cuenta, (int)$cuenta["id_cliente"], $aplicado, $forma_pago, $observacion]);
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

    public static function registrar_anticipo(int $id_cliente, float $monto, string $forma_pago, string $observacion = ""): int {
        self::asegurar_tablas();
        $pdo = obtener_pdo();
        if ($pdo === null || $id_cliente <= 0 || $monto <= 0)
            return 0;
        try {
            $stCliente = $pdo->prepare("SELECT id FROM clientes WHERE id = ? LIMIT 1");
            $stCliente->execute([$id_cliente]);
            if (!$stCliente->fetch())
                return 0;
            $st = $pdo->prepare("INSERT INTO cuentas_corrientes_recibos (id_cuenta, id_cliente, tipo, monto, forma_pago, observacion) VALUES (NULL, ?, 'ANTICIPO', ?, ?, ?)");
            $st->execute([$id_cliente, $monto, $forma_pago, $observacion]);
            return (int)$pdo->lastInsertId();
        } catch (Throwable $e) {
            registrar_log("CuentaCorriente::registrar_anticipo", $e->getMessage());
            return 0;
        }
    }

    public static function saldo_favor_cliente(int $id_cliente): float {
        self::asegurar_tablas();
        $pdo = obtener_pdo();
        if ($pdo === null || $id_cliente <= 0)
            return 0.0;
        try {
            $st = $pdo->prepare("SELECT COALESCE(SUM(CASE WHEN tipo = 'ANTICIPO' THEN monto WHEN tipo = 'APLICACION' THEN -monto ELSE 0 END), 0) AS saldo FROM cuentas_corrientes_recibos WHERE id_cliente = ?");
            $st->execute([$id_cliente]);
            return max(0.0, round((float)($st->fetch()["saldo"] ?? 0), 2));
        } catch (Throwable $e) {
            registrar_log("CuentaCorriente::saldo_favor_cliente", $e->getMessage());
            return 0.0;
        }
    }

    public static function saldos_favor_clientes(): array {
        self::asegurar_tablas();
        $pdo = obtener_pdo();
        if ($pdo === null)
            return [];
        try {
            $sql = "SELECT id_cliente, COALESCE(SUM(CASE WHEN tipo = 'ANTICIPO' THEN monto WHEN tipo = 'APLICACION' THEN -monto ELSE 0 END), 0) AS saldo
                    FROM cuentas_corrientes_recibos
                    WHERE id_cliente IS NOT NULL AND tipo IN ('ANTICIPO', 'APLICACION')
                    GROUP BY id_cliente
                    HAVING saldo > 0.00001";
            $st = $pdo->query($sql);
            $res = [];
            foreach (($st ? $st->fetchAll() : []) as $row)
                $res[(int)$row["id_cliente"]] = round((float)$row["saldo"], 2);
            return $res;
        } catch (Throwable $e) {
            registrar_log("CuentaCorriente::saldos_favor_clientes", $e->getMessage());
            return [];
        }
    }

    public static function aplicar_saldo_favor(int $id_cliente, int $id_venta, float $monto): bool {
        self::asegurar_tablas();
        $pdo = obtener_pdo();
        if ($pdo === null || $id_cliente <= 0 || $id_venta <= 0 || $monto <= 0)
            return false;
        try {
            $pdo->beginTransaction();
            $st = $pdo->prepare("SELECT COALESCE(SUM(CASE WHEN tipo = 'ANTICIPO' THEN monto WHEN tipo = 'APLICACION' THEN -monto ELSE 0 END), 0) AS saldo FROM cuentas_corrientes_recibos WHERE id_cliente = ?");
            $st->execute([$id_cliente]);
            $saldo = round((float)($st->fetch()["saldo"] ?? 0), 2);
            if ($saldo + 0.00001 < $monto) {
                $pdo->rollBack();
                return false;
            }
            $obs = "Aplicado a venta #" . $id_venta;
            $ins = $pdo->prepare("INSERT INTO cuentas_corrientes_recibos (id_cuenta, id_cliente, tipo, monto, forma_pago, observacion) VALUES (NULL, ?, 'APLICACION', ?, 'saldo_favor', ?)");
            $ok = $ins->execute([$id_cliente, $monto, $obs]);
            $pdo->commit();
            return $ok;
        } catch (Throwable $e) {
            if ($pdo->inTransaction())
                $pdo->rollBack();
            registrar_log("CuentaCorriente::aplicar_saldo_favor", $e->getMessage());
            return false;
        }
    }

    public static function buscar_recibo(int $id): ?array {
        self::asegurar_tablas();
        $pdo = obtener_pdo();
        if ($pdo === null || $id <= 0)
            return null;
        try {
            $st = $pdo->prepare("SELECT r.*, COALESCE(cc.concepto, CASE WHEN r.tipo = 'ANTICIPO' THEN 'Anticipo a favor' WHEN r.tipo = 'APLICACION' THEN r.observacion ELSE 'Movimiento' END) AS concepto, c.nombre AS cliente_nombre, c.dni AS cliente_documento FROM cuentas_corrientes_recibos r LEFT JOIN cuentas_corrientes cc ON cc.id = r.id_cuenta INNER JOIN clientes c ON c.id = COALESCE(r.id_cliente, cc.id_cliente) WHERE r.id = ? LIMIT 1");
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
