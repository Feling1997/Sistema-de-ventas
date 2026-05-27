<?php

require_once __DIR__ . "/../../configuraciones/base_datos.php";
require_once __DIR__ . "/../../configuraciones/ayudas.php";
require_once __DIR__ . "/UnidadMedida.php";

class Stock{
    private static bool $columnas_minmax_aseguradas = false;
    private static bool $alertas_leidas_aseguradas = false;

    public static function asegurar_columnas_minmax(): void {
        if (self::$columnas_minmax_aseguradas)
            return;
        $pdo = obtener_pdo();
        if ($pdo === null)
            return;
        try {
            UnidadMedida::asegurar_tabla();
            $st = $pdo->prepare("SHOW COLUMNS FROM stock LIKE ?");
            $st->execute(["stock_minimo"]);
            if (!$st->fetch())
                $pdo->exec("ALTER TABLE stock ADD COLUMN stock_minimo DECIMAL(14,3) NOT NULL DEFAULT 0 AFTER cantidad");
            $st = $pdo->prepare("SHOW COLUMNS FROM stock LIKE ?");
            $st->execute(["stock_maximo"]);
            if (!$st->fetch())
                $pdo->exec("ALTER TABLE stock ADD COLUMN stock_maximo DECIMAL(14,3) NOT NULL DEFAULT 0 AFTER stock_minimo");
            $st = $pdo->prepare("SHOW COLUMNS FROM stock LIKE ?");
            $st->execute(["tipo_stock"]);
            if (!$st->fetch()) {
                $pdo->exec("ALTER TABLE stock ADD COLUMN tipo_stock VARCHAR(20) NOT NULL DEFAULT 'general' AFTER unidad");
                $pdo->exec("UPDATE stock s
                            INNER JOIN productos p ON p.id_stock = s.id
                            INNER JOIN (
                                SELECT id_stock, COUNT(*) AS total
                                FROM productos
                                WHERE id_stock IS NOT NULL
                                GROUP BY id_stock
                            ) c ON c.id_stock = s.id
                            SET s.tipo_stock = 'propio'
                            WHERE c.total = 1 AND LOWER(TRIM(s.nombre)) = LOWER(TRIM(p.nombre))");
            }
            self::asegurar_indice($pdo, "stock", "idx_stock_alerta_menu", "ALTER TABLE stock ADD INDEX idx_stock_alerta_menu (activo, tipo_stock, cantidad, stock_minimo)");
            self::asegurar_indice($pdo, "productos", "idx_productos_stock_activo", "ALTER TABLE productos ADD INDEX idx_productos_stock_activo (id_stock, activo)");
            self::asegurar_indice($pdo, "detalle_venta", "idx_detalle_producto_venta", "ALTER TABLE detalle_venta ADD INDEX idx_detalle_producto_venta (id_producto, id_venta)");
            self::$columnas_minmax_aseguradas = true;
        } catch (Throwable $e) {
            registrar_log("Stock::asegurar_columnas_minmax", $e->getMessage());
        }
    }

    public static function asegurar_tabla_alertas_leidas(): void {
        if (self::$alertas_leidas_aseguradas)
            return;
        $pdo = obtener_pdo();
        if ($pdo === null)
            return;
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS stock_alertas_leidas (
                id INT AUTO_INCREMENT PRIMARY KEY,
                id_producto INT NOT NULL,
                fecha_lectura DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                usuario INT NOT NULL DEFAULT 0,
                cantidad_leida DECIMAL(14,3) NOT NULL DEFAULT 0,
                stock_minimo_leido DECIMAL(14,3) NOT NULL DEFAULT 0,
                UNIQUE KEY uq_stock_alerta_producto_usuario (id_producto, usuario),
                KEY idx_stock_alertas_producto (id_producto),
                KEY idx_stock_alertas_usuario (usuario)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $st = $pdo->prepare("SHOW COLUMNS FROM stock_alertas_leidas LIKE ?");
            $st->execute(["cantidad_leida"]);
            if (!$st->fetch())
                $pdo->exec("ALTER TABLE stock_alertas_leidas ADD COLUMN cantidad_leida DECIMAL(14,3) NOT NULL DEFAULT 0 AFTER usuario");
            $st = $pdo->prepare("SHOW COLUMNS FROM stock_alertas_leidas LIKE ?");
            $st->execute(["stock_minimo_leido"]);
            if (!$st->fetch())
                $pdo->exec("ALTER TABLE stock_alertas_leidas ADD COLUMN stock_minimo_leido DECIMAL(14,3) NOT NULL DEFAULT 0 AFTER cantidad_leida");
            self::$alertas_leidas_aseguradas = true;
        } catch (Throwable $e) {
            registrar_log("Stock::asegurar_tabla_alertas_leidas", $e->getMessage());
        }
    }

    private static function asegurar_indice(PDO $pdo, string $tabla, string $indice, string $sql): void {
        try {
            $st = $pdo->prepare("SHOW INDEX FROM `$tabla` WHERE Key_name = ?");
            $st->execute([$indice]);
            if (!$st->fetch())
                $pdo->exec($sql);
        } catch (Throwable $e) {
            registrar_log("Stock::asegurar_indice", $tabla . "." . $indice . " " . $e->getMessage());
        }
    }

    private static function normalizar_tipo_stock(string $tipo_stock): string {
        $tipo_stock = strtolower(trim($tipo_stock));
        return $tipo_stock === "propio" ? "propio" : "general";
    }

    private static function sql_alertas_stock_bajo(): string {
        return "SELECT p.id AS id_producto, p.nombre AS producto, p.activo AS producto_activo,
                       s.id AS id_stock, s.nombre AS stock_nombre, s.unidad, s.cantidad, s.stock_minimo,
                       s.tipo_stock, s.activo AS stock_activo, COALESCE(um.decimales, 3) AS unidad_decimales,
                       COALESCE(mov.ultimo_movimiento, s.creado_en) AS ultimo_movimiento,
                       l.fecha_lectura, l.usuario, l.cantidad_leida, l.stock_minimo_leido,
                       CASE
                         WHEN l.id IS NULL THEN 1
                         WHEN s.cantidad < l.cantidad_leida - 0.000001 THEN 1
                         WHEN s.stock_minimo > l.stock_minimo_leido + 0.000001 THEN 1
                         ELSE 0
                       END AS pendiente
                FROM productos p
                INNER JOIN stock s ON s.id = p.id_stock
                LEFT JOIN unidades_medida um ON um.abreviatura COLLATE utf8mb4_unicode_ci = s.unidad COLLATE utf8mb4_unicode_ci
                LEFT JOIN (
                    SELECT d.id_producto, MAX(v.fecha) AS ultimo_movimiento
                    FROM detalle_venta d
                    INNER JOIN ventas v ON v.id = d.id_venta
                    GROUP BY d.id_producto
                ) mov ON mov.id_producto = p.id
                LEFT JOIN stock_alertas_leidas l ON l.id_producto = p.id AND l.usuario = ?
                WHERE p.activo = 1
                  AND s.activo = 1
                  AND s.tipo_stock = 'propio'
                  AND s.cantidad <= s.stock_minimo
                ORDER BY (s.cantidad <= 0) DESC, pendiente DESC, s.cantidad ASC, p.nombre ASC";
    }
    
    public static function listar_todos(string $orden_sql = "nombre ASC"):array{
        self::asegurar_columnas_minmax();
        $lista=[];
        $pdo=obtener_pdo();
        if($pdo!==null){
            try{
                $sql = "SELECT s.id, s.nombre, s.unidad, s.tipo_stock, s.cantidad, s.stock_minimo, s.stock_maximo, s.precio_costo, s.activo, s.creado_en,
                               COALESCE(um.decimales, 3) AS unidad_decimales
                        FROM stock s
                        LEFT JOIN unidades_medida um ON um.abreviatura COLLATE utf8mb4_unicode_ci = s.unidad COLLATE utf8mb4_unicode_ci
                        ORDER BY " . $orden_sql . ", s.id ASC";
                $st=$pdo->prepare($sql);
                $st->execute();
                $rows=$st->fetchAll();
                if(is_array($rows))
                    $lista=$rows;
            }catch(Throwable $e){
                registrar_log("Stock::listar_todos ",$e->getMessage());
            }
        }
        return $lista;
    }

    public static function buscar_por_id(int $id): ?array {
        self::asegurar_columnas_minmax();
        $fila = null;
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                $sql = "SELECT s.id, s.nombre, s.unidad, s.tipo_stock, s.cantidad, s.stock_minimo, s.stock_maximo, s.precio_costo, s.activo, s.creado_en,
                               COALESCE(um.decimales, 3) AS unidad_decimales
                        FROM stock s
                        LEFT JOIN unidades_medida um ON um.abreviatura COLLATE utf8mb4_unicode_ci = s.unidad COLLATE utf8mb4_unicode_ci
                        WHERE s.id = ? LIMIT 1";
                $st = $pdo->prepare($sql);
                $st->execute([$id]);
                $r = $st->fetch();
                if ($r)
                    $fila = $r;
            } catch (Throwable $e) {
                registrar_log("Stock::buscar_por_id", $e->getMessage());
            }
        }
        return $fila;
    }

    public static function crear(string $nombre, string $unidad, float $cantidad, float $precio_costo, int $activo, float $stock_minimo = 0, float $stock_maximo = 0): bool {
        return self::crear_con_tipo($nombre, $unidad, $cantidad, $precio_costo, $activo, $stock_minimo, $stock_maximo, "general");
    }

    public static function crear_con_tipo(string $nombre, string $unidad, float $cantidad, float $precio_costo, int $activo, float $stock_minimo = 0, float $stock_maximo = 0, string $tipo_stock = "general"): bool {
        self::asegurar_columnas_minmax();
        $ok = false;
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                $tipo_stock = self::normalizar_tipo_stock($tipo_stock);
                $unidad = UnidadMedida::asegurar_desde_form($unidad, []);
                $sql = "INSERT INTO stock (nombre, unidad, tipo_stock, cantidad, stock_minimo, stock_maximo, precio_costo, activo) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $st = $pdo->prepare($sql);
                $ok = $st->execute([$nombre, $unidad, $tipo_stock, $cantidad, $stock_minimo, $stock_maximo, $precio_costo, $activo]);
            } catch (Throwable $e) {
                $ok = false;
                registrar_log("Stock::crear", $e->getMessage());
            }
        }
        return $ok;
    }

    public static function crear_retornar_id(string $nombre, string $unidad, float $cantidad, float $precio_costo, int $activo, float $stock_minimo = 0, float $stock_maximo = 0): int {
        return self::crear_retornar_id_con_tipo($nombre, $unidad, $cantidad, $precio_costo, $activo, $stock_minimo, $stock_maximo, "general");
    }

    public static function crear_retornar_id_con_tipo(string $nombre, string $unidad, float $cantidad, float $precio_costo, int $activo, float $stock_minimo = 0, float $stock_maximo = 0, string $tipo_stock = "general"): int {
        self::asegurar_columnas_minmax();
        $id = 0;
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                $tipo_stock = self::normalizar_tipo_stock($tipo_stock);
                $unidad = UnidadMedida::asegurar_desde_form($unidad, []);
                $sql = "INSERT INTO stock (nombre, unidad, tipo_stock, cantidad, stock_minimo, stock_maximo, precio_costo, activo) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $st = $pdo->prepare($sql);
                if ($st->execute([$nombre, $unidad, $tipo_stock, $cantidad, $stock_minimo, $stock_maximo, $precio_costo, $activo]))
                    $id = (int)$pdo->lastInsertId();
            } catch (Throwable $e) {
                registrar_log("Stock::crear_retornar_id", $e->getMessage());
            }
        }
        return $id;
    }

    public static function actualizar(int $id, string $nombre, string $unidad, float $cantidad, float $precio_costo, int $activo, float $stock_minimo = 0, float $stock_maximo = 0): bool {
        return self::actualizar_con_tipo($id, $nombre, $unidad, $cantidad, $precio_costo, $activo, $stock_minimo, $stock_maximo, "");
    }

    public static function actualizar_con_tipo(int $id, string $nombre, string $unidad, float $cantidad, float $precio_costo, int $activo, float $stock_minimo = 0, float $stock_maximo = 0, string $tipo_stock = ""): bool {
        self::asegurar_columnas_minmax();
        $ok = false;
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                $unidad = UnidadMedida::asegurar_desde_form($unidad, []);
                if ($tipo_stock === "") {
                    $sql = "UPDATE stock SET nombre = ?, unidad = ?, cantidad = ?, stock_minimo = ?, stock_maximo = ?, precio_costo = ?, activo = ? WHERE id = ?";
                    $st = $pdo->prepare($sql);
                    $ok = $st->execute([$nombre, $unidad, $cantidad, $stock_minimo, $stock_maximo, $precio_costo, $activo, $id]);
                } else {
                    $tipo_stock = self::normalizar_tipo_stock($tipo_stock);
                    $sql = "UPDATE stock SET nombre = ?, unidad = ?, tipo_stock = ?, cantidad = ?, stock_minimo = ?, stock_maximo = ?, precio_costo = ?, activo = ? WHERE id = ?";
                    $st = $pdo->prepare($sql);
                    $ok = $st->execute([$nombre, $unidad, $tipo_stock, $cantidad, $stock_minimo, $stock_maximo, $precio_costo, $activo, $id]);
                }
            } catch (Throwable $e) {
                $ok = false;
                registrar_log("Stock::actualizar", $e->getMessage());
            }
        }

        return $ok;
    }

    public static function sumar_cantidad(int $id, float $cantidad): bool {
        $ok = false;
        $pdo = obtener_pdo();
        if ($pdo !== null && $id > 0) {
            try {
                if ($cantidad < 0)
                    $cantidad = 0;
                $sql = "UPDATE stock SET cantidad = cantidad + ? WHERE id = ?";
                $st = $pdo->prepare($sql);
                $ok = $st->execute([$cantidad, $id]);
            } catch (Throwable $e) {
                registrar_log("Stock::sumar_cantidad", $e->getMessage());
            }
        }
        return $ok;
    }

    public static function contar_productos_asociados(int $id_stock): int {
        $cantidad = 0;
        $pdo = obtener_pdo();
        if ($pdo !== null && $id_stock > 0) {
            try {
                $sql = "SELECT COUNT(*) AS total FROM productos WHERE id_stock = ?";
                $st = $pdo->prepare($sql);
                $st->execute([$id_stock]);
                $r = $st->fetch();
                if ($r)
                    $cantidad = (int)$r["total"];
            } catch (Throwable $e) {
                registrar_log("Stock::contar_productos_asociados", $e->getMessage());
            }
        }
        return $cantidad;
    }

    public static function esta_asociado_a_productos(int $id_stock): bool {
        $rel = false;
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                $sql = "SELECT id FROM productos WHERE id_stock = ? LIMIT 1";
                $st = $pdo->prepare($sql);
                $st->execute([$id_stock]);
                $r = $st->fetch();
                if ($r)
                    $rel = true;
            } catch (Throwable $e) {
                registrar_log("Stock::esta_asociado_a_productos", $e->getMessage());
            }
        }
        return $rel;
    }

    public static function eliminar(int $id): bool {
        $ok = false;
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                $sql = "DELETE FROM stock WHERE id = ?";
                $st = $pdo->prepare($sql);
                $ok = $st->execute([$id]);
            } catch (Throwable $e) {
                $ok = false;
                registrar_log("Stock::eliminar", $e->getMessage());
            }
        }
        return $ok;
    }

    public static function recalcular_precios_productos_por_stock(int $id_stock): bool {
        $ok = false;
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                $sqlStock = "SELECT precio_costo FROM stock WHERE id = ? LIMIT 1";
                $st1 = $pdo->prepare($sqlStock);
                $st1->execute([$id_stock]);
                $s = $st1->fetch();
                if ($s) {
                    $precio_costo = (float)$s["precio_costo"];
                    $sqlUpd = "UPDATE productos SET precio_final = ( ? * factor_conversion ) * (1 + (ganancia/100)) WHERE id_stock = ?";
                    $st2 = $pdo->prepare($sqlUpd);
                    $ok = $st2->execute([$precio_costo, $id_stock]);
                } else 
                    $ok = false;
            } catch (Throwable $e) {
                $ok = false;
                registrar_log("Stock::recalcular_precios_productos_por_stock", $e->getMessage());
            }
        }
        return $ok;
    }

    public static function listar_generales_activos(): array {
        self::asegurar_columnas_minmax();
        $lista = [];
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                $sql = "SELECT s.id, s.nombre, s.unidad, s.tipo_stock, s.cantidad, s.stock_minimo, s.stock_maximo, s.precio_costo, s.activo, s.creado_en,
                               COALESCE(um.decimales, 3) AS unidad_decimales
                        FROM stock s
                        LEFT JOIN unidades_medida um ON um.abreviatura COLLATE utf8mb4_unicode_ci = s.unidad COLLATE utf8mb4_unicode_ci
                        WHERE s.activo = 1 AND s.tipo_stock = 'general'
                        ORDER BY s.nombre ASC, s.id ASC";
                $st = $pdo->prepare($sql);
                $st->execute();
                $rows = $st->fetchAll();
                if (is_array($rows))
                    $lista = $rows;
            } catch (Throwable $e) {
                registrar_log("Stock::listar_generales_activos", $e->getMessage());
            }
        }
        return $lista;
    }

    public static function alertas_stock_bajo(int $id_usuario = 0, bool $mostrar_leidas = true, string $filtro = "bajo"): array {
        self::asegurar_columnas_minmax();
        self::asegurar_tabla_alertas_leidas();
        $lista = [];
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                $st = $pdo->prepare(self::sql_alertas_stock_bajo());
                $st->execute([$id_usuario]);
                $rows = $st->fetchAll();
                if (is_array($rows)) {
                    foreach ($rows as $row) {
                        $cantidad = (float)($row["cantidad"] ?? 0);
                        $pendiente = (int)($row["pendiente"] ?? 0) === 1;
                        if (!$mostrar_leidas && !$pendiente)
                            continue;
                        if ($filtro === "criticos" && $cantidad > 0.000001)
                            continue;
                        $lista[] = $row;
                    }
                }
            } catch (Throwable $e) {
                registrar_log("Stock::alertas_stock_bajo", $e->getMessage());
            }
        }
        return $lista;
    }

    public static function resumen_alertas_stock_bajo(int $id_usuario = 0): array {
        self::asegurar_columnas_minmax();
        self::asegurar_tabla_alertas_leidas();
        $resumen = ["total" => 0, "pendientes" => 0, "leidas" => 0];
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                $sql = "SELECT COUNT(*) AS total,
                               SUM(CASE
                                 WHEN l.id IS NULL THEN 1
                                 WHEN s.cantidad < l.cantidad_leida - 0.000001 THEN 1
                                 WHEN s.stock_minimo > l.stock_minimo_leido + 0.000001 THEN 1
                                 ELSE 0
                               END) AS pendientes
                        FROM productos p
                        INNER JOIN stock s ON s.id = p.id_stock
                        LEFT JOIN stock_alertas_leidas l ON l.id_producto = p.id AND l.usuario = ?
                        WHERE p.activo = 1
                          AND s.activo = 1
                          AND s.tipo_stock = 'propio'
                          AND s.cantidad <= s.stock_minimo";
                $st = $pdo->prepare($sql);
                $st->execute([$id_usuario]);
                $row = $st->fetch() ?: [];
                $total = (int)($row["total"] ?? 0);
                $pendientes = (int)($row["pendientes"] ?? 0);
                $resumen = ["total" => $total, "pendientes" => $pendientes, "leidas" => max(0, $total - $pendientes)];
            } catch (Throwable $e) {
                registrar_log("Stock::resumen_alertas_stock_bajo", $e->getMessage());
            }
        }
        return $resumen;
    }

    public static function marcar_alerta_leida(int $id_producto, int $id_usuario): bool {
        self::asegurar_columnas_minmax();
        self::asegurar_tabla_alertas_leidas();
        $pdo = obtener_pdo();
        if ($pdo === null || $id_producto <= 0)
            return false;
        try {
            $sql = "SELECT p.id, s.cantidad, s.stock_minimo
                    FROM productos p
                    INNER JOIN stock s ON s.id = p.id_stock
                    WHERE p.id = ? AND p.activo = 1 AND s.activo = 1 AND s.tipo_stock = 'propio' AND s.cantidad <= s.stock_minimo
                    LIMIT 1";
            $st = $pdo->prepare($sql);
            $st->execute([$id_producto]);
            $row = $st->fetch();
            if (!$row)
                return false;
            $usuario = max(0, $id_usuario);
            $ins = $pdo->prepare("INSERT INTO stock_alertas_leidas (id_producto, fecha_lectura, usuario, cantidad_leida, stock_minimo_leido)
                                  VALUES (?, NOW(), ?, ?, ?)
                                  ON DUPLICATE KEY UPDATE fecha_lectura = VALUES(fecha_lectura), cantidad_leida = VALUES(cantidad_leida), stock_minimo_leido = VALUES(stock_minimo_leido)");
            return $ins->execute([$id_producto, $usuario, (float)$row["cantidad"], (float)$row["stock_minimo"]]);
        } catch (Throwable $e) {
            registrar_log("Stock::marcar_alerta_leida", $e->getMessage());
            return false;
        }
    }

    public static function listar_faltantes(bool $solo_minimo = true): array {
        self::asegurar_columnas_minmax();
        $lista = [];
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                $where = $solo_minimo ? "WHERE s.activo = 1 AND s.stock_minimo > 0 AND s.cantidad <= s.stock_minimo" : "WHERE s.activo = 1";
                $sql = "SELECT s.id, s.nombre, s.unidad, s.tipo_stock, s.cantidad, s.stock_minimo, s.stock_maximo, s.precio_costo,
                               COALESCE(um.decimales, 3) AS unidad_decimales,
                               CASE
                                 WHEN s.stock_maximo > 0 AND s.stock_maximo > s.cantidad THEN s.stock_maximo - s.cantidad
                                 WHEN s.stock_minimo > 0 AND s.stock_minimo > s.cantidad THEN s.stock_minimo - s.cantidad
                                 ELSE 0
                               END AS cantidad_sugerida
                        FROM stock s
                        LEFT JOIN unidades_medida um ON um.abreviatura COLLATE utf8mb4_unicode_ci = s.unidad COLLATE utf8mb4_unicode_ci
                        $where
                        ORDER BY (s.cantidad <= s.stock_minimo AND s.stock_minimo > 0) DESC, s.nombre ASC";
                $st = $pdo->prepare($sql);
                $st->execute();
                $rows = $st->fetchAll();
                if (is_array($rows))
                    $lista = $rows;
            } catch (Throwable $e) {
                registrar_log("Stock::listar_faltantes", $e->getMessage());
            }
        }
        return $lista;
    }
}
