<?php
require_once __DIR__ . "/../../configuraciones/base_datos.php";
require_once __DIR__ . "/../../configuraciones/ayudas.php";

class ListaPrecio {
    public static function asegurar_tablas(): void {
        $pdo = obtener_pdo();
        if ($pdo === null)
            return;
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS listas_precios (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nombre VARCHAR(80) NOT NULL,
                activo TINYINT(1) NOT NULL DEFAULT 1,
                creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $pdo->exec("CREATE TABLE IF NOT EXISTS producto_precios (
                id_producto INT NOT NULL,
                id_lista INT NOT NULL,
                porcentaje DECIMAL(12,4) NOT NULL DEFAULT 0,
                precio DECIMAL(14,2) NOT NULL DEFAULT 0,
                PRIMARY KEY (id_producto, id_lista)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $pdo->exec("CREATE TABLE IF NOT EXISTS historial_precios (
                id INT AUTO_INCREMENT PRIMARY KEY,
                id_producto INT NOT NULL,
                id_lista INT NOT NULL,
                precio_anterior DECIMAL(14,2) NOT NULL DEFAULT 0,
                precio_nuevo DECIMAL(14,2) NOT NULL DEFAULT 0,
                origen VARCHAR(40) NOT NULL DEFAULT 'manual',
                creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY idx_historial_precios_fecha (creado_en),
                KEY idx_historial_precios_lista (id_lista),
                KEY idx_historial_precios_producto (id_producto)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            self::asegurar_lista_base($pdo, "Costo");
            self::asegurar_lista_base($pdo, "Publico");
            self::asegurar_columna_cliente($pdo);
        } catch (Throwable $e) {
            registrar_log("ListaPrecio::asegurar_tablas", $e->getMessage());
        }
    }

    private static function asegurar_lista_base(PDO $pdo, string $nombre): void {
        $st = $pdo->prepare("SELECT id FROM listas_precios WHERE LOWER(nombre) = LOWER(?) LIMIT 1");
        $st->execute([$nombre]);
        $row = $st->fetch();
        if ($row) {
            $pdo->prepare("UPDATE listas_precios SET activo = 1 WHERE id = ?")->execute([(int)$row["id"]]);
            return;
        }
        $pdo->prepare("INSERT INTO listas_precios (nombre, activo) VALUES (?, 1)")->execute([$nombre]);
    }

    public static function es_lista_costo(array $lista): bool {
        return strtolower(trim((string)($lista["nombre"] ?? ""))) === "costo";
    }

    public static function es_lista_publico(array $lista): bool {
        $nombre = strtolower(trim((string)($lista["nombre"] ?? "")));
        return $nombre === "publico" || $nombre === "público";
    }

    public static function es_lista_base_id(int $id): bool {
        if ($id <= 0)
            return false;
        foreach (self::listar(false) as $lista) {
            if ((int)($lista["id"] ?? 0) === $id)
                return self::es_lista_costo($lista) || self::es_lista_publico($lista);
        }
        return false;
    }

    public static function listar(bool $solo_activas = false): array {
        self::asegurar_tablas();
        $lista = [];
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                $sql = "SELECT id, nombre, activo, creado_en FROM listas_precios";
                if ($solo_activas)
                    $sql .= " WHERE activo = 1";
                $sql .= " ORDER BY id ASC";
                $st = $pdo->prepare($sql);
                $st->execute();
                $rows = $st->fetchAll();
                if (is_array($rows))
                    $lista = $rows;
            } catch (Throwable $e) {
                registrar_log("ListaPrecio::listar", $e->getMessage());
            }
        }
        return $lista;
    }

    public static function id_predeterminada(): int {
        $listas = self::listar(true);
        foreach ($listas as $lista) {
            if (self::es_lista_publico($lista))
                return (int)$lista["id"];
        }
        foreach ($listas as $lista) {
            if (!self::es_lista_costo($lista))
                return (int)$lista["id"];
        }
        return isset($listas[0]["id"]) ? (int)$listas[0]["id"] : 1;
    }

    public static function crear(string $nombre, int $activo): bool {
        self::asegurar_tablas();
        $pdo = obtener_pdo();
        if ($pdo === null)
            return false;
        try {
            $st = $pdo->prepare("INSERT INTO listas_precios (nombre, activo) VALUES (?, ?)");
            return $st->execute([$nombre, $activo]);
        } catch (Throwable $e) {
            registrar_log("ListaPrecio::crear", $e->getMessage());
            return false;
        }
    }

    public static function actualizar(int $id, string $nombre, int $activo): bool {
        self::asegurar_tablas();
        $pdo = obtener_pdo();
        if ($pdo === null || $id <= 0)
            return false;
        try {
            $st = $pdo->prepare("UPDATE listas_precios SET nombre = ?, activo = ? WHERE id = ?");
            return $st->execute([$nombre, $activo, $id]);
        } catch (Throwable $e) {
            registrar_log("ListaPrecio::actualizar", $e->getMessage());
            return false;
        }
    }

    public static function eliminar(int $id): bool {
        self::asegurar_tablas();
        $pdo = obtener_pdo();
        if ($pdo === null || $id <= 0)
            return false;
        try {
            $st = $pdo->prepare("DELETE FROM listas_precios WHERE id = ?");
            return $st->execute([$id]);
        } catch (Throwable $e) {
            registrar_log("ListaPrecio::eliminar", $e->getMessage());
            return false;
        }
    }

    public static function precios_producto(int $id_producto): array {
        self::asegurar_tablas();
        $precios = [];
        $pdo = obtener_pdo();
        if ($pdo !== null && $id_producto > 0) {
            try {
                $st = $pdo->prepare("SELECT id_lista, porcentaje, precio FROM producto_precios WHERE id_producto = ?");
                $st->execute([$id_producto]);
                foreach ($st->fetchAll() as $row)
                    $precios[(int)$row["id_lista"]] = $row;
            } catch (Throwable $e) {
                registrar_log("ListaPrecio::precios_producto", $e->getMessage());
            }
        }
        return $precios;
    }

    public static function guardar_precio_producto(int $id_producto, int $id_lista, float $porcentaje, float $precio): bool {
        return self::guardar_precio_producto_origen($id_producto, $id_lista, $porcentaje, $precio, "manual");
    }

    public static function guardar_precio_producto_origen(int $id_producto, int $id_lista, float $porcentaje, float $precio, string $origen = "manual"): bool {
        self::asegurar_tablas();
        $pdo = obtener_pdo();
        if ($pdo === null || $id_producto <= 0 || $id_lista <= 0)
            return false;
        try {
            if ($porcentaje < 0)
                $porcentaje = 0;
            if ($precio < 0)
                $precio = 0;
            $st_actual = $pdo->prepare("SELECT precio FROM producto_precios WHERE id_producto = ? AND id_lista = ? LIMIT 1");
            $st_actual->execute([$id_producto, $id_lista]);
            $actual = $st_actual->fetch();
            $precio_anterior = $actual ? (float)($actual["precio"] ?? 0) : 0.0;
            $sql = "INSERT INTO producto_precios (id_producto, id_lista, porcentaje, precio)
                    VALUES (?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE porcentaje = VALUES(porcentaje), precio = VALUES(precio)";
            $st = $pdo->prepare($sql);
            $ok = $st->execute([$id_producto, $id_lista, $porcentaje, $precio]);
            if ($ok && abs($precio_anterior - $precio) >= 0.01) {
                $origen = substr(trim($origen) !== "" ? trim($origen) : "manual", 0, 40);
                $hist = $pdo->prepare("INSERT INTO historial_precios (id_producto, id_lista, precio_anterior, precio_nuevo, origen) VALUES (?, ?, ?, ?, ?)");
                $hist->execute([$id_producto, $id_lista, $precio_anterior, $precio, $origen]);
            }
            return $ok;
        } catch (Throwable $e) {
            registrar_log("ListaPrecio::guardar_precio_producto", $e->getMessage());
            return false;
        }
    }

    public static function historial_precios(string $desde = "", string $hasta = "", int $id_lista = 0): array {
        self::asegurar_tablas();
        $lista = [];
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                $params = [];
                $where = [];
                if ($desde !== "") {
                    $where[] = "h.creado_en >= ?";
                    $params[] = $desde . " 00:00:00";
                }
                if ($hasta !== "") {
                    $where[] = "h.creado_en <= ?";
                    $params[] = $hasta . " 23:59:59";
                }
                if ($id_lista > 0) {
                    $where[] = "h.id_lista = ?";
                    $params[] = $id_lista;
                }
                $sql = "SELECT h.creado_en, l.nombre AS lista, p.cod_barras AS codigo, p.nombre AS producto,
                               h.precio_anterior, h.precio_nuevo, h.origen
                        FROM historial_precios h
                        INNER JOIN productos p ON p.id = h.id_producto
                        INNER JOIN listas_precios l ON l.id = h.id_lista";
                if ($where)
                    $sql .= " WHERE " . implode(" AND ", $where);
                $sql .= " ORDER BY h.creado_en DESC, l.nombre ASC, p.nombre ASC";
                $st = $pdo->prepare($sql);
                $st->execute($params);
                $rows = $st->fetchAll();
                if (is_array($rows))
                    $lista = $rows;
            } catch (Throwable $e) {
                registrar_log("ListaPrecio::historial_precios", $e->getMessage());
            }
        }
        return $lista;
    }

    public static function precio_producto(int $id_producto, int $id_lista): ?float {
        $precio = self::precio_producto_cargado($id_producto, $id_lista);
        return $precio !== null ? (float)$precio["precio"] : null;
    }

    public static function precio_producto_cargado(int $id_producto, int $id_lista): ?array {
        self::asegurar_tablas();
        $pdo = obtener_pdo();
        if ($pdo === null || $id_producto <= 0 || $id_lista <= 0)
            return null;
        try {
            $sql = "SELECT l.nombre AS lista_nombre, COALESCE(pp.porcentaje, 0) AS porcentaje,
                           COALESCE(pp.precio, 0) AS precio,
                           COALESCE(s.precio_costo, 0) AS costo_stock
                    FROM productos p
                    INNER JOIN listas_precios l ON l.id = ?
                    LEFT JOIN stock s ON s.id = p.id_stock
                    LEFT JOIN producto_precios pp ON pp.id_producto = p.id AND pp.id_lista = l.id
                    WHERE p.id = ?
                    LIMIT 1";
            $st = $pdo->prepare($sql);
            $st->execute([$id_lista, $id_producto]);
            $r = $st->fetch();
            if (!$r)
                return null;
            $lista = ["nombre" => (string)($r["lista_nombre"] ?? "")];
            if (self::es_lista_costo($lista)) {
                return [
                    "porcentaje" => 0.0,
                    "precio" => (float)($r["costo_stock"] ?? 0)
                ];
            }
            if ((float)($r["precio"] ?? 0) <= 0)
                return null;
            return [
                "porcentaje" => (float)$r["porcentaje"],
                "precio" => (float)$r["precio"]
            ];
        } catch (Throwable $e) {
            registrar_log("ListaPrecio::precio_producto_cargado", $e->getMessage());
        }
        return null;
    }

    public static function precio_producto_completo(int $id_producto, int $id_lista): ?array {
        self::asegurar_tablas();
        $pdo = obtener_pdo();
        if ($pdo === null || $id_producto <= 0 || $id_lista <= 0)
            return null;
        try {
            $sql = "SELECT COALESCE(pp.porcentaje, 0) AS porcentaje,
                           CASE
                             WHEN COALESCE(pp.precio, 0) > 0 THEN pp.precio
                             WHEN COALESCE(pp.porcentaje, 0) > 0 THEN COALESCE(s.precio_costo, 0) * (1 + (pp.porcentaje / 100))
                             ELSE 0
                           END AS precio
                    FROM productos p
                    LEFT JOIN stock s ON s.id = p.id_stock
                    LEFT JOIN producto_precios pp ON pp.id_producto = p.id AND pp.id_lista = ?
                    WHERE p.id = ?
                    LIMIT 1";
            $st = $pdo->prepare($sql);
            $st->execute([$id_lista, $id_producto]);
            $r = $st->fetch();
            if ($r)
                return [
                    "porcentaje" => (float)$r["porcentaje"],
                    "precio" => (float)$r["precio"]
                ];
        } catch (Throwable $e) {
            registrar_log("ListaPrecio::precio_producto_completo", $e->getMessage());
        }
        return null;
    }

    public static function productos_para_exportar(int $id_lista = 0): array {
        self::asegurar_tablas();
        $lista = [];
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                $join = $id_lista > 0 ? "INNER JOIN listas_precios l ON l.id = ? LEFT JOIN producto_precios pp ON pp.id_producto = p.id AND pp.id_lista = l.id" : "";
                $sql = "SELECT p.id, p.nombre, p.cod_barras, p.precio_final, COALESCE(s.unidad, '') AS unidad,
                               " . ($id_lista > 0 ? "CASE
                                 WHEN LOWER(COALESCE(l.nombre, '')) = 'costo' THEN COALESCE(s.precio_costo, 0)
                                 WHEN COALESCE(pp.precio, 0) > 0 THEN pp.precio
                                 ELSE 0
                               END" : "p.precio_final") . " AS precio_lista
                        FROM productos p
                        LEFT JOIN stock s ON s.id = p.id_stock
                        $join
                        WHERE p.activo = 1
                        ORDER BY p.nombre ASC";
                $st = $pdo->prepare($sql);
                $id_lista > 0 ? $st->execute([$id_lista]) : $st->execute();
                $rows = $st->fetchAll();
                if (is_array($rows))
                    $lista = $rows;
            } catch (Throwable $e) {
                registrar_log("ListaPrecio::productos_para_exportar", $e->getMessage());
            }
        }
        return $lista;
    }

    private static function asegurar_columna_cliente(PDO $pdo): void {
        $st = $pdo->prepare("SHOW COLUMNS FROM clientes LIKE ?");
        $st->execute(["id_lista_precio"]);
        if (!$st->fetch())
            $pdo->exec("ALTER TABLE clientes ADD COLUMN id_lista_precio INT NULL AFTER email");
    }
}
